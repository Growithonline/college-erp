<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CoursePart;
use App\Models\FeeInvoice;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\FeeCalculatorService;
use App\Services\JournalService;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Reviews the fee history of students created by Bulk Excel Import (see
// StudentBulkImportController). Every such student is created with status
// "pending" and held back — no login, hidden from active student lists, fee
// collection blocked — until a staff member confirms, for each PAST semester the
// student had already completed before joining this system, how much was
// actually paid. The paid portion becomes a real, receipt-able FeeInvoice
// (head-wise distributed, same principle as the Fee Collection page's own "One
// Time Pay"); any shortfall becomes a "Previous Due" on the student's wallet.
// The student is then switched to whatever status the import intended (Active,
// Passed Out, Detained, Transferred, or Cancelled) — or, if the row turns out to
// be wrong (duplicate, bad data), it can be Rejected instead.
//
// Deliberately kept separate from AdmissionController's own Admissions Approvals
// page/flow (online admissions, quick admissions, etc.) — that page explicitly
// excludes bulk-imported students (see AdmissionController::approvalStudentsQuery())
// and this controller never touches it.
class BulkImportApprovalController extends Controller
{
    private const TERMINAL_STATUSES = ['passed_out', 'detained', 'transferred', 'cancelled'];

    // ── Resolve institute_id for any guard — matches StudentBulkImportController ──
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            if (auth()->guard($guard)->check()) {
                $id = auth()->guard($guard)->user()?->institute_id;
                if ($id) return (int) $id;
            }
        }
        abort(403, 'Institute context missing.');
    }

    // ── Permission gate — no-op for the institute-owner (web) guard, same
    // permission as the regular Admissions Approvals page.
    private function ensureAccess(): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff) return;
        abort_if(!$staff->hasPermission('admission_approve'), 403, 'You do not have permission to approve bulk-imported students. Please contact an administrator.');
    }

    private function ensureStaffCanReviewStudent(Student $student): void
    {
        $staff = Auth::guard('staff')->user();
        if ($staff) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your assigned course/stream access scope.');
        }
    }

    private function ensureBulkImportPendingStudent(Student $student): void
    {
        abort_if($student->institute_id !== $this->instituteId(), 403, 'This student does not belong to your institute.');
        abort_unless($student->is_bulk_import, 404, 'This student was not created by Bulk Excel Import.');
        abort_unless($student->status === 'pending', 404, 'This student has already been reviewed — there is nothing left to do here.');
    }

    private function pendingStudentsQuery(int $instituteId)
    {
        $query = Student::with(['stream.course', 'coursePart', 'session'])
            ->where('institute_id', $instituteId)
            ->where('is_bulk_import', true)
            ->where('status', 'pending');

        if ($staff = Auth::guard('staff')->user()) {
            $staff->scopeOperationalStudents($query);
        }

        return $query;
    }

    // ── List: bulk-imported students awaiting fee-history approval ──────
    public function index(Request $request)
    {
        $this->ensureAccess();
        $instituteId = $this->instituteId();

        $query = $this->pendingStudentsQuery($instituteId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%");
            });
        }

        $students = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('institute.admission.bulk-import-pending-index', compact('students'));
    }

    // ── All of the institute's academic sessions, oldest first — used both to build
    // the review page's session picker and to guess which one each past period
    // belongs to. ──
    private function sessionsForInstitute(int $instituteId)
    {
        return AcademicSession::where('institute_id', $instituteId)
            ->orderBy('start_date')
            ->get(['id', 'name', 'start_date']);
    }

    // ── Compute the past semesters/years this student needs fee-history reviewed
    // for, each with a reference "suggested" fee total and item breakdown (current
    // fee rules — may differ from what actually applied back then; the reviewer
    // can override both), plus a best-guess of which ACTUAL academic session that
    // period belongs to (e.g. a student now in Semester 4 of session 2026-27 has
    // Semesters 1-2 sitting in session 2025-26, one year back — counted by walking
    // the institute's own sessions, oldest to newest, the same number of years back
    // as the period is from the student's current year). The reviewer confirms or
    // corrects this on the review page — it is never applied silently. ──
    private function pastPeriods(Student $student): array
    {
        $student->loadMissing('stream.course');
        $course = $student->stream?->course;
        if (!$course) {
            return [];
        }

        $semestersPerYear = max(1, $course->effectiveSemestersPerYear());
        $isYearly = $semestersPerYear === 1;
        $currentSemester = (int) $student->current_semester;
        $targetStatus = $student->bulk_import_target_status ?? 'active';
        $isTerminal = in_array($targetStatus, self::TERMINAL_STATUSES, true);

        // Active students already had their OWN current semester auto-charged fresh
        // at import time (see StudentBulkImportController::importRow()), so only
        // semesters strictly BEFORE it need reviewing here. Terminal-status students
        // (Passed Out/Detained/Transferred/Cancelled) never got that auto-charge, so
        // their own last/final semester is reviewable too — up to and including it.
        $maxPeriod = $isTerminal ? $currentSemester : $currentSemester - 1;
        if ($maxPeriod < 1) {
            return [];
        }

        $coursePartsByYear = CoursePart::where('course_id', $course->id)->get()->keyBy('year_number');

        $sessions = $this->sessionsForInstitute((int) $student->institute_id);
        $currentSessionIndex = $sessions->search(fn($s) => (int) $s->id === (int) $student->academic_session_id);
        $currentYearNumber = (int) max(1, ceil($currentSemester / $semestersPerYear));

        $periods = [];
        for ($p = 1; $p <= $maxPeriod; $p++) {
            $yearNumber = (int) max(1, ceil($p / $semestersPerYear));
            $coursePart = $coursePartsByYear->get($yearNumber);

            $suggestedSession = null;
            if ($currentSessionIndex !== false) {
                $yearsBack = $currentYearNumber - $yearNumber;
                $mappedIndex = $currentSessionIndex - $yearsBack;
                if ($mappedIndex >= 0 && $mappedIndex < $sessions->count()) {
                    $suggestedSession = $sessions->get($mappedIndex);
                }
            }

            $feeData = ['items' => [], 'total' => 0.0];
            try {
                $feeData = FeeCalculatorService::calculate(
                    instituteId:      (int) $student->institute_id,
                    sessionId:        (int) $student->academic_session_id,
                    courseId:         $course->id,
                    coursePart:       $yearNumber,
                    semester:         $p,
                    studentType:      $student->student_type ?? 'regular',
                    admissionSource:  $student->admission_source ?? 'direct',
                    category:         $student->category ?? 'general',
                    gender:           $student->gender ?? 'other',
                    subjectIds:       [], // no historical subject-enrollment data exists for past periods
                    courseStreamId:   $student->course_stream_id,
                    coursePartId:     $coursePart?->id,
                    semestersPerYear: $semestersPerYear
                );
            } catch (\Throwable $e) {
                \Log::warning('Bulk import fee-history: suggested total calculation failed', [
                    'student_id' => $student->id,
                    'period'     => $p,
                    'error'      => $e->getMessage(),
                ]);
            }

            $periods[] = [
                'period_number'           => $p,
                'label'                   => $isYearly ? "Year {$yearNumber}" : "Semester {$p}",
                'year_number'             => $yearNumber,
                'suggested_total'         => round((float) ($feeData['total'] ?? 0), 2),
                'suggested_items'         => $feeData['items'] ?? [],
                'suggested_session_id'    => $suggestedSession?->id,
                'suggested_session_name'  => $suggestedSession?->name,
            ];
        }

        return $periods;
    }

    // ── Per-student review page ──────────────────────────────────────────
    public function show(Student $student)
    {
        $this->ensureAccess();
        $this->ensureBulkImportPendingStudent($student);
        $this->ensureStaffCanReviewStudent($student);

        $student->load(['stream.course', 'coursePart', 'session']);
        $periods = $this->pastPeriods($student);
        $sessions = $this->sessionsForInstitute((int) $student->institute_id);

        return view('institute.admission.bulk-import-pending-show', compact('student', 'periods', 'sessions'));
    }

    // ── Approve: create a receipt for the paid portion of each past semester,
    // record any shortfall as due, then activate the student. ──────────────
    public function approve(Request $request, Student $student)
    {
        $this->ensureAccess();
        $this->ensureBulkImportPendingStudent($student);
        $this->ensureStaffCanReviewStudent($student);

        $periods = $this->pastPeriods($student);

        if (empty($periods)) {
            // No past semesters to review (e.g. a fresh Semester 1 import) — a single
            // click finalizes it, no form to fill.
            return $this->finalizeApproval($student, []);
        }

        $validated = $request->validate([
            'periods'                => ['required', 'array'],
            'periods.*.total_fee'    => ['required', 'numeric', 'min:0'],
            'periods.*.paid_amount'  => ['required', 'numeric', 'min:0'],
            'periods.*.paid_date'    => ['nullable', 'date', 'before_or_equal:today'],
            'periods.*.session_id'   => ['nullable', 'integer'],
        ], [
            'periods.required'                => 'No fee-history data was submitted. Please fill in the form and try again.',
            'periods.*.total_fee.required'    => 'Please enter a Total Fee for every semester listed below.',
            'periods.*.total_fee.numeric'     => 'Total Fee must be a valid number.',
            'periods.*.total_fee.min'         => 'Total Fee cannot be negative.',
            'periods.*.paid_amount.required'  => 'Please enter an Amount Paid for every semester listed below (enter 0 if nothing was paid).',
            'periods.*.paid_amount.numeric'   => 'Amount Paid must be a valid number.',
            'periods.*.paid_amount.min'       => 'Amount Paid cannot be negative.',
            'periods.*.paid_date.date'        => 'Paid Date must be a valid date.',
            'periods.*.paid_date.before_or_equal' => 'Paid Date cannot be in the future.',
        ]);

        $instituteSessionIds = $this->sessionsForInstitute((int) $student->institute_id)->pluck('id')->flip();

        // Amount Paid can never exceed its own Total Fee for that semester, and a
        // semester with any fee at all must have a confirmed academic session (its
        // opening balance is recognized against that session's own books) — both
        // checked here (not as Laravel rules) so the error message names the exact
        // semester at fault instead of a generic "invalid input".
        $periodResults = [];
        foreach ($periods as $period) {
            $n = $period['period_number'];
            $row = $validated['periods'][$n] ?? null;
            if (!$row) {
                return back()->withErrors([
                    'periods' => "Missing fee-history entry for {$period['label']}. Please fill in every semester listed below and try again.",
                ])->withInput();
            }

            $totalFee = round((float) $row['total_fee'], 2);
            $paidAmount = round((float) $row['paid_amount'], 2);

            if ($paidAmount > $totalFee) {
                return back()->withErrors([
                    'periods' => "Amount Paid for {$period['label']} (₹" . number_format($paidAmount, 2)
                        . ") cannot be more than its Total Fee (₹" . number_format($totalFee, 2) . "). Please correct it and try again.",
                ])->withInput();
            }

            $sessionId = null;
            if ($totalFee > 0) {
                $sessionId = (int) ($row['session_id'] ?? 0);
                if ($sessionId <= 0 || !isset($instituteSessionIds[$sessionId])) {
                    return back()->withErrors([
                        'periods' => "Please select which academic session {$period['label']} belongs to (or create it under Master → Academic Sessions first) before approving.",
                    ])->withInput();
                }
            }

            $periodResults[$n] = [
                'label'       => $period['label'],
                'total_fee'   => $totalFee,
                'paid_amount' => $paidAmount,
                'due'         => round($totalFee - $paidAmount, 2),
                'paid_date'   => $row['paid_date'] ?? null,
                'session_id'  => $sessionId,
                'items'       => $period['suggested_items'],
            ];
        }

        return $this->finalizeApproval($student, $periodResults);
    }

    // ── Reject: a bulk-imported row that turned out to be wrong (duplicate,
    // bad data, etc.) is cancelled instead of approved — same convention already
    // used for a rejected online admission (see AdmissionController::updateApprovalStatus()):
    // status becomes "cancelled" with a recorded reason, nothing is deleted, and any
    // current-semester fee already charged at import time is left as-is for the audit
    // trail (a cancelled student is excluded from every active list/report already). ──
    public function reject(Request $request, Student $student)
    {
        $this->ensureAccess();
        $this->ensureBulkImportPendingStudent($student);
        $this->ensureStaffCanReviewStudent($student);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'Please explain why this student is being rejected (e.g. duplicate row, wrong data) — this is kept for the audit trail.',
            'reason.min'      => 'Please enter a more descriptive reason (at least 3 characters).',
            'reason.max'      => 'Reason cannot be longer than 500 characters.',
        ]);

        // Lock + re-check status under the lock — guards against a race with a
        // concurrent Approve (or a second Reject double-click) on the exact same row.
        $wasRejected = DB::transaction(function () use ($student, $validated) {
            $locked = Student::where('id', $student->id)->lockForUpdate()->first();
            if (!$locked || $locked->status !== 'pending') {
                return false;
            }

            $locked->update([
                'status'        => 'cancelled',
                'status_reason' => $validated['reason'],
            ]);
            return true;
        });

        if (!$wasRejected) {
            return redirect()->route('admissions.bulk-import.pending.index')
                ->withErrors(['reason' => "{$student->name} was already reviewed (possibly by someone else just now) — nothing was changed."]);
        }

        AuditLogService::log(
            $this->instituteId(),
            'admission',
            'bulk_import_rejected',
            "Bulk-imported student {$student->name} ({$student->student_uid}) rejected: {$validated['reason']}",
            $student,
            ['student_id' => $student->id, 'reason' => $validated['reason']]
        );

        return redirect()->route('admissions.bulk-import.pending.index')
            ->with('success', "{$student->name} has been rejected and marked Cancelled.");
    }

    // ── Scale the suggested fee items so they sum to $targetTotal — needed when the
    // reviewer overrides Total Fee away from the FeeCalculatorService-suggested amount
    // (e.g. they know the real historical fee was different). Falls back to a single
    // catch-all item if there is nothing to scale (no items, or they summed to zero),
    // so an overridden amount is never silently dropped instead of invoiced.
    //
    // Every item's label gets this period tagged onto it (e.g. "Registration Fee
    // (Semester 1 — Bulk Import)", not bare "Registration Fee") — WalletService's
    // pending-fee computation matches "already paid" back to "charged" purely by this
    // label string, session-scoped; a bare generic label could otherwise collide with
    // the student's CURRENT semester's own same-named fee item when the historical
    // invoice's session is queried (that session has no reliable promotion trail to
    // resolve the RIGHT historical semester for FeeCalculatorService, since a
    // bulk-imported student has no PromotionLog history), silently netting one
    // semester's payment against a completely different semester's charge. ─────────
    private function scaleItemsToTotal(array $items, float $targetTotal, string $periodLabel): array
    {
        $items = array_values(array_filter($items, fn($i) => (float) ($i['amount'] ?? 0) > 0));
        $sum = array_sum(array_column($items, 'amount'));

        if (empty($items) || $sum <= 0) {
            if ($targetTotal <= 0) {
                return [];
            }
            return [[
                'type'        => 'other',
                'fee_type_id' => null,
                'label'       => "Bulk Import Fee — {$periodLabel}",
                'subject_id'  => null,
                'amount'      => round($targetTotal, 2),
            ]];
        }

        $ratio = $targetTotal / $sum;
        return array_map(function ($item) use ($ratio, $periodLabel) {
            $item['amount'] = round(((float) $item['amount']) * $ratio, 2);
            $item['label'] = ($item['label'] ?? 'Fee') . " ({$periodLabel} — Bulk Import)";
            return $item;
        }, $items);
    }

    // ── Sequential ("waterfall") fill — the same principle the Fee Collection page's
    // own "One Time Pay" uses: fill each item in order up to its own (possibly scaled)
    // amount, move to the next once it is fully covered, until $paidAmount runs out. ──
    private function sequentialFill(array $items, float $paidAmount): array
    {
        $remaining = round($paidAmount, 2);
        $result = [];
        foreach ($items as $item) {
            if ($remaining <= 0) {
                break;
            }
            $cap = round((float) ($item['amount'] ?? 0), 2);
            if ($cap <= 0) {
                continue;
            }
            $take = min($remaining, $cap);
            $result[] = [
                'fee_type_id' => $item['fee_type_id'] ?? null,
                'subject_id'  => $item['subject_id'] ?? null,
                'item_type'   => $item['type'] ?? 'other',
                'fee_name'    => $item['label'] ?? 'Fee',
                'amount'      => $take,  // collected in this receipt
                'total_fee'   => $cap,   // this head's own (possibly scaled) full amount
                'discount'    => 0.0,
                'fine'        => 0.0,
            ];
            $remaining -= $take;
        }
        return $result;
    }

    // ── Create a real, receipt-able FeeInvoice for the PAID portion of one past
    // semester, head-wise distributed, dated against the semester's own CONFIRMED
    // historical academic session (so its StudentWallet/InstituteWallet updates land
    // in that session, not the student's current one). The normal "Fees Receivable
    // cleared" journal entry now posts normally — safe because finalizeApproval()
    // always recognizes the full period as a receivable first (see
    // JournalService::safePostBulkImportOpeningBalance()), so there is a matching
    // debit for it to clear. ──
    private function createHistoricalInvoice(Student $student, int $periodNumber, array $result, string $actorName): ?FeeInvoice
    {
        $paidAmount = (float) $result['paid_amount'];
        if ($paidAmount <= 0) {
            return null;
        }

        $scaledItems = $this->scaleItemsToTotal($result['items'], (float) $result['total_fee'], $result['label']);
        $validItems = $this->sequentialFill($scaledItems, $paidAmount);
        if (empty($validItems)) {
            return null;
        }

        $sessionId = (int) ($result['session_id'] ?? $student->academic_session_id);
        $sessionName = AcademicSession::find($sessionId)?->name;
        $year = $sessionName ? StudentIdService::getYearFromSession($sessionName) : (int) now()->format('Y');

        $invoiceNo = StudentIdService::generateInvoiceId((int) $student->institute_id, $year);
        $paymentDate = $result['paid_date'] ?: now()->toDateString();

        $invoice = FeeInvoice::create([
            'institute_id'          => $student->institute_id,
            'student_id'            => $student->id,
            'academic_session_id'   => $sessionId,
            'semester'              => $periodNumber,
            'invoice_no'            => $invoiceNo,
            'total_amount'          => $paidAmount,
            'discount'              => 0,
            'paid_amount'           => $paidAmount,
            'payment_mode'          => 'cash',
            'payment_date'          => $paymentDate,
            'payment_datetime'      => now(),
            'remarks'               => "Imported via Bulk Excel — historical {$result['label']} fee record",
            'collected_by'          => $actorName,
            'collected_by_staff_id' => Auth::guard('staff')->check() ? Auth::guard('staff')->id() : null,
            'approval_status'       => FeeInvoice::STATUS_APPROVED,
            'remaining_due'         => (float) $result['due'],
        ]);

        WalletService::settleApprovedInvoice($invoice, $validItems, postJournal: true);

        return $invoice;
    }

    // ── Shared finish step for both the "nothing to review" fast path and the
    // full review-form submission. ──────────────────────────────────────────
    private function finalizeApproval(Student $student, array $periodResults)
    {
        $instituteId = $this->instituteId();

        $actor = null;
        foreach (['web', 'staff'] as $guard) {
            if (auth()->guard($guard)->check()) {
                $actor = auth()->guard($guard)->user();
                break;
            }
        }
        $actorName = $actor?->name ?? $actor?->email ?? 'Institute Admin';

        $invoicesCreated = 0;
        $totalDueRecorded = 0.0;
        $wasApproved = false;

        DB::transaction(function () use ($student, $periodResults, $actorName, &$invoicesCreated, &$totalDueRecorded, &$wasApproved) {
            // Lock + re-check status under the lock — guards against a race with a
            // concurrent Reject (or a second Approve double-click) on the exact same
            // row: only one of them should ever actually charge/invoice anything.
            $locked = Student::where('id', $student->id)->lockForUpdate()->first();
            if (!$locked || $locked->status !== 'pending') {
                return;
            }

            foreach ($periodResults as $n => $result) {
                $totalFee = (float) ($result['total_fee'] ?? 0);

                // Recognize the FULL period (paid + due combined) as a receivable against
                // the "Opening Balance — Migrated Fees" equity account FIRST — this is
                // what keeps the current period's Profit & Loss unaffected by historical
                // data, and gives the paid-portion invoice below a matching debit to clear.
                if ($totalFee > 0) {
                    JournalService::safePostBulkImportOpeningBalance(
                        $student,
                        $n,
                        $result['label'],
                        $totalFee,
                        (int) ($result['session_id'] ?? $student->academic_session_id)
                    );
                }

                $invoice = $this->createHistoricalInvoice($student, $n, $result, $actorName);
                if ($invoice) {
                    $invoicesCreated++;
                }

                if (($result['due'] ?? 0) > 0) {
                    $due = (float) $result['due'];

                    // Charged into the student's CURRENT session (not the semester's own
                    // historical one) so it is actually collectible today — the Fee Collection
                    // page only ever looks at the student's current academic_session_id, never
                    // a past one. The historical invoice's own "Remaining Due" is shown
                    // correctly on its receipt from the FeeInvoice.remaining_due value set
                    // below, not by also duplicating this debit into the old session (which
                    // would make the same due appear to exist twice across two session tabs).
                    WalletService::chargeBulkImportPreviousDue($student, $n, $due);
                    $totalDueRecorded += $due;
                }
            }

            $locked->update([
                'status'               => $locked->bulk_import_target_status ?? 'active',
                'approved_by_staff_id' => Auth::guard('staff')->check() ? Auth::guard('staff')->id() : null,
                'approved_by_name'     => $actorName,
                'approved_at'          => now(),
            ]);
            $wasApproved = true;
        });

        if (!$wasApproved) {
            return redirect()->route('admissions.bulk-import.pending.index')
                ->withErrors(['periods' => "{$student->name} was already reviewed (possibly by someone else just now) — nothing was changed."]);
        }

        $student->refresh();

        AuditLogService::log(
            $instituteId,
            'admission',
            'bulk_import_fee_history_approved',
            "Fee history approved for bulk-imported student {$student->name} ({$student->student_uid}).",
            $student,
            [
                'student_id'        => $student->id,
                'invoices_created'  => $invoicesCreated,
                'total_due_recorded' => $totalDueRecorded,
                'final_status'      => $student->status,
            ]
        );

        $friendlyStatus = ucfirst(str_replace('_', ' ', $student->status));
        $msg = "{$student->name}'s fee history has been approved. The student is now {$friendlyStatus}.";
        if ($invoicesCreated > 0) {
            $msg .= " {$invoicesCreated} receipt(s) generated for the amount already paid.";
        }
        if ($totalDueRecorded > 0) {
            $msg .= " ₹" . number_format($totalDueRecorded, 2) . " recorded as an outstanding due on the wallet.";
        }

        return redirect()->route('admissions.bulk-import.pending.index')->with('success', $msg);
    }
}
