<?php

namespace App\Http\Controllers\Institute\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\Institute;
use App\Models\PracticalFeeTokenBatch;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\WalletService;
use App\Support\AcademicState;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    private array $customReportFeeSummaryCache = [];

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return auth()->guard('staff')->user();
    }

    private function authenticatedUser()
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }

    private function panelPrefix(): string
    {
        if (auth()->guard('staff')->check()) {
            return 'staff';
        }

        if (auth()->guard('center')->check()) {
            return 'center';
        }

        if (auth()->guard('partner')->check()) {
            return 'partner';
        }

        return 'institute';
    }

    private function instituteId(): int
    {
        $user = $this->authenticatedUser();

        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    private function authorizeReportAccess(string $report): void
    {
        $user = $this->authenticatedUser();

        if ($this->panelPrefix() !== 'staff' || !$user) {
            return;
        }

        $allowed = match ($report) {
            'fee' => $user->canViewFeeReports(),
            'admission' => $user->canViewAdmissionReports(),
            default => false,
        };

        abort_unless($allowed, 403, 'Report permission required.');
    }

    private function ensureReportExportPermission(Request $request): void
    {
        if (!$request->filled('export') || !auth()->guard('staff')->check()) {
            return;
        }

        abort_unless($this->currentStaff()?->canExportReports(), 403, 'Report export permission required.');
    }

    private function applyStaffStudentScope($query)
    {
        if ($staff = $this->currentStaff()) {
            $staff->scopeAdmissionStudents($query);
        }

        return $query;
    }

    private function applyStaffOperationalStudentScope($query)
    {
        if ($staff = $this->currentStaff()) {
            $staff->scopeOperationalStudents($query);
        }

        return $query;
    }

    private function applyStaffFeeScope($query)
    {
        if (!$staff = $this->currentStaff()) {
            return $query;
        }

        $staff->scopeFeeInvoices($query);

        if ($staff->hasRestrictedCourseAccess()) {
            $query->whereHas('student.stream', fn($streamQuery) => $streamQuery->whereIn('course_id', $staff->allowedCourseIds() ?: [-1]));
        }

        return $query;
    }

    // ════════════════════════════════════════════════════════════════════
    //  FEE DUE LIST
    //  Logic: Student ka total payable fee - total paid = due amount
    //  Sirf woh students dikhao jinki due > 0 hai
    // ════════════════════════════════════════════════════════════════════
    public function feeDueList(Request $request)
    {
        $this->authorizeReportAccess('fee');
        $this->ensureReportExportPermission($request);
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId = $request->session_id ?? $activeSession?->id;
        $filterSemester = $request->integer('semester', 0);

        $sessions       = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes    = CourseType::where('institute_id', $instituteId)->orderBy('name')->get();
        $courses        = Course::where('institute_id', $instituteId)->where('status', true)
            ->when($request->course_type_id, fn($q) => $q->where('course_type_id', $request->course_type_id))
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();
        $centers        = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $channelPartners= ChannelPartner::where('institute_id', $instituteId)->orderBy('name')->get();

        // ── Students query ───────────────────────────────────────────────
        $query = Student::with(['stream.course', 'session', 'coursePart'])
            ->where('institute_id', $instituteId)
            ->where('status', 'active');
        $this->applyStaffOperationalStudentScope($query);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        if ($request->course_type_id) {
            $query->whereHas('stream.course', fn($q) => $q->where('course_type_id', $request->course_type_id));
        }

        if ($request->course_id) {
            $query->whereHas('stream', fn($q) =>
                $q->where('course_id', $request->course_id)
            );
        }

        if ($request->stream_id) {
            $query->where('course_stream_id', $request->stream_id);
        }

        if ($filterSemester > 0) {
            $query->where('current_semester', $filterSemester);
        }

        // Source filter (replaces center_id)
        if ($request->source) {
            $src   = $request->source;
            $srcId = $request->source_id;
            if ($src === 'direct') {
                $query->where(fn($q) => $q->where('admission_source', 'direct')
                    ->orWhereNull('admission_source')->orWhere('admission_source', ''));
            } elseif ($src === 'center') {
                $query->where('admission_source', 'center');
                if ($srcId) $query->where('admission_source_id', $srcId);
            } elseif ($src === 'channel') {
                $query->where('admission_source', 'channel');
                if ($srcId) $query->where('admission_source_id', $srcId);
            }
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('student_uid', 'like', "%{$s}%")
                  ->orWhere('enrollment_no', 'like', "%{$s}%");
            });
        }

        // ── Paginate aur due calculate karo ─────────────────────────────
        $perPage  = $request->integer('per_page', 20);
        $perPage  = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $students = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $sessionObj = AcademicSession::find($sessionId);

        // Har student ke liye due calculate karo
        $dueData          = [];
        $totalDue         = 0;
        $totalPaid        = 0;
        $totalCollection  = 0;
        $totalDiscount    = 0;
        $totalPayable     = 0;
        $totalFine        = 0;
        $totalLibraryFine = 0;

        foreach ($students as $student) {
            $context = WalletService::resolveAcademicContext($student, (int) $sessionId);
            if (!empty($context['course_part'])) {
                $student->setRelation('coursePart', $context['course_part']);
            }
            $student->resolved_year_number = $context['course_part_year'];
            $student->resolved_year_label = AcademicState::yearLabel(
                $student->stream?->course?->structure_type,
                $context['semester'] ?? $student->current_semester,
                $context['course_part_year'],
                $student->stream?->course?->effectiveSemestersPerYear() ?? 2
            );

            $summary    = WalletService::getStudentSummary($student, (int) $sessionId);
            $basePayable = $this->calculatePayable($student, $sessionId, (int) ($context['semester'] ?? 1), $instituteId);
            $collection     = (float) ($summary['total_collection'] ?? 0);
            $discount       = (float) ($summary['total_discount'] ?? 0);
            $fine           = (float) ($summary['total_fine'] ?? 0);
            $libraryFineDue = (float) ($summary['library_fine_due'] ?? 0);
            $payable        = $basePayable + $fine;
            $paid           = $collection;
            $due            = (float) ($summary['total_due'] ?? 0);

            $dueData[$student->id] = [
                'payable'      => $payable,
                'paid'         => $paid,
                'collection'   => $collection,
                'discount'     => $discount,
                'fine'         => $fine,
                'library_fine' => $libraryFineDue,
                'due'          => $due,
            ];

            $totalDue         += $due;
            $totalPaid        += $paid;
            $totalCollection  += $collection;
            $totalDiscount    += $discount;
            $totalPayable     += $payable;
            $totalFine        += $fine;
            $totalLibraryFine += $libraryFineDue;
        }

        $showAll = $request->boolean('show_all', false);
        $summary = $this->getSessionSummary($instituteId, $sessionId);

        // Export CSV / Excel / PDF
        if (in_array($request->export, ['csv', 'excel', 'pdf'])) {
            $allStudents = $query->orderBy('name')->get();
            $allDueData  = [];

            // Bulk-fetch wallet balances + transaction aggregates (replaces N×7 queries with 3 queries)
            $exportIds = $allStudents->pluck('id')->all();

            $walletsMap = StudentWallet::whereIn('student_id', $exportIds)
                ->where('academic_session_id', (int) $sessionId)
                ->get(['student_id', 'main_b'])
                ->keyBy('student_id');

            $txnAggMap = StudentTransaction::whereIn('student_id', $exportIds)
                ->where('academic_session_id', (int) $sessionId)
                ->selectRaw("student_id,
                    SUM(CASE WHEN `des` LIKE 'Fee paid%'          THEN `credit` ELSE 0 END) as fee_col,
                    SUM(CASE WHEN `des` LIKE 'Fee cancelled%'     THEN `debit`  ELSE 0 END) as fee_rev,
                    SUM(CASE WHEN `des` LIKE 'Discount granted%'  THEN `credit` ELSE 0 END) as disc_col,
                    SUM(CASE WHEN `des` LIKE 'Discount reversed%' THEN `debit`  ELSE 0 END) as disc_rev,
                    SUM(CASE WHEN `des` LIKE 'Fine charged:%'     THEN `debit`  ELSE 0 END) as fine_col,
                    SUM(CASE WHEN `des` LIKE 'Fine reversed%'     THEN `credit` ELSE 0 END) as fine_rev
                ")
                ->groupBy('student_id')
                ->get()
                ->keyBy('student_id');

            $libFineMap = \Illuminate\Support\Facades\DB::table('library_transactions as lt')
                ->join('library_members as lm', 'lm.id', '=', 'lt.library_member_id')
                ->whereIn('lm.student_id', $exportIds)
                ->selectRaw('lm.student_id, COALESCE(SUM(GREATEST(lt.fine_amount - lt.fine_paid, 0)), 0) as lib_fine')
                ->groupBy('lm.student_id')
                ->get()
                ->keyBy('student_id');

            foreach ($allStudents as $student) {
                $context = WalletService::resolveAcademicContext($student, (int) $sessionId);
                if (!empty($context['course_part'])) {
                    $student->setRelation('coursePart', $context['course_part']);
                }
                $student->resolved_year_label = AcademicState::yearLabel(
                    $student->stream?->course?->structure_type,
                    $context['semester'] ?? $student->current_semester,
                    $context['course_part_year'] ?? null,
                    $student->stream?->course?->effectiveSemestersPerYear() ?? 2
                );

                $txn            = $txnAggMap->get($student->id);
                $collection     = max(0.0, (float) ($txn->fee_col  ?? 0) - (float) ($txn->fee_rev  ?? 0));
                $discount       = max(0.0, (float) ($txn->disc_col ?? 0) - (float) ($txn->disc_rev ?? 0));
                $fine           = max(0.0, (float) ($txn->fine_col ?? 0) - (float) ($txn->fine_rev ?? 0));
                $libraryFineDue = max(0.0, (float) ($libFineMap->get($student->id)?->lib_fine ?? 0));
                $walletBal      = (float) ($walletsMap->get($student->id)?->main_b ?? 0);
                $feeDue         = max(0.0, -$walletBal);
                $due            = $feeDue + $libraryFineDue;
                $payable        = $collection + $discount + $due;

                $allDueData[$student->id] = [
                    'payable'      => $payable,
                    'paid'         => $collection,
                    'collection'   => $collection,
                    'discount'     => $discount,
                    'fine'         => $fine,
                    'library_fine' => $libraryFineDue,
                    'due'          => $due,
                ];
            }

            if ($request->export === 'pdf') {
                $institute   = \App\Models\Institute::find($instituteId);
                return view('institute.reports.fee-due-list-print', [
                    'allStudents'  => $allStudents,
                    'allDueData'   => $allDueData,
                    'sessionObj'   => $sessionObj,
                    'instituteName'=> $institute?->name ?? 'Institute',
                ]);
            }

            $headers = ['#', 'Student ID', 'Roll No', 'Name', 'Father Name', 'Mother Name', 'Mobile',
                        'Course', 'Stream', 'Year', 'Semester',
                        'Total Payable (Rs)', 'Paid (Rs)', 'Discount (Rs)', 'Fine (Rs)', 'Library Fine (Rs)', 'Due (Rs)'];
            $exportRows = [];
            foreach ($allStudents as $i => $student) {
                $d = $allDueData[$student->id] ?? [];
                $due = $d['due'] ?? 0;
                if (!$showAll && $due <= 0) continue;
                $exportRows[] = [
                    $i + 1,
                    $student->student_uid,
                    $student->roll_no ?? '',
                    $student->name,
                    $student->father_name ?? '',
                    $student->mother_name ?? '',
                    $student->mobile,
                    $student->stream->course->name ?? '',
                    $student->stream->name ?? '',
                    $student->resolved_year_label ?? '',
                    $student->current_semester ? 'Sem '.$student->current_semester : '',
                    number_format($d['payable'] ?? 0, 2),
                    number_format($d['paid'] ?? 0, 2),
                    number_format($d['discount'] ?? 0, 2),
                    number_format($d['fine'] ?? 0, 2),
                    number_format($d['library_fine'] ?? 0, 2),
                    number_format($d['due'] ?? 0, 2),
                ];
            }

            $filename = 'fee-due-list-' . now()->format('Ymd');
            if ($request->export === 'excel') {
                return $this->exportSimpleExcel('Fee Due List', $headers, $exportRows, $filename);
            }
            return $this->exportCsv($headers, $exportRows, $filename);
        }

        // Streams for course filter AJAX
        $streams = $request->course_id
            ? CourseStream::where('course_id', $request->course_id)
                ->where('status', true)->orderBy('name')->get()
            : collect();

        return view('institute.reports.fee-due-list', compact(
            'students', 'dueData', 'sessions', 'courseTypes', 'courses', 'centers', 'channelPartners', 'streams',
            'activeSession', 'sessionId', 'sessionObj', 'filterSemester',
            'totalDue', 'totalPaid', 'totalCollection', 'totalDiscount', 'totalPayable', 'totalFine', 'totalLibraryFine',
            'perPage', 'showAll', 'summary'
        ));
    }

    // ── Total payable fee calculate karo (FeeCalculatorService use karke) ──
    private function calculatePayable(Student $student, ?int $sessionId, int $semester, int $instituteId): float
    {
        if (!$student->stream || !$sessionId) {
            return 0;
        }

        try {
            $feeState = WalletService::buildPromotionAwareFeeState($student, (int) $sessionId);

            return (float) ($feeState['total'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ── Is student ne is session mein kitna cash pay kiya (discount exclude) ──
    private function getCollectionAmount(int $studentId, ?int $sessionId): float
    {
        return (float) FeeInvoice::where('student_id', $studentId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('paid_amount');
    }

    // ── Is student ko kitna discount mila ──
    private function getDiscountAmount(int $studentId, ?int $sessionId): float
    {
        return (float) FeeInvoice::where('student_id', $studentId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('discount');
    }

    // ── Is student par kitna fine laga ──
    private function getFineAmount(int $studentId, ?int $sessionId): float
    {
        $charged = (float) \App\Models\StudentTransaction::where('student_id', $studentId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('des', 'like', 'Fine charged:%')
            ->sum('debit');

        $reversed = (float) \App\Models\StudentTransaction::where('student_id', $studentId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('des', 'like', 'Fine reversed%')
            ->sum('credit');

        return max(0, $charged - $reversed);
    }

    // ── Session-level summary stats ──
    private function getSessionSummary(int $instituteId, ?int $sessionId): array
    {
        $totalStudents = Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->count();

        $totalCollectedQuery = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));
        $this->applyStaffFeeScope($totalCollectedQuery);
        $totalCollected = (float) $totalCollectedQuery->sum('paid_amount');

        // Students jinse koi bhi payment nahi hui
        $paidStudentIds = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->distinct()->pluck('student_id');

        $unpaidCount = Student::where('institute_id', $instituteId)
            ->where('status', 'active')
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereNotIn('id', $paidStudentIds)
            ->count();

        return [
            'total_students'  => $totalStudents,
            'total_collected' => $totalCollected,
            'unpaid_count'    => $unpaidCount,
            'paid_count'      => $totalStudents - $unpaidCount,
        ];
    }


    // ════════════════════════════════════════════════════════════════════
    //  FEE COLLECTION REPORT
    //  Date-wise / mode-wise / course-wise collection summary
    // ════════════════════════════════════════════════════════════════════
    public function feeCollectionReport(\Illuminate\Http\Request $request)
    {
        $this->authorizeReportAccess('fee');
        $this->ensureReportExportPermission($request);
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId = $request->session_id ?? $activeSession?->id;

        // Default: today
        $dateFrom = $request->date_from ?? now()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes = CourseType::where('institute_id', $instituteId)->orderBy('name')->get();
        $courses     = Course::where('institute_id', $instituteId)->where('status', true)
            ->when($request->course_type_id, fn($q) => $q->where('course_type_id', $request->course_type_id))
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();
        $streams     = $request->course_id
            ? CourseStream::where('course_id', $request->course_id)->where('status', true)->orderBy('name')->get()
            : collect();
        $centers     = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionObj  = AcademicSession::find($sessionId);

        // ── Scope check: staff with only fee_collect sees only own records ──
        $isLimitedCollector = false;
        $limitedToCollector = null;
        if (auth()->guard('staff')->check()) {
            $staffUser = auth()->guard('staff')->user();
            $canSeeAll = $staffUser->hasPermission('fee_reports')
                      || $staffUser->hasPermission('reports')
                      || $staffUser->hasPermission('fee_view');
            if (!$canSeeAll && $staffUser->hasPermission('fee_collect')) {
                $isLimitedCollector = true;
                $limitedToCollector = $staffUser->name;
            }
        }

        $collectedByList = $isLimitedCollector
            ? collect([$limitedToCollector])
            : FeeInvoice::where('institute_id', $instituteId)
                ->whereNotNull('collected_by')->distinct()->pluck('collected_by')->sort()->values();

        // ── Base query ─────────────────────────────────────────────────
        $query = FeeInvoice::with(['student.stream.course', 'student.coursePart', 'items', 'bankAccount'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $this->applyStaffFeeScope($query);
        if ($isLimitedCollector)        $query->where('collected_by', $limitedToCollector);
        if ($request->payment_mode)     $query->where('payment_mode', $request->payment_mode);
        if ($request->course_type_id)   $query->whereHas('student.stream.course', fn($q) => $q->where('course_type_id', $request->course_type_id));
        if ($request->course_id)        $query->whereHas('student.stream', fn($q) => $q->where('course_id', $request->course_id));
        if ($request->stream_id)        $query->whereHas('student', fn($q) => $q->where('course_stream_id', $request->stream_id));
        if ($request->semester)         $query->where('semester', $request->semester);
        if (!$isLimitedCollector && $request->collected_by) $query->where('collected_by', $request->collected_by);
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('invoice_no', 'like', "%{$s}%")
                ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('student_uid', 'like', "%{$s}%")));
        }

        $perPage  = $request->integer('per_page', 20);
        $perPage  = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;
        $invoices = $query->orderByDesc('payment_date')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        // Export CSV / Excel
        if (in_array($request->export, ['csv', 'excel'])) {
            $allInvoices = $query->orderByDesc('payment_date')->get();
            $expHeaders  = ['Invoice No', 'Date', 'Time', 'Year', 'Semester', 'Student', 'Student ID',
                            'Roll No', 'UIN', 'Father Name', 'Mother Name', 'Course', 'Stream',
                            'Fee Items', 'Bank / Account', 'Transaction Ref', 'Collected By',
                            'Payment Mode', 'Collection (Rs)', 'Fine (Rs)', 'Discount (Rs)', 'Total (Rs)'];
            $expRows = $allInvoices->map(fn($inv) => [
                $inv->invoice_no,
                $inv->payment_date?->format('d/m/Y'),
                $inv->created_at?->setTimezone('Asia/Kolkata')->format('h:i A'),
                $inv->student?->coursePart?->year_label ?? '—',
                $inv->semester ? 'Sem ' . $inv->semester : '—',
                $inv->student->name ?? '',
                $inv->student->student_uid ?? '',
                $inv->student->roll_no ?? '—',
                $inv->student->uin_no ?? '—',
                $inv->student->father_name ?? '—',
                $inv->student->mother_name ?? '—',
                $inv->student->stream->course->name ?? '',
                $inv->student->stream->name ?? '',
                $inv->items->pluck('fee_name')->implode(', '),
                $inv->bankAccount?->display_label ?: ($inv->bank_name ?: '—'),
                $inv->transaction_ref ?? '—',
                $inv->collected_by ?? '—',
                strtoupper($inv->payment_mode),
                number_format($inv->paid_amount, 2),
                number_format($inv->items->sum('fine'), 2),
                number_format($inv->discount ?? 0, 2),
                number_format($inv->paid_amount + ($inv->discount ?? 0), 2),
            ])->toArray();
            $filename  = 'fee-collection-report-' . now()->format('Ymd');
            if ($request->export === 'excel') {
                $instName  = \App\Models\Institute::find($instituteId)?->name ?? 'Institute';
                $dateRange = \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y');
                $sessName  = $sessionId ? ($sessionObj?->name ?? 'Session') : 'All Sessions';
                return $this->exportFeeCollectionExcel($expHeaders, $expRows, $filename . '.xlsx', $instName, $dateRange, $sessName);
            }
            return $this->exportCsv($expHeaders, $expRows, $filename . '.csv');
        }

        // ── Summary stats (same filters) ──────────────────────────────
        $statsBase = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId,           fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->when($isLimitedCollector,  fn($q) => $q->where('collected_by', $limitedToCollector))
            ->when($request->payment_mode,   fn($q) => $q->where('payment_mode', $request->payment_mode))
            ->when($request->course_type_id, fn($q) => $q->whereHas('student.stream.course', fn($sq) => $sq->where('course_type_id', $request->course_type_id)))
            ->when($request->course_id,      fn($q) => $q->whereHas('student.stream', fn($sq) => $sq->where('course_id', $request->course_id)))
            ->when($request->stream_id,      fn($q) => $q->whereHas('student', fn($sq) => $sq->where('course_stream_id', $request->stream_id)))
            ->when($request->semester,       fn($q) => $q->where('semester', $request->semester))
            ->when(!$isLimitedCollector && $request->collected_by, fn($q) => $q->where('collected_by', $request->collected_by));
        $this->applyStaffFeeScope($statsBase);

        $totalCollected = (float)(clone $statsBase)->sum('paid_amount');
        $totalDiscount  = (float)(clone $statsBase)->sum('discount');
        $totalInvoices  = (clone $statsBase)->count();
        $totalStudents  = (clone $statsBase)->distinct('student_id')->count('student_id');

        // Total Fine (from StudentTransactions for students who paid in this date range/session)
        $fineStudentIds = (clone $statsBase)->distinct('student_id')->pluck('student_id');
        $totalFine = 0.0;
        if ($fineStudentIds->isNotEmpty()) {
            $totalFine = (float) \App\Models\StudentTransaction::whereIn('student_id', $fineStudentIds)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->where('des', 'like', 'Fine charged:%')
                ->sum('debit');
        }

        // Mode-wise breakdown
        $modeWise = (clone $statsBase)
            ->selectRaw('payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('payment_mode')->orderByDesc('total')->get();

        // Mode -> Bank -> Collector breakdown for popup (join bank accounts for display_label)
        $modeBankQuery = FeeInvoice::leftJoin('institute_bank_accounts as iba', 'fee_invoices.bank_account_id', '=', 'iba.id')
            ->where('fee_invoices.institute_id', $instituteId)
            ->where('fee_invoices.is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('fee_invoices.academic_session_id', $sessionId))
            ->whereDate('fee_invoices.payment_date', '>=', $dateFrom)
            ->whereDate('fee_invoices.payment_date', '<=', $dateTo);
        $this->applyStaffFeeScope($modeBankQuery);
        if ($isLimitedCollector)    $modeBankQuery->where('fee_invoices.collected_by', $limitedToCollector);
        if ($request->payment_mode) $modeBankQuery->where('fee_invoices.payment_mode', $request->payment_mode);
        if ($request->course_id)    $modeBankQuery->whereHas('student.stream', fn($q) => $q->where('course_id', $request->course_id));
        if ($request->semester)     $modeBankQuery->where('fee_invoices.semester', $request->semester);
        if (!$isLimitedCollector && $request->collected_by) $modeBankQuery->where('fee_invoices.collected_by', $request->collected_by);

        $modeBankWise = $modeBankQuery
            ->selectRaw("fee_invoices.payment_mode,
                COALESCE(iba.display_label, iba.bank_name, fee_invoices.bank_name, '') as bank_label,
                COALESCE(fee_invoices.collected_by, '--') as collector,
                COUNT(*) as cnt,
                SUM(fee_invoices.paid_amount) as total")
            ->groupBy('fee_invoices.payment_mode', 'iba.id', 'iba.display_label', 'iba.bank_name', 'fee_invoices.bank_name', 'fee_invoices.collected_by')
            ->orderBy('fee_invoices.payment_mode')
            ->orderByDesc('total')
            ->get()
            ->groupBy('payment_mode');

        // Fee-type wise breakdown (with charged/paid/disc columns)
        $feeTypeWise = \App\Models\FeeInvoiceItem::whereHas('invoice', fn($q) => $q
                ->where('institute_id', $instituteId)
                ->where('is_cancelled', false)
                ->when($sessionId, fn($q2) => $q2->where('academic_session_id', $sessionId))
                ->whereDate('payment_date', '>=', $dateFrom)
                ->whereDate('payment_date', '<=', $dateTo)
                ->when($request->payment_mode, fn($q2) => $q2->where('payment_mode', $request->payment_mode))
                ->when($request->course_id, fn($q2) => $q2->whereHas('student.stream', fn($sq) => $sq->where('course_id', $request->course_id)))
                ->when($request->semester, fn($q2) => $q2->where('semester', $request->semester))
                ->when($isLimitedCollector, fn($q2) => $q2->where('collected_by', $limitedToCollector))
                ->when(!$isLimitedCollector && $request->collected_by, fn($q2) => $q2->where('collected_by', $request->collected_by)))
            ->selectRaw('fee_name, COUNT(*) as cnt, SUM(COALESCE(total_fee, amount)) as charged_total, SUM(amount) as paid_total, SUM(COALESCE(discount, 0)) as disc_total, SUM(COALESCE(fine, 0)) as fine_total')
            ->groupBy('fee_name')->orderByDesc('charged_total')->get();

        // ── Collector-wise breakdown (Name | Cash | UPI | Online | Cheque | DD | Total) ──
        $collectorWise = (clone $statsBase)
            ->selectRaw("collected_by,
                SUM(CASE WHEN payment_mode='cash'   THEN paid_amount ELSE 0 END) as cash_amt,
                SUM(CASE WHEN payment_mode='upi'    THEN paid_amount ELSE 0 END) as upi_amt,
                SUM(CASE WHEN payment_mode='online' THEN paid_amount ELSE 0 END) as online_amt,
                SUM(CASE WHEN payment_mode='cheque' THEN paid_amount ELSE 0 END) as cheque_amt,
                SUM(CASE WHEN payment_mode='dd'     THEN paid_amount ELSE 0 END) as dd_amt,
                SUM(paid_amount) as total_amt,
                COUNT(*) as invoice_cnt")
            ->groupBy('collected_by')
            ->orderByDesc('total_amt')
            ->get();

        // ── Bank-wise breakdown ──────────────────────────────────────────
        $bankBaseJoin = fn($q) => $q
            ->leftJoin('institute_bank_accounts as iba', 'fee_invoices.bank_account_id', '=', 'iba.id')
            ->where('fee_invoices.institute_id', $instituteId)
            ->where('fee_invoices.is_cancelled', false)
            ->when($sessionId,           fn($q2) => $q2->where('fee_invoices.academic_session_id', $sessionId))
            ->whereDate('fee_invoices.payment_date', '>=', $dateFrom)
            ->whereDate('fee_invoices.payment_date', '<=', $dateTo)
            ->when($isLimitedCollector,  fn($q2) => $q2->where('fee_invoices.collected_by', $limitedToCollector))
            ->when($request->payment_mode,   fn($q2) => $q2->where('fee_invoices.payment_mode', $request->payment_mode))
            ->when($request->course_type_id, fn($q2) => $q2->whereHas('student.stream.course', fn($sq) => $sq->where('course_type_id', $request->course_type_id)))
            ->when($request->course_id,      fn($q2) => $q2->whereHas('student.stream', fn($sq) => $sq->where('course_id', $request->course_id)))
            ->when($request->stream_id,      fn($q2) => $q2->whereHas('student', fn($sq) => $sq->where('course_stream_id', $request->stream_id)))
            ->when($request->semester,       fn($q2) => $q2->where('fee_invoices.semester', $request->semester))
            ->when(!$isLimitedCollector && $request->collected_by, fn($q2) => $q2->where('fee_invoices.collected_by', $request->collected_by));

        $bankWise = FeeInvoice::query();
        $bankBaseJoin($bankWise);
        $bankWise = $bankWise
            ->selectRaw("COALESCE(iba.display_label, iba.bank_name, fee_invoices.bank_name, '— Cash / Direct —') as bank_label,
                fee_invoices.bank_account_id,
                COUNT(*) as cnt,
                SUM(fee_invoices.paid_amount) as total")
            ->groupBy('fee_invoices.bank_account_id', 'iba.display_label', 'iba.bank_name', 'fee_invoices.bank_name')
            ->orderByDesc('total')
            ->get();

        $bankDetailRaw = FeeInvoice::query();
        $bankBaseJoin($bankDetailRaw);
        $bankDetailWise = $bankDetailRaw
            ->selectRaw("COALESCE(iba.display_label, iba.bank_name, fee_invoices.bank_name, '— Cash / Direct —') as bank_label,
                fee_invoices.bank_account_id,
                COALESCE(fee_invoices.collected_by, '--') as collector,
                fee_invoices.payment_mode,
                COUNT(*) as cnt,
                SUM(fee_invoices.paid_amount) as total")
            ->groupBy('fee_invoices.bank_account_id', 'iba.display_label', 'iba.bank_name', 'fee_invoices.bank_name',
                      'fee_invoices.collected_by', 'fee_invoices.payment_mode')
            ->orderByDesc('total')
            ->get()
            ->groupBy('bank_label');

        return view('institute.reports.fee-collection-report', compact(
            'invoices', 'sessions', 'courseTypes', 'courses', 'streams', 'centers', 'sessionObj', 'sessionId',
            'activeSession', 'totalCollected', 'totalDiscount', 'totalInvoices',
            'totalStudents', 'totalFine', 'modeWise', 'modeBankWise', 'feeTypeWise',
            'bankWise', 'bankDetailWise',
            'perPage', 'dateFrom', 'dateTo', 'collectedByList', 'collectorWise',
            'isLimitedCollector', 'limitedToCollector'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  ADMISSION REPORT
    //  Session-wise, course-wise, source-wise admission statistics
    // ════════════════════════════════════════════════════════════════════
    public function cancelledFeeReport(Request $request)
    {
        $this->authorizeReportAccess('fee');
        $this->ensureReportExportPermission($request);
        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $sessionId = $request->session_id ?? $activeSession?->id;
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses = Course::where('institute_id', $instituteId)->where('status', true)
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();
        $centers = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionObj = AcademicSession::find($sessionId);

        $collectedByList = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', true)
            ->whereNotNull('collected_by')
            ->distinct()
            ->pluck('collected_by')
            ->sort()
            ->values();

        $query = FeeInvoice::with(['student.stream.course', 'student.coursePart', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', true)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('cancelled_at', '>=', $dateFrom)
            ->whereDate('cancelled_at', '<=', $dateTo);
        $this->applyStaffFeeScope($query);

        if ($request->course_id) {
            $query->whereHas('student.stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->semester) {
            $query->where('semester', $request->semester);
        }
        if ($request->collected_by) {
            $query->where('collected_by', $request->collected_by);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$s}%")
                        ->orWhere('mobile', 'like', "%{$s}%")
                        ->orWhere('student_uid', 'like', "%{$s}%"));
            });
        }

        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
        $invoices = $query->orderByDesc('cancelled_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->export === 'csv') {
            $allInvoices = $query->orderByDesc('cancelled_at')->orderByDesc('id')->get();
            return $this->exportCsv(
                ['Invoice No', 'Cancelled On', 'Student', 'Student ID', 'Course', 'Semester', 'Collected By', 'Paid Amount', 'Discount', 'Cancel Reason'],
                $allInvoices->map(fn($inv) => [
                    $inv->invoice_no,
                    $inv->cancelled_at?->format('d/m/Y h:i A'),
                    $inv->student->name ?? '',
                    $inv->student->student_uid ?? '',
                    $inv->student->stream->course->name ?? '',
                    $inv->semester ? 'Sem ' . $inv->semester : '-',
                    $inv->collected_by ?? '-',
                    number_format($inv->paid_amount, 2, '.', ''),
                    number_format($inv->discount ?? 0, 2, '.', ''),
                    $inv->cancel_reason ?? '',
                ])->toArray(),
                'cancelled-fee-report.csv'
            );
        }

        $statsBase = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', true)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('cancelled_at', '>=', $dateFrom)
            ->whereDate('cancelled_at', '<=', $dateTo)
            ->when($request->course_id, fn($q) => $q->whereHas('student.stream', fn($sq) => $sq->where('course_id', $request->course_id)))
            ->when($request->semester, fn($q) => $q->where('semester', $request->semester))
            ->when($request->collected_by, fn($q) => $q->where('collected_by', $request->collected_by));

        $totalCancelledInvoices = (clone $statsBase)->count();
        $totalCancelledAmount = (float) (clone $statsBase)->sum('paid_amount');
        $totalCancelledDiscount = (float) (clone $statsBase)->sum('discount');
        $totalStudents = (clone $statsBase)->distinct('student_id')->count('student_id');

        return view('institute.reports.cancelled-fee-report', compact(
            'invoices',
            'sessions',
            'courses',
            'centers',
            'sessionObj',
            'sessionId',
            'activeSession',
            'perPage',
            'dateFrom',
            'dateTo',
            'collectedByList',
            'totalCancelledInvoices',
            'totalCancelledAmount',
            'totalCancelledDiscount',
            'totalStudents'
        ));
    }

    public function admissionReport(\Illuminate\Http\Request $request)
    {
        $this->authorizeReportAccess('admission');
        $this->ensureReportExportPermission($request);
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId = $request->session_id ?? $activeSession?->id;

        $sessions    = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes = CourseType::where('institute_id', $instituteId)->orderBy('name')->get();
        $courses     = Course::where('institute_id', $instituteId)->where('status', true)
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();
        $streams     = CourseStream::where('status', true)
            ->whereIn('course_id', $courses->pluck('id'))
            ->orderBy('name')->get();
        $centers     = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $partners    = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionObj  = AcademicSession::find($sessionId);

        $centersMap  = $centers->pluck('name', 'id')->toArray();
        $partnersMap = $partners->pluck('name', 'id')->toArray();

        // ── Base query ────────────────────────────────────────────────────
        $query = Student::with(['session', 'stream.course', 'coursePart', 'admittedBy'])
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));
        $this->applyStaffStudentScope($query);

        if ($request->filled('course_type_id')) {
            $query->whereHas('stream.course', fn($q) => $q->where('course_type_id', $request->course_type_id));
        }
        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->filled('stream_id')) {
            $query->where('course_stream_id', $request->stream_id);
        }
        if ($request->filled('current_semester')) {
            $query->where('current_semester', (int) $request->current_semester);
        }
        if ($request->filled('admission_source')) {
            $query->where('admission_source', $request->admission_source);
        }
        if ($request->filled('center_id')) {
            $query->where('admission_source', 'center')
                  ->where('admission_source_id', $request->center_id);
        }
        if ($request->filled('partner_id')) {
            $query->whereIn('admission_source', ['partner', 'channel_partner'])
                  ->where('admission_source_id', $request->partner_id);
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('student_uid', 'like', "%{$s}%");
            });
        }

        $perPage  = $request->integer('per_page', 20);
        $perPage  = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;
        $students = $query->orderByDesc('admission_date')->paginate($perPage)->withQueryString();

        foreach ($students as $student) {
            if ($student->academic_session_id) {
                $context = WalletService::resolveAcademicContext($student, (int) $student->academic_session_id);
                if (!empty($context['course_part'])) {
                    $student->setRelation('coursePart', $context['course_part']);
                }
            }
            $student->resolved_year_label = AcademicState::yearLabel(
                $student->stream?->course?->structure_type,
                $student->current_semester,
                $student->coursePart?->year_number,
                $student->stream?->course?->effectiveSemestersPerYear() ?? 2
            );
        }

        // ── Export PDF / Excel / CSV ──────────────────────────────────────
        if (in_array($request->export, ['pdf', 'excel', 'csv'])) {
            $allStudents = $query->orderByDesc('admission_date')->get();

            foreach ($allStudents as $s) {
                if ($s->academic_session_id) {
                    $ctx = WalletService::resolveAcademicContext($s, (int) $s->academic_session_id);
                    if (!empty($ctx['course_part'])) $s->setRelation('coursePart', $ctx['course_part']);
                }
                $s->resolved_year_label = AcademicState::yearLabel(
                    $s->stream?->course?->structure_type,
                    $s->current_semester,
                    $s->coursePart?->year_number,
                    $s->stream?->course?->effectiveSemestersPerYear() ?? 2
                );
            }

            if ($request->export === 'pdf') {
                $institute = Institute::find($instituteId);
                return view('institute.reports.admission-export-pdf', compact(
                    'allStudents', 'institute', 'sessionObj', 'centersMap', 'partnersMap'
                ));
            }

            $expHeaders = ['#', 'Session', 'Student ID', 'Student Name', 'Father Name', 'Mother Name',
                           'Roll No', 'Enroll No', 'UIN No', 'Course', 'Stream', 'Year/Sem',
                           'Gender', 'Category', 'Source', 'Admitted By', 'Adm. Date', 'Status'];
            $expRows = [];
            foreach ($allStudents as $i => $s) {
                $src = $s->admission_source ?? 'direct';
                $srcName = match($src) {
                    'center'                     => ($centersMap[$s->admission_source_id]  ?? null) ? 'Center: '  . $centersMap[$s->admission_source_id]  : 'Center',
                    'partner', 'channel_partner' => ($partnersMap[$s->admission_source_id] ?? null) ? 'Partner: ' . $partnersMap[$s->admission_source_id] : 'Partner',
                    default                      => 'Direct',
                };
                $admittedBy = $s->admittedBy?->name ?? match($src) {
                    'center'                     => $srcName,
                    'partner', 'channel_partner' => $srcName,
                    default                      => 'Admin / Direct',
                };
                $expRows[] = [
                    $i + 1,
                    $s->session?->name ?? '',
                    $s->student_uid ?? '',
                    $s->name,
                    $s->father_name ?? '',
                    $s->mother_name ?? '',
                    $s->roll_no ?? '',
                    $s->enrollment_no ?? '',
                    $s->uin_no ?? '',
                    $s->stream?->course?->name ?? '',
                    $s->stream?->name ?? '',
                    ($s->resolved_year_label ?? '') . ($s->current_semester ? ' / S' . $s->current_semester : ''),
                    ucfirst($s->gender ?? ''),
                    strtoupper($s->category ?? ''),
                    $srcName,
                    $admittedBy,
                    $s->admission_date?->format('d/m/Y') ?? '',
                    ucfirst($s->status ?? 'pending'),
                ];
            }

            $filename = 'admission-report-' . now()->format('Ymd');
            if ($request->export === 'excel') {
                return $this->exportSimpleExcel(
                    'Admission Report — ' . ($sessionObj?->name ?? 'All Sessions'),
                    $expHeaders, $expRows, $filename . '.xlsx'
                );
            }
            return $this->exportCsv($expHeaders, $expRows, $filename . '.csv');
        }

        // ── Summary stats ─────────────────────────────────────────────────
        $baseQ = Student::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));
        $this->applyStaffStudentScope($baseQ);

        $totalAdmissions = (clone $baseQ)->count();
        $maleCount       = (clone $baseQ)->where('gender', 'male')->count();
        $femaleCount     = (clone $baseQ)->where('gender', 'female')->count();
        $todayCount      = (clone $baseQ)->whereDate('admission_date', today())->count();

        // Course-wise breakdown with semester
        $courseWiseRaw = Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->leftJoin('course_streams', 'students.course_stream_id', '=', 'course_streams.id')
            ->leftJoin('courses', 'course_streams.course_id', '=', 'courses.id')
            ->selectRaw('COALESCE(courses.name, "— No Course —") as course_name,
                         COALESCE(students.current_semester, 0) as semester,
                         COUNT(*) as cnt')
            ->groupBy('course_name', 'semester')
            ->orderBy('course_name')
            ->orderBy('semester');
        $this->applyStaffStudentScope($courseWiseRaw);
        $courseWiseRaw = $courseWiseRaw->get();
        $courseWise    = $courseWiseRaw->groupBy('course_name');

        // Source-wise breakdown
        $sourceWise = Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->selectRaw('admission_source, COUNT(*) as cnt')
            ->groupBy('admission_source')
            ->orderByDesc('cnt');
        $this->applyStaffStudentScope($sourceWise);
        $sourceWise = $sourceWise->get();

        // Center-wise detail
        $centerDetail = Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->where('admission_source', 'center')
            ->leftJoin('centers', 'students.admission_source_id', '=', 'centers.id')
            ->leftJoin('course_streams', 'students.course_stream_id', '=', 'course_streams.id')
            ->leftJoin('courses', 'course_streams.course_id', '=', 'courses.id')
            ->selectRaw('COALESCE(centers.name, "Unknown Center") as center_name,
                         COALESCE(courses.name, "— No Course —") as course_name,
                         COUNT(*) as cnt')
            ->groupBy('center_name', 'course_name')
            ->orderBy('center_name')
            ->orderByDesc('cnt');
        $this->applyStaffStudentScope($centerDetail);
        $centerDetail = $centerDetail->get()->groupBy('center_name');

        // Category-wise
        $categoryWise = Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->orderByDesc('cnt');
        $this->applyStaffStudentScope($categoryWise);
        $categoryWise = $categoryWise->get();

        // Admitted-by breakdown: staff name OR center/partner name OR Admin/Direct
        $staffWise = \App\Models\Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->leftJoin('staff_members', 'students.admitted_by_staff_id', '=', 'staff_members.id')
            ->leftJoin('centers', function ($j) {
                $j->on('students.admission_source_id', '=', 'centers.id')
                  ->where('students.admission_source', '=', 'center');
            })
            ->leftJoin('channel_partners', function ($j) {
                $j->on('students.admission_source_id', '=', 'channel_partners.id')
                  ->whereRaw("students.admission_source IN ('partner','channel_partner')");
            })
            ->selectRaw("
                CASE
                    WHEN staff_members.name IS NOT NULL
                        THEN staff_members.name
                    WHEN students.admission_source = 'center'
                        THEN CONCAT('Center: ', COALESCE(centers.name, 'Unknown'))
                    WHEN students.admission_source IN ('partner','channel_partner')
                        THEN CONCAT('Partner: ', COALESCE(channel_partners.name, 'Unknown'))
                    ELSE 'Admin / Direct'
                END as staff_name,
                COUNT(*) as cnt
            ")
            ->groupBy('staff_name')
            ->orderByDesc('cnt');
        $this->applyStaffStudentScope($staffWise);
        $staffWise = $staffWise->get();

        return view('institute.reports.admission-report', compact(
            'students', 'sessions', 'courseTypes', 'courses', 'streams', 'centers', 'partners',
            'centersMap', 'partnersMap',
            'sessionObj', 'sessionId', 'activeSession', 'perPage',
            'totalAdmissions', 'maleCount', 'femaleCount', 'todayCount',
            'courseWise', 'courseWiseRaw', 'sourceWise', 'categoryWise', 'centerDetail',
            'staffWise'
        ));
    }
    // ── AJAX: Course ke streams fetch karo ──────────────────────────────
    public function customStudentReport(Request $request)
    {
        $this->authorizeReportAccess('admission');
        $this->ensureReportExportPermission($request);

        $instituteId = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $sessionId = $request->session_id ?? $activeSession?->id;
        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionObj = AcademicSession::find($sessionId);
        $columns = $this->customReportColumns($instituteId);

        $defaultColumns = array_values(array_intersect(
            ['form_no', 'sr_no', 'name', 'father_name', 'mother_name', 'mobile', 'admission_date'],
            array_keys($columns)
        ));
        if (empty($defaultColumns)) {
            $defaultColumns = array_slice(array_keys($columns), 0, 8);
        }
        $requestedColumns = $request->filled('columns_csv')
            ? array_filter(explode(',', (string) $request->input('columns_csv')))
            : (array) $request->input('columns', $defaultColumns);
        $selectedColumns = array_values(array_intersect($requestedColumns, array_keys($columns)));
        if (empty($selectedColumns)) {
            $selectedColumns = $defaultColumns;
        }
        $columnFilters = $this->customReportColumnFilters($columns, $courses, $instituteId, $sessionId);

        $query = $this->customStudentQuery($request, $instituteId, $sessionId);
        $query = $this->applyCustomReportQueryFilters($query, $request, $columns);
        $usesPostFilters = $this->customReportUsesPostFilters($request, $columns);

        if (in_array($request->export, ['csv', 'excel'], true)) {
            $students = $this->customReportFilteredStudents($query, $request, $columns);
            $rows = $this->customReportRows($students, $selectedColumns, $columns);
            $filename = 'custom-student-report-' . now()->format('Ymd-His');

            if ($request->export === 'excel') {
                return $this->exportExcelTable($selectedColumns, $columns, $rows, $filename . '.xlsx', $sessionObj?->name);
            }

            return $this->exportCsv(
                array_map(fn($key) => $columns[$key]['label'], $selectedColumns),
                $rows,
                $filename . '.csv'
            );
        }

        if ($request->export === 'pdf') {
            $students = $this->customReportFilteredStudents($query, $request, $columns);
            $totalStudents = $students->count();
            $printMode = true;

            return view('institute.reports.custom-student-report-print', compact(
                'students', 'sessions', 'courses', 'sessionId', 'sessionObj', 'activeSession',
                'columns', 'selectedColumns', 'totalStudents', 'printMode'
            ));
        }

        $perPage = $request->integer('per_page', 20);
        $perPage = in_array($perPage, [20, 50, 100, 500, 1000], true) ? $perPage : 20;
        if ($usesPostFilters) {
            $filteredStudents = $this->customReportFilteredStudents($query, $request, $columns);
            $totalStudents = $filteredStudents->count();
            $page = LengthAwarePaginator::resolveCurrentPage();
            $students = new LengthAwarePaginator(
                $filteredStudents->forPage($page, $perPage)->values(),
                $totalStudents,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $totalStudents = (clone $query)->count();
            $students = $query->orderBy('name')->paginate($perPage)->withQueryString();
        }
        $streams = $request->course_id
            ? CourseStream::where('course_id', $request->course_id)->where('status', true)->orderBy('name')->get()
            : collect();
        $printMode = false;

        return view('institute.reports.custom-student-report', compact(
            'students', 'sessions', 'courses', 'streams', 'sessionId', 'sessionObj', 'activeSession',
            'columns', 'selectedColumns', 'totalStudents', 'perPage', 'printMode', 'columnFilters'
        ));
    }

    private function customStudentQuery(Request $request, int $instituteId, ?int $sessionId)
    {
        $query = Student::with(['session', 'stream.course', 'coursePart', 'studentSubjects.subject', 'educationDetails'])
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));
        $this->applyStaffStudentScope($query);

        if ($request->course_id) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->stream_id) {
            $query->where('course_stream_id', $request->stream_id);
        }
        if ($request->gender) {
            $query->where('gender', $request->gender);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('father_name', 'like', "%{$search}%")
                    ->orWhere('mother_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%")
                    ->orWhere('sr_no', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function customReportColumnFilters(array $columns, $courses, int $instituteId, ?int $sessionId): array
    {
        $filters = [];
        $studentOptionColumns = [
            'admission_type', 'admission_source', 'gender', 'category', 'special_category',
            'nationality', 'religion', 'student_type', 'marital_status', 'status', 'guardian_relation',
        ];
        $dateColumns = ['admission_date', 'submitted_date', 'dob', 'scholarship_applied_date'];
        $numberColumns = ['form_no', 'current_semester'];

        foreach ($columns as $key => $column) {
            if (isset($column['fee_summary'], $column['payment_mode']) || str_starts_with($key, 'fee_')) {
                $filters[$key] = ['type' => 'amount', 'label' => $column['label']];
                continue;
            }

            if ($key === 'course_name') {
                $filters[$key] = [
                    'type' => 'multi',
                    'label' => $column['label'],
                    'options' => $courses->map(fn($course) => ['value' => (string) $course->id, 'label' => $course->name])->values()->all(),
                ];
                continue;
            }

            if ($key === 'stream_name') {
                $courseIds = $courses->pluck('id')->all();
                $filters[$key] = [
                    'type' => 'multi',
                    'label' => $column['label'],
                    'options' => CourseStream::whereIn('course_id', $courseIds)
                        ->where('status', true)
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->map(fn($stream) => ['value' => (string) $stream->id, 'label' => $stream->name])
                        ->values()
                        ->all(),
                ];
                continue;
            }

            if ($key === 'academic_session') {
                $filters[$key] = [
                    'type' => 'multi',
                    'label' => $column['label'],
                    'options' => AcademicSession::where('institute_id', $instituteId)
                        ->orderByDesc('id')
                        ->get(['id', 'name'])
                        ->map(fn($session) => ['value' => (string) $session->id, 'label' => $session->name])
                        ->values()
                        ->all(),
                ];
                continue;
            }

            if ($key === 'gap_year' || ($column['label'] ?? '') === 'Gap Year') {
                $filters[$key] = [
                    'type' => 'multi',
                    'label' => $column['label'],
                    'options' => [
                        ['value' => '1', 'label' => 'Yes'],
                        ['value' => '0', 'label' => 'No'],
                    ],
                ];
                continue;
            }

            if (in_array($key, $studentOptionColumns, true)) {
                $filters[$key] = [
                    'type' => 'multi',
                    'label' => $column['label'],
                    'options' => $this->customReportDistinctStudentOptions($instituteId, $sessionId, $key),
                ];
                continue;
            }

            if (in_array($key, $dateColumns, true) || str_ends_with($key, '_date')) {
                $filters[$key] = ['type' => 'date', 'label' => $column['label']];
                continue;
            }

            if (in_array($key, $numberColumns, true)) {
                $filters[$key] = ['type' => 'number', 'label' => $column['label']];
                continue;
            }

            $filters[$key] = ['type' => 'text', 'label' => $column['label']];
        }

        return $filters;
    }

    private function customReportDistinctStudentOptions(int $instituteId, ?int $sessionId, string $column): array
    {
        return Student::where('institute_id', $instituteId)
            ->when($sessionId, fn($query) => $query->where('academic_session_id', $sessionId))
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn($value) => ['value' => (string) $value, 'label' => ucwords(str_replace('_', ' ', (string) $value))])
            ->values()
            ->all();
    }

    private function applyCustomReportQueryFilters($query, Request $request, array $columns)
    {
        foreach ((array) $request->input('column_filters', []) as $key => $filter) {
            if (!isset($columns[$key]) || !$this->customReportFilterIsActive((array) $filter)) {
                continue;
            }

            if (!$this->customReportCanQueryFilter($key, $columns)) {
                continue;
            }

            $filter = (array) $filter;
            $values = array_values(array_filter((array) ($filter['values'] ?? []), fn($value) => $value !== ''));

            if ($key === 'course_name' && $values) {
                $query->whereHas('stream', fn($streamQuery) => $streamQuery->whereIn('course_id', $values));
                continue;
            }

            if ($key === 'stream_name' && $values) {
                $query->whereIn('course_stream_id', $values);
                continue;
            }

            if ($key === 'academic_session' && $values) {
                $query->whereIn('academic_session_id', $values);
                continue;
            }

            if ($key === 'subjects') {
                $value = trim((string) ($filter['value'] ?? ''));
                if ($value !== '') {
                    $query->whereHas('studentSubjects.subject', fn($subjectQuery) => $subjectQuery->where('name', 'like', "%{$value}%"));
                }
                continue;
            }

            if (isset($columns[$key]['exam'])) {
                $value = trim((string) ($filter['value'] ?? ''));
                if ($value !== '') {
                    $query->whereHas('educationDetails', function ($educationQuery) use ($columns, $key, $value) {
                        $educationQuery->where('exam_name', 'like', '%' . $columns[$key]['exam'] . '%')
                            ->where($columns[$key]['attribute'], 'like', "%{$value}%");
                    });
                }
                continue;
            }

            if ($values) {
                $query->whereIn($key === 'form_no' ? 'id' : $key, $values);
                continue;
            }

            if (($filter['from'] ?? '') !== '' || ($filter['to'] ?? '') !== '') {
                $column = $key === 'form_no' ? 'id' : $key;
                if (($filter['from'] ?? '') !== '') {
                    $query->where($column, '>=', $filter['from']);
                }
                if (($filter['to'] ?? '') !== '') {
                    $query->where($column, '<=', $filter['to']);
                }
                continue;
            }

            $value = trim((string) ($filter['value'] ?? ''));
            if ($value !== '') {
                $query->where($key === 'form_no' ? 'id' : $key, 'like', "%{$value}%");
            }
        }

        return $query;
    }

    private function customReportUsesPostFilters(Request $request, array $columns): bool
    {
        foreach ((array) $request->input('column_filters', []) as $key => $filter) {
            if (isset($columns[$key]) && !$this->customReportCanQueryFilter($key, $columns) && $this->customReportFilterIsActive((array) $filter)) {
                return true;
            }
        }

        return false;
    }

    private function customReportFilteredStudents($query, Request $request, array $columns)
    {
        $students = $query->orderBy('name')->get();

        foreach ((array) $request->input('column_filters', []) as $key => $filter) {
            if (!isset($columns[$key]) || $this->customReportCanQueryFilter($key, $columns) || !$this->customReportFilterIsActive((array) $filter)) {
                continue;
            }

            $filter = (array) $filter;
            $students = $students->filter(function ($student) use ($key, $filter, $columns) {
                $rawValue = $this->customReportValue($student, $key, $columns[$key]);

                if (($filter['from'] ?? '') !== '' || ($filter['to'] ?? '') !== '') {
                    $value = (float) $rawValue;
                    if (($filter['from'] ?? '') !== '' && $value < (float) $filter['from']) {
                        return false;
                    }
                    if (($filter['to'] ?? '') !== '' && $value > (float) $filter['to']) {
                        return false;
                    }
                }

                $values = array_values(array_filter((array) ($filter['values'] ?? []), fn($value) => $value !== ''));
                if ($values && !in_array((string) $rawValue, $values, true)) {
                    return false;
                }

                $needle = trim((string) ($filter['value'] ?? ''));
                if ($needle !== '' && !str_contains(strtolower((string) $rawValue), strtolower($needle))) {
                    return false;
                }

                return true;
            })->values();
        }

        return $students;
    }

    private function customReportFilterIsActive(array $filter): bool
    {
        if (array_values(array_filter((array) ($filter['values'] ?? []), fn($value) => $value !== ''))) {
            return true;
        }

        foreach (['value', 'from', 'to'] as $key) {
            if (isset($filter[$key]) && trim((string) $filter[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    private function customReportCanQueryFilter(string $key, array $columns): bool
    {
        if (str_starts_with($key, 'fee_') || $key === 'year_label') {
            return false;
        }

        if (in_array($key, ['course_name', 'stream_name', 'academic_session', 'subjects'], true) || isset($columns[$key]['exam'])) {
            return true;
        }

        return $key === 'form_no' || in_array($key, (new Student())->getFillable(), true);
    }

    private function customReportColumns(int $instituteId): array
    {
        $sectionColumns = [];
        $activeConfig = AdmissionFormController::getActiveConfig($instituteId, 'admission');

        foreach (AdmissionFormController::getSections('admission') as $sectionKey => $section) {
            foreach ($section['fields'] as $field) {
                $fieldConfig = $activeConfig[$field['key']] ?? null;
                $isVisible = ($fieldConfig['section_enabled'] ?? ($section['section_enabled'] ?? true))
                    && ($fieldConfig['enabled'] ?? $field['enabled']);

                if (!$isVisible) {
                    continue;
                }

                if (str_starts_with($field['key'], 'edu_')) {
                    $sectionColumns[$sectionKey] = ($sectionColumns[$sectionKey] ?? []) + $this->educationColumns($field['key'], $field['label']);
                    continue;
                }

                $sectionColumns[$sectionKey][$field['key']] = ['label' => $field['label'], 'section' => $section['label']];
            }
        }

        $courseColumns = [
            'course_name' => ['label' => 'Course Name', 'section' => 'Course Details'],
            'stream_name' => ['label' => 'Class / Stream', 'section' => 'Course Details'],
            'year_label' => ['label' => 'Year / Semester', 'section' => 'Course Details'],
            'current_semester' => ['label' => 'Current Semester', 'section' => 'Course Details'],
            'subjects' => ['label' => 'Subjects', 'section' => 'Course Details'],
        ];

        $feeColumns = [
            'fee_total_chargeable' => ['label' => 'Total Chargeable', 'section' => 'Fee Summary', 'fee_summary' => 'total_charged'],
            'fee_total_paid' => ['label' => 'Total Paid', 'section' => 'Fee Summary', 'fee_summary' => 'total_paid'],
            'fee_paid_cash' => ['label' => 'Paid Cash', 'section' => 'Fee Summary', 'payment_mode' => 'cash'],
            'fee_paid_upi' => ['label' => 'Paid UPI', 'section' => 'Fee Summary', 'payment_mode' => 'upi'],
            'fee_paid_online' => ['label' => 'Paid Online', 'section' => 'Fee Summary', 'payment_mode' => 'online'],
            'fee_paid_cheque' => ['label' => 'Paid Cheque', 'section' => 'Fee Summary', 'payment_mode' => 'cheque'],
            'fee_paid_dd' => ['label' => 'Paid DD', 'section' => 'Fee Summary', 'payment_mode' => 'dd'],
            'fee_paid_neft' => ['label' => 'Paid NEFT', 'section' => 'Fee Summary', 'payment_mode' => 'neft'],
            'fee_paid_rtgs' => ['label' => 'Paid RTGS', 'section' => 'Fee Summary', 'payment_mode' => 'rtgs'],
            'fee_total_fine' => ['label' => 'Total Fine', 'section' => 'Fee Summary', 'fee_summary' => 'total_fine'],
            'fee_total_discount' => ['label' => 'Total Discount', 'section' => 'Fee Summary', 'fee_summary' => 'total_discount'],
            'fee_total_due' => ['label' => 'Total Due', 'section' => 'Fee Summary', 'fee_summary' => 'total_due'],
        ];

        return array_merge(
            $sectionColumns['office'] ?? [],
            $courseColumns,
            $sectionColumns['personal'] ?? [],
            $feeColumns,
            $sectionColumns['address'] ?? [],
            $sectionColumns['education'] ?? []
        );
    }

    private function educationColumns(string $fieldKey, string $fieldLabel): array
    {
        $exam = match ($fieldKey) {
            'edu_10th' => '10',
            'edu_12th' => '12',
            'edu_graduation' => 'GRADUATION',
            default => 'OTHER',
        };
        $suffix = str_replace(' Details', '', $fieldLabel);

        return [
            "{$fieldKey}_exam_name" => ['label' => "Exam Name {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'exam_name'],
            "{$fieldKey}_passing_year" => ['label' => "Passing Year {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'passing_year'],
            "{$fieldKey}_roll_number" => ['label' => "Roll Number {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'roll_number'],
            "{$fieldKey}_education_stream" => ['label' => "STREAM {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'education_stream'],
            "{$fieldKey}_institute_name" => ['label' => "Institute Name {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'institute_name'],
            "{$fieldKey}_board_university" => ['label' => "Board / University {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'board_university'],
            "{$fieldKey}_max_marks" => ['label' => "Total Max. Marks {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'max_marks'],
            "{$fieldKey}_obtained_marks" => ['label' => "Total Marks Obt. {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'obtained_marks'],
            "{$fieldKey}_percentage" => ['label' => "Percentage {$suffix}", 'section' => 'Passed Exam Details', 'exam' => $exam, 'attribute' => 'percentage'],
        ];
    }

    private function customReportRows($students, array $selectedColumns, array $columns): array
    {
        return $students->map(fn($student) => array_map(
            fn($key) => $this->customReportValue($student, $key, $columns[$key]),
            $selectedColumns
        ))->toArray();
    }

    public function customReportValue(Student $student, string $key, array $column): string
    {
        if (isset($column['exam'], $column['attribute'])) {
            $education = $student->educationDetails->first(fn($row) => str_contains(strtoupper((string) $row->exam_name), $column['exam']));
            $value = (string) ($education?->{$column['attribute']} ?? '');
            return $column['attribute'] === 'education_stream' ? strtoupper($value) : $value;
        }

        if (isset($column['fee_summary'])) {
            return $this->formatReportAmount($this->customReportFeeSummary($student)[$column['fee_summary']] ?? 0);
        }

        if (isset($column['payment_mode'])) {
            return $this->formatReportAmount($this->customReportFeeSummary($student)['paid_by_mode'][$column['payment_mode']] ?? 0);
        }

        $value = match ($key) {
            'form_no' => $student->id,
            'academic_session' => $student->session?->name,
            'course_name' => $student->stream?->course?->name,
            'stream_name' => $student->stream?->name,
            'year_label' => AcademicState::yearLabel($student->stream?->course?->structure_type, $student->current_semester, $student->coursePart?->year_number, $student->stream?->course?->effectiveSemestersPerYear() ?? 2),
            'subjects' => $student->studentSubjects->map(fn($row) => $row->subject?->name)->filter()->unique()->implode(', '),
            'photo' => $student->photo ? Storage::url($student->photo) : '',
            default => $student->{$key} ?? '',
        };

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('d-M-Y');
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    private function customReportFeeSummary(Student $student): array
    {
        $sessionId = (int) ($student->academic_session_id ?: ($student->session?->id ?? 0));
        $cacheKey = $student->id . ':' . $sessionId;

        if (isset($this->customReportFeeSummaryCache[$cacheKey])) {
            return $this->customReportFeeSummaryCache[$cacheKey];
        }

        $summary = WalletService::getStudentSummary($student, $sessionId);
        $paidByMode = FeeInvoice::query()
            ->active()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->selectRaw("LOWER(COALESCE(payment_mode, '')) as mode, COALESCE(SUM(paid_amount), 0) as amount")
            ->groupByRaw("LOWER(COALESCE(payment_mode, ''))")
            ->pluck('amount', 'mode')
            ->map(fn($amount) => (float) $amount)
            ->toArray();

        return $this->customReportFeeSummaryCache[$cacheKey] = [
            'total_charged' => (float) ($summary['total_charged'] ?? 0),
            'total_paid' => (float) ($summary['total_paid'] ?? 0),
            'total_fine' => (float) ($summary['total_fine'] ?? 0),
            'total_discount' => (float) ($summary['total_discount'] ?? 0),
            'total_due' => (float) ($summary['total_due'] ?? 0),
            'paid_by_mode' => $paidByMode,
        ];
    }

    private function formatReportAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    private function exportExcelTable(array $selectedColumns, array $columns, array $rows, string $filename, ?string $sessionName)
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->exportCsv(
                array_map(fn($key) => $columns[$key]['label'], $selectedColumns),
                $rows,
                str_replace('.xlsx', '.csv', $filename)
            );
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'custom-report-');
        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheetXml($selectedColumns, $columns, $rows, $sessionName));
        $zip->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    private function xlsxSheetXml(array $selectedColumns, array $columns, array $rows, ?string $sessionName): string
    {
        $headers = array_map(fn($key) => $columns[$key]['label'], $selectedColumns);
        $sheetRows = [
            ['Custom Student Report' . ($sessionName ? ' - ' . $sessionName : '')],
            $headers,
            ...$rows,
        ];

        $xmlRows = [];
        foreach ($sheetRows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $cellRef = $this->xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $style = $rowIndex <= 1 ? ' s="1"' : '';
                $cells[] = '<c r="' . $cellRef . '" t="inlineStr"' . $style . '><is><t>' . $this->xlsxEscape((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xlsxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Custom Report" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private function xlsxCoreXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Custom Student Report</dc:title><dc:creator>College ERP</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . now()->toIso8601String() . '</dcterms:created>'
            . '</cp:coreProperties>';
    }

    private function xlsxAppXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>College ERP</Application></Properties>';
    }

    public function getStreams(Request $request)
    {
        $this->authorizeReportAccess('admission');
        if ($this->currentStaff() && !$this->currentStaff()->canAccessCourse((int) $request->course_id)) {
            return response()->json([]);
        }

        $streams = CourseStream::where('course_id', $request->course_id)
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($streams);
    }

    // ════════════════════════════════════════════════════════════════════
    //  DAILY / MONTHLY COLLECTION REPORT
    // ════════════════════════════════════════════════════════════════════
    public function dailyReport(Request $request)
    {
        $this->authorizeReportAccess('fee');
        $this->ensureReportExportPermission($request);
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId  = $request->session_id ?? $activeSession?->id;
        $sessions   = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $sessionObj = AcademicSession::find($sessionId);

        // Default: today (same day)
        $dateFrom = $request->date_from ?? now()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();
        $groupBy  = $request->group_by  ?? 'day'; // day | month

        // Clamp monthly range to max 3 months
        if ($groupBy === 'month') {
            $fromCarbon = \Carbon\Carbon::parse($dateFrom);
            $toCarbon   = \Carbon\Carbon::parse($dateTo);
            if ($fromCarbon->diffInMonths($toCarbon) > 3) {
                $dateTo = $fromCarbon->copy()->addMonths(3)->endOfMonth()->toDateString();
            }
        }

        // Base query
        $baseQ = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);
        $this->applyStaffFeeScope($baseQ);

        // Day-wise ya month-wise group
        if ($groupBy === 'month') {
            $grouped = (clone $baseQ)
                ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as period,
                             DATE_FORMAT(payment_date, '%b %Y') as label,
                             SUM(paid_amount) as collected,
                             SUM(COALESCE(discount,0)) as discount,
                             COUNT(*) as invoices,
                             COUNT(DISTINCT student_id) as students")
                ->groupBy('period', 'label')
                ->orderBy('period')
                ->get();
        } else {
            $grouped = (clone $baseQ)
                ->selectRaw("DATE(payment_date) as period,
                             DATE_FORMAT(payment_date, '%d %b %Y') as label,
                             SUM(paid_amount) as collected,
                             SUM(COALESCE(discount,0)) as discount,
                             COUNT(*) as invoices,
                             COUNT(DISTINCT student_id) as students")
                ->groupBy('period', 'label')
                ->orderBy('period')
                ->get();
        }

        // Mode-wise summary for period
        $modeWise = (clone $baseQ)
            ->selectRaw('payment_mode, SUM(paid_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_mode')
            ->orderByDesc('total')
            ->get();

        // Mode → Bank breakdown for popup
        $modeBankWise = (clone $baseQ)
            ->selectRaw("payment_mode, COALESCE(bank_name, '') as bank_label, COALESCE(collected_by, '\u2014') as collector, COUNT(*) as cnt, SUM(paid_amount) as total")
            ->groupBy('payment_mode', 'bank_label', 'collector')
            ->orderBy('payment_mode')->orderByDesc('total')
            ->get()
            ->groupBy('payment_mode');

        $totalCollected = (float) (clone $baseQ)->sum('paid_amount');
        $totalDiscount  = (float) (clone $baseQ)->sum('discount');
        $totalInvoices  = (clone $baseQ)->count();
        $totalStudents  = (clone $baseQ)->distinct('student_id')->count('student_id');

        // Fine per period (from StudentTransaction)
        $finePeriodFormat = $groupBy === 'month' ? "DATE_FORMAT(date, '%Y-%m')" : "DATE(date)";
        $fineGrouped = \App\Models\StudentTransaction::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->where('des', 'like', 'Fine charged:%')
            ->selectRaw("{$finePeriodFormat} as period, SUM(debit) as fine_total")
            ->groupBy('period')
            ->pluck('fine_total', 'period');
        $totalFine = (float) $fineGrouped->sum();

        // Per-period invoice detail for date-row popup
        $periodInvoices = FeeInvoice::with('student:id,name,student_uid')
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->select(['id', 'invoice_no', 'student_id', 'payment_date', 'payment_mode',
                      'paid_amount', 'discount', 'collected_by', 'bank_name', 'transaction_ref'])
            ->orderBy('payment_date');
        $this->applyStaffFeeScope($periodInvoices);
        $periodInvoices = $periodInvoices->get()
            ->groupBy(fn($inv) => $groupBy === 'month'
                ? $inv->payment_date->format('Y-m')
                : $inv->payment_date->format('Y-m-d'))
            ->map(fn($rows) => $rows->map(fn($inv) => [
                'no'      => $inv->invoice_no,
                'student' => $inv->student->name ?? '—',
                'uid'     => $inv->student->student_uid ?? '',
                'mode'    => $inv->payment_mode,
                'amount'  => (float) $inv->paid_amount,
                'disc'    => (float) ($inv->discount ?? 0),
                'bank'    => $inv->bank_name ?: '',
                'ref'     => $inv->transaction_ref ?: '',
                'by'      => $inv->collected_by ?: '',
            ])->values()->toArray());

        // Export CSV
        if ($request->export === 'csv') {
            return $this->exportCsv(
                ['Date', 'Collection (₹)', 'Discount (₹)', 'Fine (₹)', 'Payable (₹)', 'Invoices', 'Students'],
                $grouped->map(fn($r) => [
                    $r->label,
                    number_format($r->collected, 2),
                    number_format($r->discount, 2),
                    number_format($fineGrouped[$r->period] ?? 0, 2),
                    number_format($r->collected + $r->discount, 2),
                    $r->invoices,
                    $r->students,
                ])->toArray(),
                'daily-collection-report.csv'
            );
        }

        return view('institute.reports.daily-report', compact(
            'sessions', 'sessionObj', 'sessionId', 'activeSession',
            'grouped', 'modeWise', 'modeBankWise', 'dateFrom', 'dateTo', 'groupBy',
            'totalCollected', 'totalDiscount', 'totalInvoices', 'totalStudents',
            'totalFine', 'fineGrouped', 'periodInvoices'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  SEMESTER-WISE COLLECTION REPORT
    // ════════════════════════════════════════════════════════════════════
    public function semesterReport(Request $request)
    {
        $this->authorizeReportAccess('fee');
        $this->ensureReportExportPermission($request);
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $sessionId  = $request->session_id ?? $activeSession?->id;
        $sessions   = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courses    = Course::where('institute_id', $instituteId)->where('status', true)
            ->when($this->currentStaff()?->hasRestrictedCourseAccess(), fn($q) => $q->whereIn('id', $this->currentStaff()->allowedCourseIds() ?: [-1]))
            ->orderBy('name')->get();
        $sessionObj = AcademicSession::find($sessionId);

        // Semester-wise collection
        $semWise = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->selectRaw('COALESCE(semester, 0) as semester,
                         SUM(paid_amount) as collected,
                         SUM(COALESCE(discount,0)) as discount,
                         COUNT(*) as invoices,
                         COUNT(DISTINCT student_id) as students')
            ->groupBy('semester')
            ->orderBy('semester');
        $this->applyStaffFeeScope($semWise);
        $semWise = $semWise->get();

        // Course + semester wise
        $courseWise = FeeInvoice::where('fee_invoices.institute_id', $instituteId)
            ->where('fee_invoices.is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('fee_invoices.academic_session_id', $sessionId))
            ->join('students', 'fee_invoices.student_id', '=', 'students.id')
            ->join('course_streams', 'students.course_stream_id', '=', 'course_streams.id')
            ->join('courses', 'course_streams.course_id', '=', 'courses.id')
            ->selectRaw('courses.name as course_name,
                         COALESCE(fee_invoices.semester, 0) as semester,
                         SUM(fee_invoices.paid_amount) as collected,
                         COUNT(DISTINCT fee_invoices.student_id) as students')
            ->groupBy('course_name', 'semester')
            ->orderBy('course_name')
            ->orderBy('semester');
        $this->applyStaffFeeScope($courseWise);
        $courseWise = $courseWise->get();

        $totalCollected = (float) FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('paid_amount');

        // Fine per semester (from StudentTransaction, matched via invoice semester)
        // We join student_transactions with fee_invoices via student_id + session to get semester-wise fine
        // Simpler: query StudentTransaction grouped by student → then map to semester via invoice
        // Best approach: fine is charged against a student/session; semester tag from invoice
        // Use the invoice's semester for students who got fine in this session
        $finesBySemester = \App\Models\StudentTransaction::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('des', 'like', 'Fine charged:%')
            ->selectRaw('student_id, SUM(debit) as fine_total')
            ->groupBy('student_id')
            ->pluck('fine_total', 'student_id');

        // Map student → semester from invoice
        $studentSemester = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereIn('student_id', $finesBySemester->keys())
            ->selectRaw('student_id, COALESCE(MAX(semester), 0) as semester')
            ->groupBy('student_id')
            ->pluck('semester', 'student_id');

        $fineGrouped = collect();
        foreach ($finesBySemester as $studentId => $fine) {
            $sem = $studentSemester[$studentId] ?? 0;
            $fineGrouped[$sem] = ($fineGrouped[$sem] ?? 0) + $fine;
        }
        $totalFine = (float) $fineGrouped->sum();

        // Export CSV
        if ($request->export === 'csv') {
            return $this->exportCsv(
                ['Semester', 'Collection (₹)', 'Discount (₹)', 'Fine (₹)', 'Invoices', 'Students'],
                $semWise->map(fn($r) => [
                    $r->semester ? 'Semester ' . $r->semester : 'Untagged',
                    number_format($r->collected, 2),
                    number_format($r->discount, 2),
                    number_format($fineGrouped[$r->semester] ?? 0, 2),
                    $r->invoices,
                    $r->students,
                ])->toArray(),
                'semester-wise-report.csv'
            );
        }

        return view('institute.reports.semester-report', compact(
            'sessions', 'sessionObj', 'sessionId', 'activeSession',
            'courses', 'semWise', 'courseWise', 'totalCollected', 'fineGrouped', 'totalFine'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  EXPORT CSV — helper
    // ════════════════════════════════════════════════════════════════════
    private function exportFeeCollectionExcel(
        array $headers,
        array $rows,
        string $filename,
        string $instituteName,
        string $dateRange,
        string $session
    ): mixed {
        if (!class_exists(\ZipArchive::class)) {
            return $this->exportCsv($headers, $rows, str_replace('.xlsx', '.csv', $filename));
        }
        $tempPath = tempnam(sys_get_temp_dir(), 'fee-coll-');
        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxFeeCollectionStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxFeeCollectionSheetXml($headers, $rows, $instituteName, $dateRange, $session));
        $zip->close();
        return response()->download($tempPath, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    private function xlsxFeeCollectionStylesXml(): string
    {
        // Fonts: 0=normal, 1=bold, 2=title-large-blue, 3=bold-white (header row)
        $fonts = '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="13"/><name val="Calibri"/><color rgb="FF1D4ED8"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>';

        // Fills: 0+1 reserved, 2=dark-header, 3=even-row, 4=title-blue, 5=total-row
        $fills = '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
            . '</fills>';

        // Borders: 0=none, 1=thin all sides
        $borders = '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>';

        $cellStyleXfs = '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';

        // 0=normal, 1=title, 2=subtitle-bold, 3=header-cell,
        // 4=data-odd-left, 5=data-odd-right, 6=data-even-left, 7=data-even-right,
        // 8=total-left, 9=total-right
        $cellXfs = '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="2" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            . '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $fonts . $fills . $borders . $cellStyleXfs . $cellXfs
            . '</styleSheet>';
    }

    private function xlsxFeeCollectionSheetXml(
        array $headers,
        array $rows,
        string $instituteName,
        string $dateRange,
        string $session
    ): string {
        $colCount   = count($headers);
        $lastCol    = $this->xlsxColumnName($colCount);
        $amountCols = [18, 19, 20, 21]; // 0-based: Collection, Fine, Discount, Total
        $colWidths  = [18, 12, 10, 10, 10, 20, 14, 10, 12, 18, 18, 16, 14, 22, 18, 18, 16, 12, 12, 10, 10, 12];

        // Compute column totals for amount columns
        $totals = array_fill(0, $colCount, null);
        foreach ($amountCols as $ci) {
            $totals[$ci] = 0.0;
            foreach ($rows as $row) {
                $totals[$ci] += (float) str_replace(',', '', $row[$ci] ?? '0');
            }
        }

        $xmlRows = [];

        // Row 1: Title (style 1 = large bold blue, light-blue fill)
        $xmlRows[] = '<row r="1" ht="24" customHeight="1"><c r="A1" t="inlineStr" s="1"><is><t>'
            . $this->xlsxEscape($instituteName . ' — Fee Collection Report')
            . '</t></is></c></row>';

        // Row 2: Subtitle (style 2 = bold)
        $subtitle = 'Session: ' . $session
            . '   |   Date Range: ' . $dateRange
            . '   |   Generated: ' . now()->setTimezone('Asia/Kolkata')->format('d M Y h:i A');
        $xmlRows[] = '<row r="2" ht="16" customHeight="1"><c r="A2" t="inlineStr" s="2"><is><t>'
            . $this->xlsxEscape($subtitle)
            . '</t></is></c></row>';

        // Row 3: Column headers (style 3 = bold white on dark bg)
        $cells = [];
        foreach ($headers as $ci => $h) {
            $ref     = $this->xlsxColumnName($ci + 1) . '3';
            $cells[] = '<c r="' . $ref . '" t="inlineStr" s="3"><is><t>' . $this->xlsxEscape($h) . '</t></is></c>';
        }
        $xmlRows[] = '<row r="3" ht="20" customHeight="1">' . implode('', $cells) . '</row>';

        // Data rows starting at row 4, alternating fills
        foreach ($rows as $rowIdx => $row) {
            $ri     = $rowIdx + 4;
            $isEven = ($rowIdx % 2 === 1);
            $cells  = [];
            foreach (array_values((array) $row) as $ci => $value) {
                $ref     = $this->xlsxColumnName($ci + 1) . $ri;
                $isAmt   = in_array($ci, $amountCols);
                $style   = $isEven ? ($isAmt ? 7 : 6) : ($isAmt ? 5 : 4);
                $cells[] = '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>'
                    . $this->xlsxEscape((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . $ri . '">' . implode('', $cells) . '</row>';
        }

        // Total row
        $totRow = count($rows) + 4;
        $cells  = [];
        foreach ($totals as $ci => $val) {
            $ref     = $this->xlsxColumnName($ci + 1) . $totRow;
            $isAmt   = in_array($ci, $amountCols);
            $style   = $isAmt ? 9 : 8;
            if ($ci === 0) {
                $text = 'TOTAL (' . count($rows) . ' records)';
            } elseif ($val !== null) {
                $text = number_format($val, 2);
            } else {
                $text = '';
            }
            $cells[] = '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>'
                . $this->xlsxEscape($text) . '</t></is></c>';
        }
        $xmlRows[] = '<row r="' . $totRow . '" ht="18" customHeight="1">' . implode('', $cells) . '</row>';

        // Column widths
        $colsXml = '<cols>';
        foreach ($colWidths as $i => $w) {
            $n        = $i + 1;
            $colsXml .= '<col min="' . $n . '" max="' . $n . '" width="' . $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        // Merge title and subtitle rows across all columns
        $mergeCells = '<mergeCells count="2">'
            . '<mergeCell ref="A1:' . $lastCol . '1"/>'
            . '<mergeCell ref="A2:' . $lastCol . '2"/>'
            . '</mergeCells>';

        // Freeze top 3 rows so headers stay visible while scrolling
        $sheetViews = '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . $sheetViews . $colsXml
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . $mergeCells
            . '</worksheet>';
    }

    private function exportSimpleExcel(string $title, array $headers, array $rows, string $filename): mixed
    {
        if (!class_exists(\ZipArchive::class)) {
            return $this->exportCsv($headers, $rows, str_replace('.xlsx', '.csv', $filename));
        }
        $allRows  = [[$title], $headers, ...$rows];
        $tempPath = tempnam(sys_get_temp_dir(), 'report-');
        $zip = new \ZipArchive();
        $zip->open($tempPath, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('docProps/app.xml', $this->xlsxAppXml());
        $zip->addFromString('docProps/core.xml', $this->xlsxCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSimpleSheetXml($allRows));
        $zip->close();
        return response()->download($tempPath, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    private function xlsxSimpleSheetXml(array $allRows): string
    {
        $xmlRows = [];
        foreach ($allRows as $ri => $row) {
            $cells = [];
            foreach (array_values((array) $row) as $ci => $value) {
                $ref   = $this->xlsxColumnName($ci + 1) . ($ri + 1);
                $style = $ri <= 1 ? ' s="1"' : '';
                $cells[] = '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $this->xlsxEscape((string) $value) . '</t></is></c>';
            }
            $xmlRows[] = '<row r="' . ($ri + 1) . '">' . implode('', $cells) . '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>';
    }

    private function exportCsv(array $headers, array $rows, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  FEE DUE LIST — CSV export (add to feeDueList)
    // ════════════════════════════════════════════════════════════════════
    // ════════════════════════════════════════════════════════════════════
    //  ADMISSION SUB-REPORTS
    // ════════════════════════════════════════════════════════════════════

    private function admissionSubReport(Request $request, string $type)
    {
        $this->authorizeReportAccess('admission');
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes   = CourseType::where('institute_id', $instituteId)->orderBy('name')->get();
        $courses       = Course::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $streams       = CourseStream::where('status', true)
                            ->whereHas('course', fn($q) => $q->where('institute_id', $instituteId))
                            ->with('course')
                            ->orderBy('name')
                            ->get();
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $staffList     = StaffMember::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $sessionObj    = AcademicSession::find($sessionId);

        $query = Student::with(['stream.course', 'coursePart', 'admittedBy', 'session'])
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId));

        match ($type) {
            'full-form'       => $query->where('is_quick_admission', false),
            'online'          => $query->where('admission_source', 'online'),
            'centre'          => $query->where('admission_source', 'center')
                                       ->when($request->center_id, fn($q) => $q->where('admission_source_id', $request->center_id)),
            'channel-partner' => $query->whereIn('admission_source', ['partner', 'channel_partner'])
                                       ->when($request->partner_id, fn($q) => $q->where('admission_source_id', $request->partner_id)),
            'staff'           => $query->whereNotNull('admitted_by_staff_id')
                                       ->when($request->staff_id, fn($q) => $q->where('admitted_by_staff_id', $request->staff_id)),
            'blocked'         => $query->whereIn('status', ['inactive', 'detained', 'cancelled', 'transferred']),
            default           => null,
        };

        if ($request->filled('course_type_id')) {
            $query->whereHas('stream.course', fn($q) => $q->where('course_type_id', $request->course_type_id));
        }
        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', $request->course_id));
        }
        if ($request->filled('stream_id')) {
            $query->where('stream_id', $request->stream_id);
        }
        if ($request->filled('semester')) {
            $query->where('current_semester', (int) $request->semester);
        }
        if ($request->filled('status') && $type !== 'blocked') {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%")
                ->orWhere('father_name', 'like', "%{$s}%")
                ->orWhere('mother_name', 'like', "%{$s}%")
                ->orWhere('student_uid', 'like', "%{$s}%"));
        }

        $perPage = in_array((int) $request->per_page, [10, 20, 50, 100]) ? (int) $request->per_page : 20;
        $total   = (clone $query)->count();

        $subTitles = [
            'full-form'       => 'Full Form Admissions',
            'online'          => 'Online Admissions',
            'centre'          => 'Centre Admissions',
            'channel-partner' => 'Channel Partner Admissions',
            'staff'           => 'Staff Admissions',
            'blocked'         => 'Blocked Students',
        ];
        $pageTitle = $subTitles[$type] ?? 'Admissions';

        // Export
        if (in_array($request->export, ['csv', 'excel', 'pdf'])) {
            $allStudents = $query->orderByDesc('admission_date')->get();

            $allCenterIds  = $allStudents->where('admission_source', 'center')->pluck('admission_source_id')->filter()->unique();
            $allPartnerIds = $allStudents->whereIn('admission_source', ['partner', 'channel_partner'])->pluck('admission_source_id')->filter()->unique();
            $cMapExp = $allCenterIds->isNotEmpty()  ? Center::whereIn('id', $allCenterIds)->pluck('name', 'id')  : collect();
            $pMapExp = $allPartnerIds->isNotEmpty() ? ChannelPartner::whereIn('id', $allPartnerIds)->pluck('name', 'id') : collect();

            if ($request->export === 'pdf') {
                $institute   = Institute::find($instituteId);
                $centersMap  = $cMapExp;
                $partnersMap = $pMapExp;
                return view('institute.reports.admission-sub-report-export-pdf', compact(
                    'allStudents', 'institute', 'sessionObj', 'centersMap', 'partnersMap', 'type', 'pageTitle'
                ));
            }

            $expHeaders = ['#', 'Session', 'Student ID', 'Student Name', 'Father Name', 'Mother Name',
                           'Roll No', 'Enroll No', 'UIN No', 'Course', 'Stream', 'Year/Sem',
                           'Gender', 'Category', 'Admitted By', 'Adm. Date', 'Status'];
            if ($type === 'blocked') $expHeaders[] = 'Reason';

            $expRows = $allStudents->map(function ($s, $i) use ($type, $cMapExp, $pMapExp) {
                $pdfSrc = $s->admission_source ?? 'direct';
                $admittedBy = $s->admittedBy?->name ?? match($pdfSrc) {
                    'center'                     => $cMapExp[$s->admission_source_id] ?? 'Center',
                    'partner', 'channel_partner' => $pMapExp[$s->admission_source_id] ?? 'Partner',
                    default                      => 'Admin / Direct',
                };
                $row = [
                    $i + 1,
                    $s->session?->name ?? '',
                    $s->student_uid ?? '',
                    $s->name,
                    $s->father_name ?? '',
                    $s->mother_name ?? '',
                    $s->roll_no ?? '',
                    $s->enrollment_no ?? '',
                    $s->uin_no ?? '',
                    $s->stream?->course?->name ?? '',
                    $s->stream?->name ?? '',
                    $s->coursePart?->year_label ?? '',
                    ucfirst($s->gender ?? ''),
                    strtoupper($s->category ?? ''),
                    $admittedBy,
                    $s->admission_date?->format('d/m/Y') ?? '',
                    ucfirst($s->status ?? ''),
                ];
                if ($type === 'blocked') $row[] = $s->status_reason ?? '';
                return $row;
            })->toArray();

            $fileName = $type . '-admissions';
            if ($request->export === 'excel') {
                return $this->exportSimpleExcel($pageTitle . ' — ' . ($sessionObj?->name ?? ''), $expHeaders, $expRows, $fileName . '.xlsx');
            }
            return $this->exportCsv($expHeaders, $expRows, $fileName . '.csv');
        }

        $students = $query->orderByDesc('admission_date')->paginate($perPage)->withQueryString();

        // N+1-safe center/partner name maps for current page
        $centerIds  = $students->where('admission_source', 'center')->pluck('admission_source_id')->filter()->unique();
        $partnerIds = $students->whereIn('admission_source', ['partner', 'channel_partner'])->pluck('admission_source_id')->filter()->unique();
        $centersMap  = $centerIds->isNotEmpty()  ? Center::whereIn('id', $centerIds)->pluck('name', 'id')  : collect();
        $partnersMap = $partnerIds->isNotEmpty() ? ChannelPartner::whereIn('id', $partnerIds)->pluck('name', 'id') : collect();

        return view('institute.reports.admission-sub-report', compact(
            'students', 'type', 'total', 'sessions', 'courseTypes', 'courses', 'streams',
            'centers', 'partners', 'staffList', 'sessionId', 'sessionObj', 'perPage',
            'centersMap', 'partnersMap', 'pageTitle'
        ));
    }

    public function fullFormAdmissionReport(Request $request)       { return $this->admissionSubReport($request, 'full-form'); }
    public function onlineAdmissionReport(Request $request)         { return $this->admissionSubReport($request, 'online'); }
    public function centreAdmissionReport(Request $request)         { return $this->admissionSubReport($request, 'centre'); }
    public function channelPartnerAdmissionReport(Request $request) { return $this->admissionSubReport($request, 'channel-partner'); }
    public function staffAdmissionReport(Request $request)          { return $this->admissionSubReport($request, 'staff'); }
    public function blockedStudentsReport(Request $request)         { return $this->admissionSubReport($request, 'blocked'); }

    // ════════════════════════════════════════════════════════════════════
    //  FEE COLLECTION SUB-REPORTS
    // ════════════════════════════════════════════════════════════════════

    public function staffCollectionReport(Request $request): mixed
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        // Old records (before collected_by_staff_id column was added) only have
        // collected_by = staff name as text. Include both old and new style.
        $staffMembers = StaffMember::where('institute_id', $instituteId)->get();
        $staffNames   = $staffMembers->pluck('name');

        $invoices = FeeInvoice::with(['collectedByStaff', 'bankAccount', 'student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(fn($q) => $q
                ->whereNotNull('collected_by_staff_id')
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_staff_id')
                    ->whereIn('collected_by', $staffNames))
            )
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get();

        // Old-style records ke liye collected_by_staff_id aur relation resolve karo
        $invoices->each(function (FeeInvoice $invoice) use ($staffMembers) {
            if (!$invoice->collected_by_staff_id && $invoice->collected_by) {
                $resolved = $staffMembers->first(fn($s) => $s->name === $invoice->collected_by);
                if ($resolved) {
                    $invoice->collected_by_staff_id = $resolved->id;
                    $invoice->setRelation('collectedByStaff', $resolved);
                }
            }
        });

        $staffData = $invoices->groupBy('collected_by_staff_id')->map(fn($rows) => [
            'staff'  => $rows->first()->collectedByStaff,
            'count'  => $rows->count(),
            'total'  => (float) $rows->sum('paid_amount'),
            'cash'   => (float) $rows->where('payment_mode', 'cash')->sum('paid_amount'),
            'upi'    => (float) $rows->where('payment_mode', 'upi')->sum('paid_amount'),
            'online' => (float) $rows->where('payment_mode', 'online')->sum('paid_amount'),
            'cheque' => (float) $rows->where('payment_mode', 'cheque')->sum('paid_amount'),
            'dd'     => (float) $rows->where('payment_mode', 'dd')->sum('paid_amount'),
            'neft'   => (float) $rows->where('payment_mode', 'neft')->sum('paid_amount'),
            'rtgs'   => (float) $rows->where('payment_mode', 'rtgs')->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $grandTotal = (float) $invoices->sum('paid_amount');
        $grandCount = $invoices->count();

        // ── Bank-wise breakdown (non-cash payments carrying a bank account/name) ──
        $bankInvoices = $invoices->filter(fn($inv) => $inv->bank_account_id || $inv->bank_name);
        $bankGroups   = $bankInvoices->groupBy(fn($inv) => $inv->bank_account_id ? 'acct:' . $inv->bank_account_id : 'name:' . $inv->bank_name);

        $bankWise = $bankGroups->map(fn($rows) => [
            'label' => $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name,
            'count' => $rows->count(),
            'total' => (float) $rows->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $bankDetailWise = $bankGroups->mapWithKeys(function ($rows) {
            $label     = $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name;
            $staffRows = $rows->groupBy('collected_by_staff_id')->map(fn($srows) => [
                'staff' => $srows->first()->collectedByStaff?->name ?? 'Unknown Staff',
                'count' => $srows->count(),
                'total' => (float) $srows->sum('paid_amount'),
            ])->sortByDesc('total')->values();
            return [$label => $staffRows];
        });

        if ($request->filled('export')) {
            $eHeaders = ['#', 'Staff Name', 'Designation', 'Receipts', 'Cash (Rs)', 'UPI (Rs)', 'Online (Rs)', 'Cheque (Rs)', 'DD (Rs)', 'NEFT (Rs)', 'RTGS (Rs)', 'Total (Rs)'];
            $eRows    = $staffData->values()->map(fn($row, $i) => [
                $i + 1,
                $row['staff']?->name ?? 'Unknown',
                $row['staff']?->designation ?? '',
                $row['count'],
                number_format($row['cash'], 2),
                number_format($row['upi'], 2),
                number_format($row['online'], 2),
                number_format($row['cheque'], 2),
                number_format($row['dd'], 2),
                number_format($row['neft'], 2),
                number_format($row['rtgs'], 2),
                number_format($row['total'], 2),
            ])->toArray();

            // Append bank-wise / staff breakdown rows after the staff list
            $eRows[] = [];
            $eRows[] = ['Bank-wise Collection — Staff Breakdown'];
            $eRows[] = ['Bank / Account', 'Staff Name', 'Receipts', 'Amount (Rs)'];
            foreach ($bankWise as $bw) {
                $eRows[] = [$bw['label'], '', $bw['count'], number_format($bw['total'], 2)];
                foreach ($bankDetailWise[$bw['label']] ?? [] as $sr) {
                    $eRows[] = ['', $sr['staff'], $sr['count'], number_format($sr['total'], 2)];
                }
            }
            if ($bankWise->isNotEmpty()) {
                $eRows[] = ['Grand Total', '', $bankWise->sum('count'), number_format($bankWise->sum('total'), 2)];
            }

            $eTitle = 'Staff Collection Report | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($eHeaders, $eRows, 'staff-collection.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($eTitle, $eHeaders, $eRows, 'staff-collection.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.staff-collection-print', compact(
                    'instituteName', 'staffData', 'grandTotal', 'grandCount', 'dateFrom', 'dateTo', 'bankWise', 'bankDetailWise'
                ));
            }
        }

        return view('institute.reports.staff-collection', compact(
            'staffData', 'sessions', 'sessionId', 'dateFrom', 'dateTo', 'grandTotal', 'grandCount',
            'bankWise', 'bankDetailWise'
        ));
    }

    public function centreCollectionReport(Request $request): mixed
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $centers       = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        // Old records (before collected_by_center_id column was added) only have
        // collected_by = center name as text. Include both old and new style.
        $centerNames = $centers->pluck('name');

        $invoices = FeeInvoice::with(['collectedByCenter', 'bankAccount', 'student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(function ($q) use ($request, $centers, $centerNames) {
                if ($request->filled('center_id')) {
                    $centerName = $centers->find((int) $request->center_id)?->name ?? '';
                    $q->where('collected_by_center_id', $request->center_id)
                      ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                          ->where('collected_by', $centerName));
                } else {
                    $q->whereNotNull('collected_by_center_id')
                      ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                          ->whereIn('collected_by', $centerNames));
                }
            })
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get();

        // Old-style records ke liye collected_by_center_id aur relation resolve karo
        $invoices->each(function (FeeInvoice $invoice) use ($centers) {
            if (!$invoice->collected_by_center_id && $invoice->collected_by) {
                $resolved = $centers->first(fn($c) => $c->name === $invoice->collected_by);
                if ($resolved) {
                    $invoice->collected_by_center_id = $resolved->id;
                    $invoice->setRelation('collectedByCenter', $resolved);
                }
            }
        });

        $centreData = $invoices->groupBy('collected_by_center_id')->map(fn($rows) => [
            'center' => $rows->first()->collectedByCenter,
            'count'  => $rows->count(),
            'total'  => (float) $rows->sum('paid_amount'),
            'cash'   => (float) $rows->where('payment_mode', 'cash')->sum('paid_amount'),
            'upi'    => (float) $rows->where('payment_mode', 'upi')->sum('paid_amount'),
            'online' => (float) $rows->where('payment_mode', 'online')->sum('paid_amount'),
            'cheque' => (float) $rows->where('payment_mode', 'cheque')->sum('paid_amount'),
            'dd'     => (float) $rows->where('payment_mode', 'dd')->sum('paid_amount'),
            'neft'   => (float) $rows->where('payment_mode', 'neft')->sum('paid_amount'),
            'rtgs'   => (float) $rows->where('payment_mode', 'rtgs')->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $grandTotal = (float) $invoices->sum('paid_amount');
        $grandCount = $invoices->count();

        // ── Bank-wise breakdown (non-cash payments carrying a bank account/name) ──
        $bankInvoices = $invoices->filter(fn($inv) => $inv->bank_account_id || $inv->bank_name);
        $bankGroups   = $bankInvoices->groupBy(fn($inv) => $inv->bank_account_id ? 'acct:' . $inv->bank_account_id : 'name:' . $inv->bank_name);

        $bankWise = $bankGroups->map(fn($rows) => [
            'label' => $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name,
            'count' => $rows->count(),
            'total' => (float) $rows->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $bankDetailWise = $bankGroups->mapWithKeys(function ($rows) {
            $label      = $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name;
            $centreRows = $rows->groupBy('collected_by_center_id')->map(fn($crows) => [
                'name'  => $crows->first()->collectedByCenter?->name ?? 'Unknown Centre',
                'count' => $crows->count(),
                'total' => (float) $crows->sum('paid_amount'),
            ])->sortByDesc('total')->values();
            return [$label => $centreRows];
        });

        if ($request->filled('export')) {
            $eHeaders = ['#', 'Centre Name', 'Receipts', 'Cash (Rs)', 'UPI (Rs)', 'Online (Rs)', 'Cheque (Rs)', 'DD (Rs)', 'NEFT (Rs)', 'RTGS (Rs)', 'Total (Rs)'];
            $eRows    = $centreData->values()->map(fn($row, $i) => [
                $i + 1,
                $row['center']?->name ?? 'Unknown',
                $row['count'],
                number_format($row['cash'], 2),
                number_format($row['upi'], 2),
                number_format($row['online'], 2),
                number_format($row['cheque'], 2),
                number_format($row['dd'], 2),
                number_format($row['neft'], 2),
                number_format($row['rtgs'], 2),
                number_format($row['total'], 2),
            ])->toArray();

            // Append bank-wise / centre breakdown rows after the centre list
            $eRows[] = [];
            $eRows[] = ['Bank-wise Collection — Centre Breakdown'];
            $eRows[] = ['Bank / Account', 'Centre Name', 'Receipts', 'Amount (Rs)'];
            foreach ($bankWise as $bw) {
                $eRows[] = [$bw['label'], '', $bw['count'], number_format($bw['total'], 2)];
                foreach ($bankDetailWise[$bw['label']] ?? [] as $cr) {
                    $eRows[] = ['', $cr['name'], $cr['count'], number_format($cr['total'], 2)];
                }
            }
            if ($bankWise->isNotEmpty()) {
                $eRows[] = ['Grand Total', '', $bankWise->sum('count'), number_format($bankWise->sum('total'), 2)];
            }

            $eTitle = 'Centre Collection Report | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($eHeaders, $eRows, 'centre-collection.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($eTitle, $eHeaders, $eRows, 'centre-collection.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.centre-collection-print', compact(
                    'instituteName', 'centreData', 'grandTotal', 'grandCount', 'dateFrom', 'dateTo', 'bankWise', 'bankDetailWise'
                ));
            }
        }

        return view('institute.reports.centre-collection', compact(
            'centreData', 'centers', 'sessions', 'sessionId', 'dateFrom', 'dateTo', 'grandTotal', 'grandCount',
            'bankWise', 'bankDetailWise'
        ));
    }

    public function channelPartnerCollectionReport(Request $request): mixed
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $partners      = ChannelPartner::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        // Old records (before collected_by_partner_id column was added) only have
        // collected_by = partner name as text. Include both old and new style.
        $partnerNames = $partners->pluck('name');
        $invoices = FeeInvoice::with(['collectedByPartner', 'bankAccount', 'student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(function ($q) use ($request, $partners, $partnerNames) {
                if ($request->filled('partner_id')) {
                    $partnerName = $partners->find((int) $request->partner_id)?->name ?? '';
                    $q->where('collected_by_partner_id', $request->partner_id)
                      ->orWhere(fn($sq) => $sq->whereNull('collected_by_partner_id')
                          ->where('collected_by', $partnerName));
                } else {
                    $q->whereNotNull('collected_by_partner_id')
                      ->orWhere(fn($sq) => $sq->whereNull('collected_by_partner_id')
                          ->whereIn('collected_by', $partnerNames));
                }
            })
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get();

        // Old-style records ke liye collected_by_partner_id aur relation resolve karo
        $invoices->each(function ($invoice) use ($partners) {
            if (!$invoice->collected_by_partner_id && $invoice->collected_by) {
                $resolved = $partners->first(fn($p) => $p->name === $invoice->collected_by);
                if ($resolved) {
                    $invoice->collected_by_partner_id = $resolved->id;
                    $invoice->setRelation('collectedByPartner', $resolved);
                }
            }
        });

        $partnerData = $invoices->groupBy('collected_by_partner_id')->map(fn($rows) => [
            'partner' => $rows->first()->collectedByPartner,
            'count'   => $rows->count(),
            'total'   => (float) $rows->sum('paid_amount'),
            'cash'    => (float) $rows->where('payment_mode', 'cash')->sum('paid_amount'),
            'upi'     => (float) $rows->where('payment_mode', 'upi')->sum('paid_amount'),
            'online'  => (float) $rows->where('payment_mode', 'online')->sum('paid_amount'),
            'cheque'  => (float) $rows->where('payment_mode', 'cheque')->sum('paid_amount'),
            'dd'      => (float) $rows->where('payment_mode', 'dd')->sum('paid_amount'),
            'neft'    => (float) $rows->where('payment_mode', 'neft')->sum('paid_amount'),
            'rtgs'    => (float) $rows->where('payment_mode', 'rtgs')->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $grandTotal = (float) $invoices->sum('paid_amount');
        $grandCount = $invoices->count();

        // ── Bank-wise breakdown (non-cash payments carrying a bank account/name) ──
        $bankInvoices = $invoices->filter(fn($inv) => $inv->bank_account_id || $inv->bank_name);
        $bankGroups   = $bankInvoices->groupBy(fn($inv) => $inv->bank_account_id ? 'acct:' . $inv->bank_account_id : 'name:' . $inv->bank_name);

        $bankWise = $bankGroups->map(fn($rows) => [
            'label' => $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name,
            'count' => $rows->count(),
            'total' => (float) $rows->sum('paid_amount'),
        ])->sortByDesc('total')->values();

        $bankDetailWise = $bankGroups->mapWithKeys(function ($rows) {
            $label       = $rows->first()->bankAccount?->display_label ?: $rows->first()->bank_name;
            $partnerRows = $rows->groupBy('collected_by_partner_id')->map(fn($prows) => [
                'name'  => $prows->first()->collectedByPartner?->name ?? 'Unknown Partner',
                'count' => $prows->count(),
                'total' => (float) $prows->sum('paid_amount'),
            ])->sortByDesc('total')->values();
            return [$label => $partnerRows];
        });

        if ($request->filled('export')) {
            $eHeaders = ['#', 'Channel Partner', 'Receipts', 'Cash (Rs)', 'UPI (Rs)', 'Online (Rs)', 'Cheque (Rs)', 'DD (Rs)', 'NEFT (Rs)', 'RTGS (Rs)', 'Total (Rs)'];
            $eRows    = $partnerData->values()->map(fn($row, $i) => [
                $i + 1,
                $row['partner']?->name ?? 'Unknown',
                $row['count'],
                number_format($row['cash'], 2),
                number_format($row['upi'], 2),
                number_format($row['online'], 2),
                number_format($row['cheque'], 2),
                number_format($row['dd'], 2),
                number_format($row['neft'], 2),
                number_format($row['rtgs'], 2),
                number_format($row['total'], 2),
            ])->toArray();

            // Append bank-wise / partner breakdown rows after the partner list
            $eRows[] = [];
            $eRows[] = ['Bank-wise Collection — Channel Partner Breakdown'];
            $eRows[] = ['Bank / Account', 'Channel Partner', 'Receipts', 'Amount (Rs)'];
            foreach ($bankWise as $bw) {
                $eRows[] = [$bw['label'], '', $bw['count'], number_format($bw['total'], 2)];
                foreach ($bankDetailWise[$bw['label']] ?? [] as $pr) {
                    $eRows[] = ['', $pr['name'], $pr['count'], number_format($pr['total'], 2)];
                }
            }
            if ($bankWise->isNotEmpty()) {
                $eRows[] = ['Grand Total', '', $bankWise->sum('count'), number_format($bankWise->sum('total'), 2)];
            }

            $eTitle = 'Channel Partner Collection Report | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($eHeaders, $eRows, 'channel-partner-collection.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($eTitle, $eHeaders, $eRows, 'channel-partner-collection.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.channel-partner-collection-print', compact(
                    'instituteName', 'partnerData', 'grandTotal', 'grandCount', 'dateFrom', 'dateTo', 'bankWise', 'bankDetailWise'
                ));
            }
        }

        return view('institute.reports.channel-partner-collection', compact(
            'partnerData', 'partners', 'sessions', 'sessionId', 'dateFrom', 'dateTo', 'grandTotal', 'grandCount',
            'bankWise', 'bankDetailWise'
        ));
    }

    public function staffCollectionDetail(Request $request, int $staffId): mixed
    {
        $instituteId = $this->instituteId();
        $staff       = StaffMember::where('id', $staffId)->where('institute_id', $instituteId)->firstOrFail();

        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        $invoices = FeeInvoice::with(['student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(fn($q) => $q
                ->where('collected_by_staff_id', $staffId)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_staff_id')
                    ->where('collected_by', $staff->name))
            )
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get();

        $totalAmount   = (float) $invoices->sum('paid_amount');
        $totalReceipts = $invoices->count();
        $cashTotal     = (float) $invoices->where('payment_mode', 'cash')->sum('paid_amount');
        $upiTotal      = (float) $invoices->where('payment_mode', 'upi')->sum('paid_amount');
        $onlineTotal   = (float) $invoices->where('payment_mode', 'online')->sum('paid_amount');

        if ($request->filled('export')) {
            [$headers, $rows] = $this->collectionDetailExportData($invoices);
            $title = 'Staff Collection — ' . $staff->name . ' | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($headers, $rows, 'staff-' . $staffId . '-receipts.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($title, $headers, $rows, 'staff-' . $staffId . '-receipts.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.collection-detail-print', compact(
                    'instituteName', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal', 'dateFrom', 'dateTo'
                ) + ['entityName' => $staff->name, 'entitySubtitle' => $staff->designation ?? '', 'entityType' => 'Staff']);
            }
        }

        return view('institute.reports.staff-collection-detail', compact(
            'staff', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
            'sessions', 'sessionId', 'dateFrom', 'dateTo'
        ));
    }

    public function centreCollectionDetail(Request $request, int $centreId): mixed
    {
        $instituteId = $this->instituteId();
        $centre      = Center::where('id', $centreId)->where('institute_id', $instituteId)->firstOrFail();

        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        $invoices = FeeInvoice::with(['student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(fn($q) => $q
                ->where('collected_by_center_id', $centreId)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                    ->where('collected_by', $centre->name))
            )
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get();

        $totalAmount   = (float) $invoices->sum('paid_amount');
        $totalReceipts = $invoices->count();
        $cashTotal     = (float) $invoices->where('payment_mode', 'cash')->sum('paid_amount');
        $upiTotal      = (float) $invoices->where('payment_mode', 'upi')->sum('paid_amount');
        $onlineTotal   = (float) $invoices->where('payment_mode', 'online')->sum('paid_amount');

        if ($request->filled('export')) {
            [$headers, $rows] = $this->collectionDetailExportData($invoices);
            $title = 'Centre Collection — ' . $centre->name . ' | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($headers, $rows, 'centre-' . $centreId . '-receipts.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($title, $headers, $rows, 'centre-' . $centreId . '-receipts.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.collection-detail-print', compact(
                    'instituteName', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal', 'dateFrom', 'dateTo'
                ) + ['entityName' => $centre->name, 'entitySubtitle' => '', 'entityType' => 'Centre']);
            }
        }

        return view('institute.reports.centre-collection-detail', compact(
            'centre', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
            'sessions', 'sessionId', 'dateFrom', 'dateTo'
        ));
    }

    public function channelPartnerCollectionDetail(Request $request, int $partnerId): mixed
    {
        $instituteId = $this->instituteId();
        $partner     = ChannelPartner::where('id', $partnerId)->where('institute_id', $instituteId)->firstOrFail();

        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->session_id ?? $activeSession?->id;
        $sessions      = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $dateFrom      = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo        = $request->date_to   ?? now()->toDateString();

        $invoices = FeeInvoice::with(['student.stream.course', 'items'])
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where(fn($q) => $q
                ->where('collected_by_partner_id', $partnerId)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_partner_id')
                    ->where('collected_by', $partner->name))
            )
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get();

        $totalAmount   = (float) $invoices->sum('paid_amount');
        $totalReceipts = $invoices->count();
        $cashTotal     = (float) $invoices->where('payment_mode', 'cash')->sum('paid_amount');
        $upiTotal      = (float) $invoices->where('payment_mode', 'upi')->sum('paid_amount');
        $onlineTotal   = (float) $invoices->where('payment_mode', 'online')->sum('paid_amount');

        if ($request->filled('export')) {
            [$headers, $rows] = $this->collectionDetailExportData($invoices);
            $title = 'Channel Partner Collection — ' . $partner->name . ' | ' . $dateFrom . ' to ' . $dateTo;
            if ($request->export === 'csv')   return $this->exportCsv($headers, $rows, 'partner-' . $partnerId . '-receipts.csv');
            if ($request->export === 'excel') return $this->exportSimpleExcel($title, $headers, $rows, 'partner-' . $partnerId . '-receipts.xlsx');
            if ($request->export === 'pdf') {
                $instituteName = Institute::find($instituteId)?->name ?? 'Institute';
                return view('institute.reports.collection-detail-print', compact(
                    'instituteName', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal', 'dateFrom', 'dateTo'
                ) + ['entityName' => $partner->name, 'entitySubtitle' => '', 'entityType' => 'Channel Partner']);
            }
        }

        return view('institute.reports.channel-partner-collection-detail', compact(
            'partner', 'invoices', 'totalAmount', 'totalReceipts', 'cashTotal', 'upiTotal', 'onlineTotal',
            'sessions', 'sessionId', 'dateFrom', 'dateTo'
        ));
    }

    private function collectionDetailExportData($invoices): array
    {
        $headers = ['#', 'Invoice No', 'Date', 'Student Name', 'Student ID', 'Roll No', 'Father Name', 'Mother Name', 'Course', 'Mode', 'Fee Items', 'Amount (Rs)', 'Discount (Rs)'];
        $rows    = $invoices->values()->map(fn($inv, $i) => [
            $i + 1,
            $inv->invoice_no,
            $inv->payment_date?->format('d/m/Y'),
            $inv->student->name ?? '',
            $inv->student->student_uid ?? '',
            $inv->student->roll_no ?? '',
            $inv->student->father_name ?? '',
            $inv->student->mother_name ?? '',
            $inv->student->stream->course->name ?? '',
            strtoupper($inv->payment_mode ?? ''),
            $inv->items->pluck('fee_name')->implode(', '),
            number_format($inv->paid_amount, 2),
            number_format($inv->discount ?? 0, 2),
        ])->toArray();
        return [$headers, $rows];
    }

    public function practicalTokenCollectionReport(Request $request): mixed
    {
        $instituteId   = $this->instituteId();
        $institute     = \App\Models\Institute::find($instituteId);
        $activeSession = AcademicSession::viewSession($instituteId);

        // Allow null sessionId for "All Sessions"
        $sessionId = $request->has('session_id')
            ? ($request->session_id ?: null)
            : $activeSession?->id;

        $sessions     = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $courseTypes  = \App\Models\CourseType::where('institute_id', $instituteId)->where('is_active', true)->orderBy('name')->get();
        $courses      = Course::where('institute_id', $instituteId)->where('status', true)->with('type')->orderBy('name')->get();
        $subjects     = \App\Models\Subject::where('institute_id', $instituteId)->orderBy('name')->get();

        $query = PracticalFeeTokenBatch::with(['course.type', 'subject', 'session', 'entries.student'])
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($request->course_type_id, fn($q) => $q->whereHas('course', fn($cq) => $cq->where('course_type_id', $request->course_type_id)))
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->subject_id, fn($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->semester, fn($q) => $q->where('semester', $request->semester))
            ->when($request->batch_id, fn($q) => $q->where('id', $request->batch_id))
            ->orderByDesc('collection_date');

        $batches = $query->get();

        $grandTotal    = (float) $batches->sum(fn($b) => $b->entries->sum('amount'));
        $grandStudents = $batches->sum(fn($b) => $b->entries->count());

        $export = $request->input('export');
        if ($export) {
            $sessionName     = $sessionId ? ($sessions->firstWhere('id', $sessionId)?->name ?? '') : 'All Sessions';
            $courseTypeName  = $request->course_type_id ? ($courseTypes->firstWhere('id', $request->course_type_id)?->name ?? '') : '';
            $courseName      = $request->course_id ? ($courses->firstWhere('id', $request->course_id)?->name ?? '') : '';
            $subjectName     = $request->subject_id ? ($subjects->firstWhere('id', $request->subject_id)?->name ?? '') : '';
            $semesterLabel   = $request->semester ? ('Sem ' . $request->semester) : '';
            $batchTitle      = '';
            if ($request->batch_id && $batches->count() === 1) {
                $batchTitle = $batches->first()?->title ?? ('Token #' . $batches->first()?->id);
            }

            if ($export === 'pdf') {
                return view('institute.reports.practical-token-collection-print', compact(
                    'batches', 'grandTotal', 'grandStudents',
                    'sessionName', 'courseTypeName', 'courseName', 'subjectName', 'semesterLabel', 'batchTitle',
                    'institute'
                ));
            }

            // Build flat rows for CSV/Excel
            $headers = [
                '#', 'Session', 'Batch', 'Course Type', 'Course', 'Subject', 'Semester', 'Collection Date',
                'Student Name', 'Mobile', 'Student ID', 'Roll No', 'Father Name', 'Mother Name',
                'Token Amt (Rs)', 'Amount (Rs)', 'Fine (Rs)', 'Discount (Rs)', 'Status', 'Posted On', 'Posted By',
            ];
            $rows = [];
            $rowNum = 1;
            foreach ($batches as $b) {
                foreach ($b->entries as $entry) {
                    $rows[] = [
                        $rowNum++,
                        $b->session?->name ?? '',
                        $b->title ?? ('Token #' . $b->id),
                        $b->course?->type?->name ?? '',
                        $b->course?->name ?? '',
                        $b->subject?->name ?? '',
                        $b->semester ? 'S' . $b->semester : '',
                        $b->collection_date?->format('d/m/Y') ?? '',
                        $entry->student?->name ?? '',
                        $entry->student?->mobile ?? '',
                        $entry->student?->student_uid ?? '',
                        $entry->student?->roll_no ?? '',
                        $entry->student?->father_name ?? '',
                        $entry->student?->mother_name ?? '',
                        number_format((float) $b->token_amount, 2),
                        number_format((float) $entry->amount, 2),
                        number_format((float) $entry->fine, 2),
                        number_format((float) $entry->discount, 2),
                        ucfirst($entry->status ?? 'pending'),
                        $entry->posted_at?->format('d/m/Y') ?? '',
                        $entry->entered_by_name ?? '',
                    ];
                }
            }

            $filename = 'practical-token-collection-' . now()->format('Ymd');
            if ($export === 'excel') {
                return $this->exportSimpleExcel('Practical Token Collection', $headers, $rows, $filename);
            }
            return $this->exportCsv($headers, $rows, $filename);
        }

        return view('institute.reports.practical-token-collection', compact(
            'batches', 'sessions', 'courseTypes', 'courses', 'subjects',
            'sessionId', 'grandTotal', 'grandStudents'
        ));
    }

    // ════════════════════════════════════════════════════════════════════
    //  FEE DUE LIST — CSV export (add to feeDueList)
    // ════════════════════════════════════════════════════════════════════
    private function exportDueCsv($students, $dueData, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($students, $dueData) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Student ID', 'Name', 'Mobile', 'Course', 'Stream', 'Payable (₹)', 'Collection (₹)', 'Discount (₹)', 'Total Paid (₹)', 'Due (₹)']);
            foreach ($students as $s) {
                $d = $dueData[$s->id] ?? ['payable'=>0,'paid'=>0,'collection'=>0,'discount'=>0,'due'=>0];
                fputcsv($out, [
                    $s->student_uid,
                    $s->name,
                    $s->mobile,
                    $s->stream->course->name ?? '',
                    $s->stream->name ?? '',
                    number_format($d['payable'], 2),
                    number_format($d['collection'], 2),
                    number_format($d['discount'], 2),
                    number_format($d['paid'], 2),
                    number_format($d['due'], 2),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
