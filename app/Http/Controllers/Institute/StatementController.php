<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentWallet;
use App\Services\WalletService;
use App\Support\AcademicState;
use App\Traits\BuildsStudentStatements;
use Illuminate\Http\Request;

class StatementController extends Controller
{
    use BuildsStudentStatements;
    // ── Institute ID — multi-guard support ──────────────────────────
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user && $user->institute_id) {
                return (int) $user->institute_id;
            }
        }
        abort(403, 'Not authenticated');
    }

    private function institute(): ?Institute
    {
        return Institute::find($this->instituteId());
    }

    private function checkStaffPermission(): void
    {
        $staff = auth()->guard('staff')->user();
        if ($staff && !$staff->canViewStatements()) {
            abort(403, 'Permission denied.');
        }
    }

    // ── AJAX Student Search ──────────────────────────────────────────
    public function searchStudent(Request $request)
    {
        $this->checkStaffPermission();
        $q           = trim($request->q ?? '');
        $instituteId = $this->instituteId();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $instituteId)
            ->where(function ($q2) use ($q) {
                $q2->where('name',         'like', "%{$q}%")
                   ->orWhere('student_uid', 'like', "%{$q}%")
                   ->orWhere('mobile',      'like', "%{$q}%");
            });

        if ($staff = auth()->guard('staff')->user()) {
            $staff->scopeOperationalStudents($query);
        }

        $students = $query->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'student_uid' => $s->student_uid,
                'mobile'      => $s->mobile,
                'father_name' => $s->father_name ?? '',
                'mother_name' => $s->mother_name ?? '',
                'course'      => $s->stream->course->name ?? '',
                'part'        => AcademicState::yearLabel(
                    $s->stream?->course?->structure_type,
                    $s->current_semester,
                    $s->coursePart?->year_number,
                    $s->stream?->course?->effectiveSemestersPerYear() ?? 2
                ),
                'semester'    => $s->current_semester ? 'Sem ' . $s->current_semester : '',
                'session'     => $s->session->name ?? '',
            ]);

        return response()->json($students);
    }

    // ════════════════════════════════════════════════════════════════
    //  GET STUDENT BALANCE — Session wise + full course balance
    // ════════════════════════════════════════════════════════════════
    public function studentBalance(Request $request)
    {
        $this->checkStaffPermission();
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('id')->get();

        $student  = null;
        $balances = collect();

        if ($request->student_id) {
            $student = Student::with(['stream.course', 'coursePart', 'session'])
                ->where('institute_id', $instituteId)
                ->findOrFail($request->student_id);

            $currentContext = WalletService::resolveAcademicContext($student, (int) $student->academic_session_id);
            if (!empty($currentContext['course_part'])) {
                $student->setRelation('coursePart', $currentContext['course_part']);
            }

            $balances = $this->buildBalances($student, $instituteId);

            // Print mode
            if ($request->print) {
                return view('institute.statement.balance-print', [
                    'student'    => $student,
                    'balances'   => $balances,
                    'institute'  => $this->institute(),
                    'printMode'  => $request->print,
                    'printedBy'  => $this->currentUserName(),
                    'receiptUrl' => $this->receiptUrl('balance', $student),
                    'autoprint'  => true,
                ]);
            }
        }

        return view('institute.statement.balance', compact(
            'sessions', 'student', 'balances'
        ));
    }

    // ════════════════════════════════════════════════════════════════
    //  GET STUDENT FEE SUBMIT RECORD — Complete history
    // ════════════════════════════════════════════════════════════════
    public function feeRecord(Request $request)
    {
        $this->checkStaffPermission();
        $instituteId = $this->instituteId();
        $sessions    = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('id')->get();

        $student      = null;
        $history      = collect();
        $subjectNames = collect();

        if ($request->student_id) {
            $student = Student::with(['stream.course', 'coursePart', 'session'])
                ->where('institute_id', $instituteId)
                ->findOrFail($request->student_id);

            $currentContext = WalletService::resolveAcademicContext($student, (int) $student->academic_session_id);
            if (!empty($currentContext['course_part'])) {
                $student->setRelation('coursePart', $currentContext['course_part']);
            }

            // Current session subjects
            $subjectNames = StudentSubject::where('student_id', $student->id)
                ->where('academic_session_id', $student->academic_session_id)
                ->with('subject')
                ->get()
                ->pluck('subject.name')
                ->filter()
                ->values();

            $history = $this->buildHistory($student, $instituteId);

            // Print mode
            if ($request->print) {
                return view('institute.statement.record-print', [
                    'student'      => $student,
                    'history'      => $history,
                    'institute'    => $this->institute(),
                    'printMode'    => $request->print,
                    'subjectNames' => $subjectNames,
                    'printedBy'    => $this->currentUserName(),
                    'receiptUrl'   => $this->receiptUrl('record', $student),
                    'autoprint'    => true,
                ]);
            }
        }

        return view('institute.statement.record', compact(
            'sessions', 'student', 'history', 'subjectNames'
        ));
    }

    // ── Helpers ─────────────────────────────────────────────────────
    private function currentUserName(): string
    {
        return auth()->guard('web')->user()?->name
            ?? auth()->guard('staff')->user()?->name
            ?? 'System';
    }

    private function receiptUrl(string $type, Student $student): string
    {
        $iid = $this->instituteId();
        $sig = substr(hash_hmac('sha256', "{$student->id}:{$iid}:{$type}", config('app.key')), 0, 32);
        return url("/receipt/{$type}?sid={$student->id}&iid={$iid}&sig={$sig}");
    }

    // ── Build session-wise balances ──────────────────────────────────
    private function buildBalances(Student $student, int $instituteId): \Illuminate\Support\Collection
    {
        $wallets = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $instituteId)
            ->get()
            ->keyBy('academic_session_id');

        $invoiceSessionIds = FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->pluck('academic_session_id')
            ->filter()
            ->unique();

        $allSessionIds = $wallets->keys()
            ->merge($invoiceSessionIds)
            ->unique()
            ->sort()
            ->values();

        if ($allSessionIds->isEmpty()) {
            return collect();
        }

        $sessions = AcademicSession::whereIn('id', $allSessionIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // Student ka current session — iske baad wale sessions "old" hain
        $currentSessionId = (int) $student->academic_session_id;

        return $allSessionIds->map(function ($sessId) use ($wallets, $sessions, $student, $currentSessionId) {
            $session = $sessions[$sessId] ?? null;
            if (!$session) return null;

            $invoiceIds = FeeInvoice::where('student_id', $student->id)
                ->where('academic_session_id', $sessId)
                ->where('is_cancelled', false)
                ->pluck('id');

            $paid = (float) FeeInvoice::whereIn('id', $invoiceIds)->sum('paid_amount');

            $discount = (float) FeeInvoice::whereIn('id', $invoiceIds)->sum('discount');

            $fine = (float) \App\Models\FeeInvoiceItem::whereIn('fee_invoice_id', $invoiceIds)->sum('fine');

            $summary = WalletService::getStudentSummary($student, (int) $sessId);
            $walletBalance = (float) ($wallets[$sessId]?->main_b ?? 0);

            // Old session (student aage promote ho gaya): due ko "carried_forward" mark karo
            // Current session ka wallet balance hi real due hai (carry-forward bhi include hai usme)
            $isOldSession     = ($sessId < $currentSessionId);
            $wasCarriedForward = $isOldSession && ($walletBalance < 0);
            $due = !$wasCarriedForward ? (float) ($summary['total_due'] ?? 0) : 0;

            return [
                'session'         => $session,
                'paid'            => $paid,
                'discount'        => $discount,
                'fine'            => $fine,
                'due'             => $due,
                'carried_forward' => $wasCarriedForward,
            ];
        })->filter()->values();
    }

    // ── Build session-wise fee history ───────────────────────────────
    private function buildHistory(Student $student, int $instituteId): \Illuminate\Support\Collection
    {
        $wallets = StudentWallet::where('student_id', $student->id)
            ->where('institute_id', $instituteId)
            ->get()
            ->keyBy('academic_session_id');

        // Wallet ke alawa FeeInvoice se bhi session IDs lo (promoted students ke liye)
        $invoiceSessionIds = FeeInvoice::where('student_id', $student->id)
            ->where('is_cancelled', false)
            ->pluck('academic_session_id')
            ->filter()
            ->unique();

        $allSessionIds = $wallets->keys()
            ->merge($invoiceSessionIds)
            ->unique()
            ->sort()
            ->values();

        if ($allSessionIds->isEmpty()) {
            return collect();
        }

        $sessions = AcademicSession::whereIn('id', $allSessionIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $currentSessionId = (int) $student->academic_session_id;

        return $allSessionIds->map(function ($sessId) use ($wallets, $sessions, $student, $currentSessionId) {
            $session = $sessions[$sessId] ?? null;
            if (!$session) return null;

            $invoices = FeeInvoice::where('student_id', $student->id)
                ->where('academic_session_id', $sessId)
                ->where('is_cancelled', false)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            $summary = WalletService::getStudentSummary($student, (int) $sessId);
            $walletBalance    = (float) ($wallets[$sessId]?->main_b ?? 0);
            $isOldSession     = ($sessId < $currentSessionId);
            $wasCarriedForward = $isOldSession && ($walletBalance < 0);

            // Skip: koi invoice nahi aur wallet bhi zero
            if ($invoices->isEmpty() && $walletBalance == 0) {
                return null;
            }

            $due = !$wasCarriedForward ? (float) ($summary['total_due'] ?? 0) : 0;

            $feeFromInvoices = $invoices->sum('total_amount');
            // When no invoice exists yet (fee pre-charged via onAdmission wallet debit),
            // derive total_fee from wallet balance so display shows correct charged amount
            $total_fee = max(
                (float) ($summary['total_charged'] ?? 0),
                (float) $feeFromInvoices
            );

            return [
                'session'         => $session,
                'invoices'        => $invoices,
                'total_paid'      => $invoices->sum('paid_amount'),
                'total_discount'  => $invoices->sum('discount'),
                'total_fee'       => $total_fee,
                'due'             => $due,
                'carried_forward' => $wasCarriedForward,
            ];
        })->filter()->values();
    }

    // ════════════════════════════════════════════════════════════════
    //  EXPORT — CSV export of student fee records
    // ════════════════════════════════════════════════════════════════
    public function exportCsv(Request $request)
    {
        $this->checkStaffExportPermission();
        $instituteId = $this->instituteId();

        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $instituteId)
            ->findOrFail($request->student_id);

        $history = $this->buildHistory($student, $instituteId);

        $filename = 'fee_record_' . $student->student_uid . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($student, $history) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Student Name', $student->name]);
            fputcsv($out, ['Student ID',   $student->student_uid]);
            fputcsv($out, ['Mobile',       $student->mobile ?? '']);
            fputcsv($out, ['Course',       $student->stream->course->name ?? '']);
            fputcsv($out, []);

            fputcsv($out, ['Session', 'Total Fee', 'Paid', 'Discount', 'Due', 'Status']);

            foreach ($history as $row) {
                fputcsv($out, [
                    $row['session']->name ?? '',
                    number_format($row['total_fee'], 2, '.', ''),
                    number_format($row['total_paid'], 2, '.', ''),
                    number_format($row['total_discount'], 2, '.', ''),
                    number_format($row['due'], 2, '.', ''),
                    $row['carried_forward'] ? 'Carried Forward' : ($row['due'] > 0 ? 'Due' : 'Cleared'),
                ]);

                foreach ($row['invoices'] as $inv) {
                    fputcsv($out, [
                        '  ' . ($inv->payment_date ?? ''),
                        number_format($inv->total_amount, 2, '.', ''),
                        number_format($inv->paid_amount, 2, '.', ''),
                        number_format($inv->discount, 2, '.', ''),
                        '',
                        $inv->is_cancelled ? 'Cancelled' : 'Invoice #' . $inv->invoice_no,
                    ]);
                }
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function checkStaffExportPermission(): void
    {
        $staff = auth()->guard('staff')->user();
        if ($staff && !$staff->hasPermission('statement_export')) {
            abort(403, 'Permission denied.');
        }
    }
}
