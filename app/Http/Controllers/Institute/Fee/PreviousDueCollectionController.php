<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\FeeInvoice;
use App\Models\InstituteBankAccount;
use App\Models\InstituteIncomeCategory;
use App\Models\InstituteManualIncome;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\InstituteWalletService;
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Lets staff collect a leftover fee due from a NON-active student (Passed Out, Detained,
// Transferred, Cancelled) — the regular Fee Collection page (FeeCollectionController)
// refuses this outright for any non-active status, and its own suggested alternative
// ("clear it through the wallet") does not actually exist anywhere: WalletController's
// student-wallet page is read-only, and there is no other route in the app that settles
// a student's due. This is a separate, additive flow — it never touches
// FeeCollectionController, so the everyday Collect Fee page is completely unaffected.
//
// The collected amount still credits the STUDENT's own wallet + gets a real FeeInvoice
// (receipt) exactly like a normal collection, and the underlying accounting-books
// treatment is unchanged (the receivable this clears was already properly recognized
// earlier — at original admission for a normally-promoted student, or via the
// "Opening Balance — Migrated Fees" equity account for a Bulk Import student — so
// collecting it now is just "Debit Cash, Credit Fees Receivable", same as any other
// collection). What IS different is the INSTITUTE wallet side: instead of the generic
// "Fee received: ..." line a normal collection writes, this is recorded as a
// categorized InstituteManualIncome entry ("Previous Due Collection") — so it shows up,
// filterable, on the existing Manual Income Entries page, distinct from ordinary
// current fee income.
class PreviousDueCollectionController extends Controller
{
    private const NON_ACTIVE_STATUSES = ['passed_out', 'detained', 'transferred', 'cancelled', 'inactive'];
    private const MANUAL_INCOME_CATEGORY = 'Previous Due Collection';
    private const PAYMENT_MODES = ['cash', 'online', 'cheque', 'dd', 'upi', 'neft', 'rtgs'];

    // ── Resolve institute_id for any guard ──────────────────────────────
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

    private function currentStaff(): ?\App\Models\StaffMember
    {
        return Auth::guard('staff')->user();
    }

    private function actorName(): string
    {
        foreach (['staff', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                $user = auth()->guard($guard)->user();
                return $user?->name ?? $user?->email ?? 'Institute Admin';
            }
        }
        return 'Institute Admin';
    }

    private function actorId(): ?int
    {
        foreach (['staff', 'web'] as $guard) {
            $id = auth()->guard($guard)->id();
            if ($id !== null) return (int) $id;
        }
        return null;
    }

    // Redirecting back to a hardcoded 'fee.previous-dues.index' would still technically
    // work for a staff-authenticated request (the institute-owner route isn't
    // guard-restricted), but it would land them on the institute panel's URL instead of
    // their own staff panel's — this keeps the redirect on whichever panel the request
    // actually came in through, matching AdmissionController::admissionRoute()'s pattern.
    private function indexRouteName(): string
    {
        return Auth::guard('staff')->check() ? 'staff.fee.previous-dues.index' : 'fee.previous-dues.index';
    }

    // ── Permission gate — no-op for the institute-owner (web) guard. Deliberately a
    // separate permission from ordinary fee collection — see StaffMember::canCollectPreviousDues(). ──
    private function ensureAccess(): void
    {
        $staff = $this->currentStaff();
        if (!$staff) return;
        abort_if(!$staff->canCollectPreviousDues(), 403, 'You do not have permission to collect previous dues from non-active students. Please contact an administrator.');
    }

    private function ensureStaffCanAccessStudent(Student $student): void
    {
        $staff = $this->currentStaff();
        if ($staff) {
            abort_if(!$staff->canAccessStudentForOperations($student), 403, 'This student is outside your assigned course/stream access scope.');
        }
    }

    private function ensureNonActiveStudent(Student $student): void
    {
        abort_if($student->institute_id !== $this->instituteId(), 403, 'This student does not belong to your institute.');
        abort_unless(in_array($student->status, self::NON_ACTIVE_STATUSES, true), 404,
            'This student is Active (or Pending) — use the regular Collect Fee page for them instead.');
    }

    // ── List: non-active students, with their outstanding due ───────────
    public function index(Request $request)
    {
        $this->ensureAccess();
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();

        $query = Student::with(['stream.course', 'session'])
            ->where('institute_id', $instituteId)
            ->whereIn('status', self::NON_ACTIVE_STATUSES);

        if ($staff = $this->currentStaff()) {
            $staff->scopeOperationalStudents($query);
        }

        if ($request->filled('session_id')) {
            $query->where('academic_session_id', (int) $request->session_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('student_uid', 'like', "%{$search}%")
                    ->orWhere('enrollment_no', 'like', "%{$search}%")
                    ->orWhere('roll_no', 'like', "%{$search}%")
                    ->orWhere('uin_no', 'like', "%{$search}%");
            });
        }

        // Due isn't a DB column — it's computed live per student via buildPendingRows(),
        // the same function every other due/receipt figure in the app already uses. The
        // non-active list for one institute is a bounded, moderate-sized set (unlike the
        // full student roster), so computing it for the whole filtered set before sorting
        // and paginating in PHP is fine here.
        $allMatching = $query->orderByDesc('id')->get();
        $allMatching->each(function (Student $student) {
            $student->computed_due = round((float) WalletService::buildPendingRows(
                $student, (int) $student->academic_session_id
            )->sum('pending'), 2);
        });

        $sorted = $allMatching->sortByDesc('computed_due')->values();

        $perPage = 30;
        $page = max(1, (int) $request->input('page', 1));
        $students = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('institute.fee.previous-due-index', compact('students', 'sessions'));
    }

    // ── Per-student collect page ─────────────────────────────────────────
    public function show(Student $student)
    {
        $this->ensureAccess();
        $this->ensureNonActiveStudent($student);
        $this->ensureStaffCanAccessStudent($student);

        $student->load(['stream.course', 'coursePart', 'session']);
        $pendingRows = $this->pendingRows($student);

        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('institute.fee.previous-due-collect', compact('student', 'pendingRows', 'bankAccounts'));
    }

    private function pendingRows(Student $student)
    {
        return WalletService::buildPendingRows($student, (int) $student->academic_session_id)
            ->filter(fn($row) => (float) $row['pending'] > 0)
            ->values();
    }

    // ── Collect: create a real receipt, clear the student's own due, and record the
    // institute-side income as a categorized Manual Income entry. ──────────
    public function store(Request $request, Student $student)
    {
        $this->ensureAccess();
        $this->ensureNonActiveStudent($student);
        $this->ensureStaffCanAccessStudent($student);

        $pendingRows = $this->pendingRows($student)->keyBy('name');
        if ($pendingRows->isEmpty()) {
            return redirect()->route($this->indexRouteName())
                ->withErrors(['items' => "{$student->name} has no outstanding due left to collect."]);
        }

        $validated = $request->validate([
            'payment_mode'     => ['required', 'in:' . implode(',', self::PAYMENT_MODES)],
            'payment_date'     => ['required', 'date', 'before_or_equal:today'],
            'transaction_ref'  => ['nullable', 'string', 'max:100'],
            'bank_account_id'  => ['nullable', 'integer'],
            'items'            => ['required', 'array'],
            'items.*.collect'  => ['required', 'numeric', 'min:0'],
            'items.*.fine'     => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'payment_mode.required'    => 'Please select a payment mode.',
            'payment_mode.in'          => 'Selected payment mode is invalid.',
            'payment_date.required'    => 'Payment date is required.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'items.required'           => 'No fee items were submitted. Please fill in the form and try again.',
            'items.*.collect.required' => 'Please enter an amount for every listed due (0 if you are only adding a fine).',
            'items.*.collect.numeric'  => 'Collect amount must be a valid number.',
            'items.*.collect.min'      => 'Collect amount cannot be negative.',
            'items.*.fine.numeric'     => 'Fine must be a valid number.',
            'items.*.fine.min'         => 'Fine cannot be negative.',
            'items.*.discount.numeric' => 'Discount must be a valid number.',
            'items.*.discount.min'     => 'Discount cannot be negative.',
        ]);

        if ($validated['payment_mode'] !== 'cash' && empty($validated['transaction_ref'])) {
            return back()->withErrors(['transaction_ref' => 'Transaction Ref / UTR / Cheque No. is required for non-cash payments.'])->withInput();
        }

        $validItems = [];
        $totalCollect = 0.0;
        $totalFine = 0.0;
        $totalDiscount = 0.0;

        foreach ($validated['items'] as $name => $row) {
            $pendingRow = $pendingRows->get($name);
            if (!$pendingRow) {
                // Stale row from a page the student's due changed under since it was
                // loaded (e.g. collected from a second tab) — skip it rather than fail
                // the whole submission; the other, still-valid rows still go through.
                continue;
            }

            $collect  = round((float) ($row['collect'] ?? 0), 2);
            $fine     = round((float) ($row['fine'] ?? 0), 2);
            $discount = round((float) ($row['discount'] ?? 0), 2);
            if ($collect <= 0 && $fine <= 0 && $discount <= 0) {
                continue;
            }

            $payableCap = round((float) $pendingRow['pending'] + $fine, 2);
            if (($collect + $discount) - $payableCap > 0.01) {
                return back()->withErrors([
                    'items' => "Amount for \"{$name}\" (₹" . number_format($collect + $discount, 2)
                        . ") cannot exceed its outstanding due plus fine (₹" . number_format($payableCap, 2) . "). Please correct it and try again.",
                ])->withInput();
            }

            $validItems[] = [
                'fee_name'  => $name,
                'amount'    => $collect,
                'discount'  => $discount,
                'fine'      => $fine,
                'total_fee' => round((float) $pendingRow['charged'], 2),
            ];
            $totalCollect  += $collect;
            $totalFine     += $fine;
            $totalDiscount += $discount;
        }

        if (empty($validItems)) {
            return back()->withErrors(['items' => 'Please enter a Collect, Fine or Discount amount for at least one due before submitting.'])->withInput();
        }

        $instituteId = $this->instituteId();
        $sessionId = (int) $student->academic_session_id;
        $paidAmount = round($totalCollect + $totalFine, 2);
        $totalAmount = round($paidAmount + $totalDiscount, 2);
        $actorName = $this->actorName();
        $paymentDate = $validated['payment_date'];

        $bankAccount = null;
        if ($validated['payment_mode'] !== 'cash' && !empty($validated['bank_account_id'])) {
            $bankAccount = InstituteBankAccount::where('id', $validated['bank_account_id'])
                ->where('institute_id', $instituteId)
                ->where('is_active', true)
                ->first();
        }

        $year = StudentIdService::getYearFromSession($student->session?->name ?? (string) now()->format('Y'));
        $invoiceNo = StudentIdService::generateInvoiceId($instituteId, $year);

        try {
            $invoice = DB::transaction(function () use (
                $student, $validItems, $instituteId, $sessionId, $paidAmount, $totalAmount,
                $totalDiscount, $totalCollect, $totalFine, $actorName, $paymentDate,
                $validated, $bankAccount, $invoiceNo
            ) {
                // Lock the student row — serializes concurrent "collect due" submissions
                // for the SAME student (double-click, two open tabs) into a strict queue,
                // so the fresh-pending re-check right below always sees the true,
                // up-to-date state rather than racing another in-flight collection
                // against the same due.
                $lockedStudent = Student::where('id', $student->id)->lockForUpdate()->first();

                $freshPending = $this->pendingRows($lockedStudent)->keyBy('name');
                foreach ($validItems as $item) {
                    $fresh = $freshPending->get($item['fee_name']);
                    $freshPendingAmount = $fresh ? (float) $fresh['pending'] : 0.0;
                    if (($item['amount'] + $item['discount']) - ($freshPendingAmount + $item['fine']) > 0.01) {
                        throw new \RuntimeException(
                            "The due for \"{$item['fee_name']}\" changed since this page was loaded — it may have already been collected (e.g. from another tab). Please refresh and try again."
                        );
                    }
                }

                $invoice = FeeInvoice::create([
                    'institute_id'          => $instituteId,
                    'student_id'            => $student->id,
                    'academic_session_id'   => $sessionId,
                    'semester'              => (int) ($student->current_semester ?: 1),
                    'invoice_no'            => $invoiceNo,
                    'total_amount'          => $totalAmount,
                    'discount'              => $totalDiscount,
                    'paid_amount'           => $paidAmount,
                    'payment_mode'          => $validated['payment_mode'],
                    'bank_account_id'       => $bankAccount?->id,
                    'transaction_ref'       => $validated['transaction_ref'] ?? null,
                    'payment_date'          => $paymentDate,
                    'payment_datetime'      => now(),
                    'remarks'               => "Previous due collected — {$student->name} was {$student->status} when this was cleared.",
                    'collected_by'          => $actorName,
                    'collected_by_staff_id' => Auth::guard('staff')->check() ? Auth::guard('staff')->id() : null,
                    'approval_status'       => FeeInvoice::STATUS_APPROVED,
                ]);

                // postJournal stays TRUE — the receivable this clears was already properly
                // recognized earlier (at original admission, or via the "Opening Balance —
                // Migrated Fees" account for a Bulk Import student), so this collection's
                // own journal entry (Debit Cash, Credit Fees Receivable) is the normal,
                // correct one. creditInstituteWallet is FALSE — the institute side is
                // credited below instead, as a categorized Manual Income entry, not the
                // generic "Fee received: ..." line.
                WalletService::settleApprovedInvoice($invoice, $validItems, postJournal: true, creditInstituteWallet: false);

                $category = InstituteIncomeCategory::firstOrCreate(
                    ['institute_id' => $instituteId, 'name' => self::MANUAL_INCOME_CATEGORY],
                    ['is_active' => true]
                );

                if ($totalCollect + $totalFine > 0) {
                    $manualIncome = InstituteManualIncome::create([
                        'institute_id'        => $instituteId,
                        'academic_session_id' => $sessionId,
                        'income_category_id'  => $category->id,
                        'amount'              => round($totalCollect + $totalFine, 2),
                        'date'                => $paymentDate,
                        'receipt_no'          => $invoiceNo,
                        'description'         => "{$student->name} ({$student->student_uid}) — {$student->status} student, previous due cleared",
                        'created_by'          => $this->actorId(),
                    ]);
                    $manualIncome->setRelation('category', $category);
                    InstituteWalletService::creditManualIncome($manualIncome);
                }

                return $invoice;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        AuditLogService::log(
            $instituteId,
            'fee',
            'previous_due_collected',
            "Previous due of ₹" . number_format($paidAmount, 2) . " collected from {$student->name} ({$student->student_uid}), a {$student->status} student.",
            $invoice,
            [
                'student_id'   => $student->id,
                'invoice_no'   => $invoiceNo,
                'paid_amount'  => $paidAmount,
                'discount'     => $totalDiscount,
                'fine'         => $totalFine,
            ]
        );

        return redirect()->route($this->indexRouteName())
            ->with('success', "₹" . number_format($paidAmount, 2) . " collected from {$student->name}. Receipt {$invoiceNo} generated.");
    }
}
