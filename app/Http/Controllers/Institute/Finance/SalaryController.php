<?php

namespace App\Http\Controllers\Institute\Finance;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Account;
use App\Models\EmployeeSalaryDisbursement;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Models\SalaryRecord;
use App\Models\StaffMember;
use App\Services\AccountingSetupService;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SalaryController extends Controller
{
    use HasInstituteId;

    private function ensureFinanceTablesReady(): ?RedirectResponse
    {
        foreach (['accounts', 'journal_entries', 'salary_records'] as $table) {
            if (!Schema::hasTable($table)) {
                return redirect()
                    ->route('institute.dashboard')
                    ->with('error', 'Salary module has not been migrated yet. Please run the finance migrations first.');
            }
        }

        return null;
    }

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();

        $staffRecords = SalaryRecord::with(['staffMember.role', 'expenseAccount', 'bankAccount'])
            ->where('institute_id', $instituteId)
            ->get()
            ->map(function (SalaryRecord $record) {
                $totalDeductions = round(
                    (float) $record->deductions
                    + (float) $record->absence_deduction
                    + (float) $record->pf_employee
                    + (float) $record->esi_employee
                    + (float) $record->tds
                    + (float) $record->professional_tax
                    + (float) $record->loan_deduction,
                    2
                );

                return (object) [
                    'type'          => 'staff',
                    'id'            => $record->id,
                    'year'          => (int) $record->salary_year,
                    'month'         => (int) $record->salary_month,
                    'payment_date'  => $record->payment_date,
                    'name'          => $record->staffMember?->name ?? '—',
                    'sub_label'     => $record->staffMember?->role?->name ?? 'Staff',
                    'expense_head'  => $record->expenseAccount,
                    'gross'         => round((float) $record->net_payable + $totalDeductions, 2),
                    'deductions'    => $totalDeductions,
                    'net'           => (float) $record->net_payable,
                    'payment_mode'  => $record->payment_mode,
                    'bank_account'  => $record->bankAccount,
                    'status'        => $record->status,
                    'journal_entry_id'          => $record->journal_entry_id,
                    'reversal_journal_entry_id' => $record->reversal_journal_entry_id,
                    'pay_route'     => !in_array($record->status, [SalaryRecord::STATUS_PAID, SalaryRecord::STATUS_REVERSED], true)
                        ? route('finance.salary.pay', $record->id) : null,
                    'reverse_route' => $record->status === SalaryRecord::STATUS_PAID
                        ? route('finance.salary.reverse', $record->id) : null,
                    'retry_route'   => ($record->status === SalaryRecord::STATUS_PAID && !$record->journal_entry_id)
                        ? route('finance.salary.retry-posting', $record->id) : null,
                    'profile_route' => null,
                ];
            });

        $employeeRecords = EmployeeSalaryDisbursement::with(['employee.department', 'employee.designation', 'expenseAccount', 'bankAccount'])
            ->where('institute_id', $instituteId)
            ->get()
            ->map(function (EmployeeSalaryDisbursement $record) {
                $subLabel = collect([$record->employee?->department?->name, $record->employee?->designation?->name])
                    ->filter()
                    ->join(' · ');

                return (object) [
                    'type'          => 'employee',
                    'id'            => $record->id,
                    'year'          => (int) $record->year,
                    'month'         => (int) $record->month,
                    'payment_date'  => $record->payment_date,
                    'name'          => $record->employee?->name ?? '—',
                    'sub_label'     => $subLabel ?: 'Employee',
                    'expense_head'  => $record->expenseAccount,
                    'gross'         => (float) $record->gross_salary,
                    'deductions'    => (float) $record->deductions,
                    'net'           => (float) $record->net_salary,
                    'payment_mode'  => $record->payment_mode,
                    'bank_account'  => $record->bankAccount,
                    'status'        => $record->status,
                    'journal_entry_id'          => $record->journal_entry_id,
                    'reversal_journal_entry_id' => $record->reversal_journal_entry_id,
                    'pay_route'     => null,
                    'reverse_route' => $record->status === 'paid' && $record->employee_id
                        ? route('employees.salary.reverseForm', [$record->employee_id, $record->id]) : null,
                    'retry_route'   => null,
                    'profile_route' => $record->employee_id
                        ? route('employees.salary.disbursements', $record->employee_id) : null,
                ];
            });

        $salaryRecords = $staffRecords->concat($employeeRecords)
            ->sort(fn ($a, $b) => [$b->year, $b->month, $b->id] <=> [$a->year, $a->month, $a->id])
            ->values();

        $totalPayable = round((float) $salaryRecords->sum('gross'), 2);
        $totalPaid    = round((float) $salaryRecords->where('status', 'paid')->sum('net'), 2);
        $pendingCount = $salaryRecords->whereNotIn('status', ['paid', 'reversed'])->count();

        return view('institute.finance.salary.index', compact(
            'salaryRecords',
            'totalPayable',
            'totalPaid',
            'pendingCount'
        ));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $staffMembers = StaffMember::with('role')
            ->where('institute_id', $instituteId)
            ->where('status', true)
            ->orderBy('name')
            ->get();

        $expenseAccounts = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $settings = FinanceSetting::where('institute_id', $instituteId)->first();

        return view('institute.finance.salary.create', compact(
            'staffMembers',
            'expenseAccounts',
            'bankAccounts',
            'sessions',
            'settings'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'staff_member_id' => 'required|integer',
            'academic_session_id' => 'nullable|integer',
            'salary_month' => 'required|integer|min:1|max:12',
            'salary_year' => 'required|integer|min:2000|max:2100',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'expense_account_id' => 'required|integer',
            'payment_mode' => 'nullable|in:cash,bank',
            'bank_account_id' => 'nullable|integer|required_if:payment_mode,bank',
            'payment_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $staffMember    = StaffMember::where('institute_id', $instituteId)->findOrFail((int) $validated['staff_member_id']);
        $expenseAccount = Account::where('institute_id', $instituteId)->where('type', 'expense')->findOrFail((int) $validated['expense_account_id']);

        $existing = SalaryRecord::where('institute_id', $instituteId)
            ->where('staff_member_id', $staffMember->id)
            ->where('salary_month', (int) $validated['salary_month'])
            ->where('salary_year', (int) $validated['salary_year'])
            ->first();

        if ($existing) {
            return back()->withInput()->with('error',
                "A salary record for {$staffMember->name} for month {$validated['salary_month']}/{$validated['salary_year']} already exists (Status: {$existing->status}). A duplicate cannot be created."
            );
        }

        $basic = round((float) $validated['basic_salary'], 2);
        $allowances = round((float) ($validated['allowances'] ?? 0), 2);
        $deductions = round((float) ($validated['deductions'] ?? 0), 2);
        $netPayable = round($basic + $allowances - $deductions, 2);

        $paymentMode = $validated['payment_mode'] ?? null;
        $paymentDate = $validated['payment_date'] ?? null;
        $paymentAccountId = null;
        $bankAccountId = null;

        if ($paymentDate && $paymentMode === 'cash') {
            $settings = FinanceSetting::where('institute_id', $instituteId)->first();
            $paymentAccountId = $settings?->cash_account_id;
            abort_if(!$paymentAccountId, 422, 'Cash account mapping missing in finance settings.');
        }

        if ($paymentDate && $paymentMode === 'bank') {
            $bankAccount = InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->findOrFail((int) $validated['bank_account_id']);
            $bankAccountId = $bankAccount->id;
            $paymentAccountId = $bankAccount->gl_account_id;
            abort_if(!$paymentAccountId, 422, 'Selected bank account GL mapping is missing.');
        }

        // Always create as Draft first — payment (status/journal/wallet-debit) is applied
        // afterwards via PayrollService::markSalaryPaid(), the same atomic path the "pay"
        // screen uses, so a directly-paid record can never skip the wallet debit.
        $salaryRecord = SalaryRecord::create([
            'institute_id' => $instituteId,
            'academic_session_id' => !empty($validated['academic_session_id']) ? (int) $validated['academic_session_id'] : null,
            'staff_member_id' => (int) $staffMember->id,
            'expense_account_id' => (int) $expenseAccount->id,
            'salary_month' => (int) $validated['salary_month'],
            'salary_year' => (int) $validated['salary_year'],
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_payable' => $netPayable,
            'paid_amount' => 0,
            'remarks' => $validated['remarks'] ?? null,
            'status' => SalaryRecord::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        if (!$paymentDate) {
            return redirect()
                ->route('finance.salary.index')
                ->with('success', 'Salary record created. You can now process the payment from the pay screen.');
        }

        try {
            PayrollService::markSalaryPaid(
                $salaryRecord, $paymentDate, $paymentMode, $validated['remarks'] ?? null,
                $paymentAccountId, $bankAccountId
            );
        } catch (InsufficientWalletBalanceException $e) {
            return redirect()->route('finance.salary.index')->with('error',
                'Salary record created (Draft), but wallet balance was insufficient (Available: Rs '
                . number_format($e->available, 2) . '). Try the payment again from the pay screen.');
        } catch (\RuntimeException $e) {
            return redirect()->route('finance.salary.index')->with('error',
                'Salary record created (Draft), but payment could not be posted: ' . $e->getMessage());
        }

        return redirect()
            ->route('finance.salary.index')
            ->with('success', 'Salary record created and payment posted.');
    }

    public function pay(SalaryRecord $salaryRecord): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);

        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $settings = FinanceSetting::where('institute_id', $this->instituteId())->first();

        return view('institute.finance.salary.pay', compact('salaryRecord', 'bankAccounts', 'settings'));
    }

    public function markPaid(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        abort_if($salaryRecord->status === SalaryRecord::STATUS_REVERSED, 422, 'Reversed salary record cannot be paid again.');
        abort_if($salaryRecord->journal_entry_id, 422, 'Salary already posted.');

        $validated = $request->validate([
            'payment_mode' => 'required|in:cash,bank',
            'bank_account_id' => 'nullable|integer|required_if:payment_mode,bank',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $paymentAccountId = null;
        $bankAccountId = null;

        if ($validated['payment_mode'] === 'cash') {
            $settings = FinanceSetting::where('institute_id', $this->instituteId())->first();
            $paymentAccountId = $settings?->cash_account_id;
            abort_if(!$paymentAccountId, 422, 'Cash account mapping missing in finance settings.');
        } else {
            $bankAccount = InstituteBankAccount::where('institute_id', $this->instituteId())
                ->where('is_active', true)
                ->findOrFail((int) $validated['bank_account_id']);
            $bankAccountId = $bankAccount->id;
            $paymentAccountId = $bankAccount->gl_account_id;
            abort_if(!$paymentAccountId, 422, 'Selected bank account GL mapping is missing.');
        }

        // Status update + journal posting + wallet debit happen atomically inside
        // PayrollService::markSalaryPaid() (single DB transaction, locked balance check).
        try {
            PayrollService::markSalaryPaid(
                $salaryRecord, $validated['payment_date'], $validated['payment_mode'],
                $validated['remarks'] ?? $salaryRecord->remarks, $paymentAccountId, $bankAccountId
            );
        } catch (InsufficientWalletBalanceException $e) {
            return back()->with('error',
                'Wallet balance insufficient. Available: Rs ' . number_format($e->available, 2) .
                ', Required: Rs ' . number_format((float) $salaryRecord->net_payable, 2));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $fresh = $salaryRecord->fresh();

        return redirect()
            ->route('finance.salary.index')
            ->with('success', $fresh->journal_entry_id
                ? 'Salary payment recorded, wallet debited and journal posted.'
                : 'Salary marked as paid, but accounting posting is pending.');
    }

    public function retryPosting(SalaryRecord $salaryRecord): RedirectResponse
    {
        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        abort_if($salaryRecord->journal_entry_id, 422, 'This salary has already been posted.');
        abort_if($salaryRecord->status !== SalaryRecord::STATUS_PAID, 422, 'Only a paid salary can be posted.');

        $journalEntry = JournalService::safePostSalaryPayment(
            $salaryRecord->fresh(['staffMember', 'expenseAccount', 'bankAccount'])
        );

        if ($journalEntry) {
            $salaryRecord->update(['journal_entry_id' => $journalEntry->id]);
            return back()->with('success', 'Accounting entry posted.');
        }

        return back()->with('error', 'Posting is still failing — check the expense/cash/bank account GL mapping in Finance Settings.');
    }

    public function reverseForm(SalaryRecord $salaryRecord): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        abort_if($salaryRecord->status !== SalaryRecord::STATUS_PAID, 422, 'Only paid salary can be reversed.');

        return view('institute.finance.salary.reverse', compact('salaryRecord'));
    }

    public function reverse(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        abort_if($salaryRecord->status !== SalaryRecord::STATUS_PAID, 422, 'Only paid salary can be reversed.');
        abort_if(
            FinanceSetting::isDateLocked($this->instituteId(), $salaryRecord->payment_date),
            422,
            'This salary falls in a locked accounting period (' . $salaryRecord->payment_date?->format('d M Y') . ') and cannot be reversed.'
        );

        $validated = $request->validate([
            'reversal_reason' => 'required|string|max:255',
        ]);

        $reversalEntry = JournalService::safeReverseSalaryPayment($salaryRecord, $validated['reversal_reason']);

        $salaryRecord->update([
            'status'                    => SalaryRecord::STATUS_REVERSED,
            'reversal_journal_entry_id' => $reversalEntry?->id,
            'reversed_at'               => now(),
            'reversed_by'               => auth()->id(),
            'reversal_reason'           => $validated['reversal_reason'],
        ]);

        // Credit wallet back (internally transactional with lockForUpdate — re-reads wallet_debited)
        InstituteWalletService::creditSalaryReversal($salaryRecord->fresh(['staffMember']));

        return redirect()
            ->route('finance.salary.index')
            ->with('success', $reversalEntry
                ? 'Salary reversal complete, wallet credited back and journal reversed.'
                : 'Salary marked as reversed. Accounting reversal was pending or missing.');
    }
}
