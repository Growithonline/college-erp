<?php

namespace App\Http\Controllers\Center;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Fee\FeeCollectionController as InstituteFeeController;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\Student;
use App\Models\WalletExtensionRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CenterFeeController extends Controller
{
    private function center()
    {
        return Auth::guard('center')->user();
    }

    public function index(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canCollectFee(), 403, 'Fee collection not permitted for this center.');

        $instituteId = $center->institute_id;
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->first();

        $allSessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('id')->get();

        $permsMap = $center->sessionPermsMap();
        $allowedSessions = $permsMap === null
            ? $allSessions
            : $allSessions->filter(fn($s) => (bool) ($permsMap[$s->id]['fee'] ?? false))->values();

        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : ($activeSession?->id ?? 0);

        $dateFrom = $request->date_from ?? now()->toDateString();
        $dateTo   = $request->date_to   ?? now()->toDateString();

        $query = FeeInvoice::with(['student.stream.course', 'student.coursePart', 'student.wallets', 'session', 'items'])
            ->where('institute_id', $instituteId)
            ->where(fn($q) => $q
                ->where('collected_by_center_id', $center->id)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')->where('collected_by', $center->name))
                ->orWhereHas('student', fn($sq) => $sq
                    ->where('admission_source', 'center')
                    ->where('admission_source_id', $center->id))
            );

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $query->whereDate('payment_date', '>=', $dateFrom)
              ->whereDate('payment_date', '<=', $dateTo);

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('student', fn($sq) => $sq
                      ->where('name', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%")
                      ->orWhere('student_uid', 'like', "%{$search}%"));
            });
        }

        $perPage  = in_array((int) $request->per_page, [10, 20, 50, 100], true) ? (int) $request->per_page : 20;
        $invoices = $query->orderByDesc('payment_date')->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        $pageStudentIds = $invoices->getCollection()->pluck('student_id')->unique()->filter()->values()->all();
        $totalPaidByStudent = FeeInvoice::whereIn('student_id', $pageStudentIds)
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(paid_amount) as total_paid')
            ->pluck('total_paid', 'student_id');

        // Stats for the filtered result set
        $statsBase = FeeInvoice::where('institute_id', $instituteId)
            ->where(fn($q) => $q
                ->where('collected_by_center_id', $center->id)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                    ->where('collected_by', $center->name))
            )
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $totalPaid     = (float) (clone $statsBase)->where('is_cancelled', false)->sum('paid_amount');
        $totalInvoices = (clone $statsBase)->where('is_cancelled', false)->count();
        $cashAmt       = (float) (clone $statsBase)->where('payment_mode', 'cash')->where('is_cancelled', false)->sum('paid_amount');
        $upiAmt        = (float) (clone $statsBase)->where('payment_mode', 'upi')->where('is_cancelled', false)->sum('paid_amount');
        $onlineAmt     = (float) (clone $statsBase)->where('payment_mode', 'online')->where('is_cancelled', false)->sum('paid_amount');
        $chequeAmt     = (float) (clone $statsBase)->where('payment_mode', 'cheque')->where('is_cancelled', false)->sum('paid_amount');
        $cashCount     = (clone $statsBase)->where('payment_mode', 'cash')->where('is_cancelled', false)->count();
        $upiCount      = (clone $statsBase)->where('payment_mode', 'upi')->where('is_cancelled', false)->count();
        $onlineCount   = (clone $statsBase)->where('payment_mode', 'online')->where('is_cancelled', false)->count();

        return view('center.fee.index', compact(
            'invoices', 'allowedSessions', 'activeSession', 'sessionId',
            'dateFrom', 'dateTo', 'perPage',
            'totalPaid', 'totalInvoices',
            'cashAmt', 'upiAmt', 'onlineAmt', 'chequeAmt',
            'cashCount', 'upiCount', 'onlineCount',
            'totalPaidByStudent', 'center'
        ));
    }

    public function walletStatus()
    {
        $center = $this->center();
        $wallet = $center->wallet;

        $transactions = $wallet
            ? $wallet->transactions()->orderByDesc('id')->paginate(20)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        $extensionRequests = WalletExtensionRequest::where('entity_type', 'center')
            ->where('entity_id', $center->id)
            ->orderByDesc('id')
            ->get();

        return view('center.fee.wallet-status', compact('wallet', 'transactions', 'extensionRequests'));
    }

    public function requestExtension(Request $request)
    {
        $center = $this->center();
        $data = $request->validate([
            'request_type'     => 'required|in:expiry_extension,token_topup',
            'reason'           => 'required|string|max:1000',
            'requested_days'   => 'nullable|integer|min:1|max:365',
            'requested_amount' => 'nullable|numeric|min:1',
        ]);

        WalletExtensionRequest::create([
            'entity_type'      => 'center',
            'entity_id'        => $center->id,
            'institute_id'     => $center->institute_id,
            'request_type'     => $data['request_type'],
            'reason'           => $data['reason'],
            'requested_days'   => $data['requested_days'] ?? null,
            'requested_amount' => $data['requested_amount'] ?? null,
            'status'           => 'pending',
        ]);

        return back()->with('extension_request_sent', 'Your request has been sent to admin.');
    }

    public function create(Request $request)
    {
        $center = $this->center();
        $fromAdmission = session()->has('from_admission_fee_payment');
        abort_unless($center->canCollectFee() || $fromAdmission, 403, 'Fee collection not permitted for this center.');

        // Wallet check — flash status so fee create blade can show popup
        $walletStatus = $center->walletBlockStatus();
        if ($walletStatus && $walletStatus['blocked']) {
            session()->flash('wallet_blocked', $walletStatus);
        }

        if ($request->filled('student_id')) {
            $student = Student::find((int) $request->student_id);
            if ($student) {
                if ($center->isFeesScopeOwn()) {
                    $this->assertOwnStudent($student->id, $center);
                }
                abort_unless(
                    $center->canCollectFeeInSession((int) $student->academic_session_id),
                    403, 'Fee collection is not permitted for this session.'
                );
            }
        }

        return app(InstituteFeeController::class)->create($request);
    }

    public function store(Request $request)
    {
        $center = $this->center();
        $fromAdmission = session()->has('from_admission_fee_payment');
        abort_unless($center->canCollectFee() || $fromAdmission, 403, 'Fee collection not permitted for this center.');

        // Wallet check before processing fee
        $wallet = $center->wallet;
        if ($wallet) {
            $paidAmount = collect($request->fee_items ?? [])
                ->sum(fn($item) => max(0, (float) ($item['amount'] ?? 0)));

            $walletStatus = $wallet->getBlockStatus($paidAmount);
            if ($walletStatus['blocked']) {
                return back()
                    ->withErrors(['wallet_error' => $walletStatus['reason']])
                    ->with('wallet_blocked', $walletStatus)
                    ->withInput();
            }
        }

        if ($request->filled('student_id')) {
            $student = Student::find((int) $request->student_id);
            if ($student) {
                if ($center->isFeesScopeOwn()) {
                    $this->assertOwnStudent($student->id, $center);
                }
                abort_unless(
                    $fromAdmission || $center->canCollectFeeInSession((int) $student->academic_session_id),
                    403, 'Fee collection is not permitted for this session.'
                );
            }
        }

        // Payment mode restriction
        if ($center->allowed_pay_modes !== null && $request->filled('payment_mode')) {
            abort_unless(
                $center->isAllowedPayMode($request->payment_mode),
                403, 'This payment mode is not permitted for your center.'
            );
        }

        // Discount limit enforcement
        $totalReqDiscount = collect($request->fee_items ?? [])
            ->sum(fn($item) => max(0, (float) ($item['discount'] ?? 0)));

        if ($totalReqDiscount > 0) {
            abort_unless(
                $center->can_give_discount,
                403, 'Discount is not permitted for your center.'
            );
            $max = $center->getMaxDiscountPercent();
            if ($max > 0 && $max < 100) {
                foreach ($request->fee_items ?? [] as $item) {
                    $disc = max(0, (float) ($item['discount'] ?? 0));
                    if ($disc <= 0) continue;
                    $base = max(0, (float) ($item['total_fee'] ?? 0));
                    if ($base <= 0) $base = $disc + max(0, (float) ($item['amount'] ?? 0));
                    if ($base > 0) {
                        abort_unless(
                            ($disc / $base) * 100 <= $max + 0.01,
                            403, "Discount exceeds the maximum allowed limit of {$max}% for your center."
                        );
                    }
                }
            }
        }

        return app(InstituteFeeController::class)->store($request);
    }

    public function searchStudent(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canCollectFee(), 403, 'Fee collection not permitted for this center.');

        // Determine the session context for scope check
        $sessionId = $request->filled('session_id')
            ? (int) $request->session_id
            : (int) AcademicSession::where('institute_id', $center->institute_id)
                ->where('is_active', true)->value('id');

        if ($center->feeScopeForSession($sessionId) === 'own') {
            return $this->searchOwnStudents($request, $center, $sessionId);
        }

        // All-scope: search across institute students in the selected session
        return app(InstituteFeeController::class)->searchStudent($request);
    }

    public function receipt(Student $student, FeeInvoice $invoice)
    {
        $center = $this->center();
        abort_unless($center->canCollectFee(), 403, 'Fee collection not permitted for this center.');

        $isOwnCollection = (int) ($invoice->collected_by_center_id ?? 0) === (int) $center->id
            || $invoice->collected_by === $center->name;
        $isOwnStudent = $student->admission_source === 'center'
            && (int) $student->admission_source_id === (int) $center->id;

        abort_if(
            $invoice->institute_id !== $center->institute_id || (!$isOwnCollection && !$isOwnStudent),
            403,
            'This receipt is not accessible to your center.'
        );

        return app(InstituteFeeController::class)->receipt($student, $invoice);
    }

    public function export(Request $request)
    {
        $center = $this->center();
        abort_unless($center->canCollectFee(), 403, 'Fee collection not permitted for this center.');

        $instituteId = $center->institute_id;

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : 0;
        $dateFrom  = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo    = $request->date_to   ?? now()->toDateString();
        $format    = strtolower($request->format ?? 'csv');

        $query = FeeInvoice::with(['student.stream.course', 'student.wallets', 'session', 'items'])
            ->where('institute_id', $instituteId)
            ->where(fn($q) => $q
                ->where('collected_by_center_id', $center->id)
                ->orWhere(fn($sq) => $sq->whereNull('collected_by_center_id')
                    ->where('collected_by', $center->name))
            )
            ->where('is_cancelled', false);

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $query->whereDate('payment_date', '>=', $dateFrom)
              ->whereDate('payment_date', '<=', $dateTo);

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        $invoices    = $query->orderBy('payment_date')->orderBy('id')->get();
        $sessionName = $sessionId ? AcademicSession::find($sessionId)?->name : 'All Sessions';
        $filename    = 'fee-collection-' . now()->format('Ymd-His');

        if ($format === 'pdf') {
            $institute = Institute::find($instituteId);
            $totalAmt  = $invoices->sum('paid_amount');
            $pdf = Pdf::loadView('center.fee.export-pdf', compact(
                'invoices', 'center', 'institute', 'sessionName', 'dateFrom', 'dateTo', 'totalAmt'
            ))->setPaper('A4', 'landscape');
            return $pdf->download($filename . '.pdf');
        }

        $metaRows = [
            ['Fee Collection Report — ' . $center->name],
            ['Session: ' . $sessionName, 'From: ' . $dateFrom, 'To: ' . $dateTo],
            ['Generated: ' . now()->format('d M Y h:i A')],
            [],
        ];

        $headers = [
            '#', 'Invoice No', 'Payment Date', 'Student Name', 'Roll No', 'UIN No',
            'Father Name', 'Mother Name', 'Enrollment No', 'Mobile',
            'Course', 'Stream', 'Semester', 'Session', 'Payment Mode',
            'Total Fee (Rs)', 'Fine (Rs)', 'Discount (Rs)', 'Due (Rs)', 'Collected By',
        ];

        $rows = $invoices->values()->map(fn($inv, $i) => [
            $i + 1,
            $inv->invoice_no ?? '',
            $inv->payment_date?->format('d-m-Y') ?? '',
            $inv->student?->name ?? '',
            $inv->student?->roll_no ?? '',
            $inv->student?->student_uid ?? '',
            $inv->student?->father_name ?? '',
            $inv->student?->mother_name ?? '',
            $inv->student?->enrollment_no ?? '',
            $inv->student?->mobile ?? '',
            $inv->student?->stream?->course?->name ?? '',
            $inv->student?->stream?->name ?? '',
            $inv->semester ? 'S' . $inv->semester : '',
            $inv->session?->name ?? '',
            ucfirst($inv->payment_mode ?? ''),
            $inv->paid_amount ?? 0,
            round($inv->items->sum('fine'), 2),
            $inv->discount ?? 0,
            (function () use ($inv) {
                $w = $inv->student?->wallets->firstWhere('academic_session_id', $inv->academic_session_id);
                return $w && $w->main_b < 0 ? abs((float) $w->main_b) : 0;
            })(),
            $inv->collected_by ?? '',
        ])->all();

        $ext = $format === 'excel' ? 'xlsx' : 'csv';
        return response()->streamDownload(function () use ($metaRows, $headers, $rows) {
            $h = fopen('php://output', 'w');
            fprintf($h, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($metaRows as $meta) { fputcsv($h, $meta); }
            fputcsv($h, $headers);
            foreach ($rows as $row) { fputcsv($h, $row); }
            fclose($h);
        }, $filename . '.' . $ext, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $ext . '"',
        ]);
    }

    private function assertOwnStudent(int $studentId, $center): void
    {
        $student = Student::find($studentId);
        abort_unless(
            $student
            && (int) $student->institute_id === (int) $center->institute_id
            && $student->admission_source === 'center'
            && (int) $student->admission_source_id === (int) $center->id,
            403,
            'You can only collect fees for your own students.'
        );
    }

    private function searchOwnStudents(Request $request, $center, int $sessionId = 0)
    {
        $query = $request->q ?? '';

        if (!$sessionId) {
            $sessionId = (int) AcademicSession::where('institute_id', $center->institute_id)
                ->where('is_active', true)->value('id');
        }

        if (!$sessionId) {
            return response()->json([]);
        }

        $students = Student::where('institute_id', $center->institute_id)
            ->where('academic_session_id', $sessionId)
            ->where('admission_source', 'center')
            ->where('admission_source_id', $center->id)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('mobile', 'like', "%{$query}%")
                    ->orWhere('student_uid', 'like', "%{$query}%");
            })
            ->with('stream.course')
            ->limit(10)
            ->get();

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'student_uid' => $s->student_uid,
            'mobile'      => $s->mobile,
            'course'      => $s->stream->course->name ?? '',
            'stream'      => $s->stream->name ?? '',
        ]));
    }
}
