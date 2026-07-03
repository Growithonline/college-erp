<?php

namespace App\Http\Controllers\Institute\Finance;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SalaryController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function ensureFinanceTablesReady(): ?RedirectResponse
    {
        foreach (['accounts', 'journal_entries', 'salary_records'] as $table) {
            if (!Schema::hasTable($table)) {
                return redirect()
                    ->route('institute.dashboard')
                    ->with('error', 'Salary module abhi migrate nahi hua hai. Pehle finance migrations run karo.');
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

        $salaryRecords = EmployeeSalaryDisbursement::with(['employee.department', 'employee.designation', 'expenseAccount', 'bankAccount', 'journalEntry'])
            ->where('institute_id', $instituteId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get();

        $totalPayable = (float) $salaryRecords->sum('gross_salary');
        $totalPaid    = (float) $salaryRecords->where('status', 'paid')->sum('net_salary');
        $pendingCount = $salaryRecords->where('status', 'pending')->count();

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
                "{$staffMember->name} ki {$validated['salary_year']} ke mahine {$validated['salary_month']} ki salary record pehle se exist karti hai (Status: {$existing->status}). Duplicate create nahi ki ja sakti."
            );
        }

        $basic = round((float) $validated['basic_salary'], 2);
        $allowances = round((float) ($validated['allowances'] ?? 0), 2);
        $deductions = round((float) ($validated['deductions'] ?? 0), 2);
        $netPayable = round($basic + $allowances - $deductions, 2);

        $paymentMode = $validated['payment_mode'] ?? null;
        $paymentDate = $validated['payment_date'] ?? null;
        $paidAmount = $paymentDate ? $netPayable : 0;
        $paymentAccountId = null;
        $bankAccountId = null;
        $status = $paymentDate ? SalaryRecord::STATUS_PAID : SalaryRecord::STATUS_DRAFT;

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

        $salaryRecord = SalaryRecord::create([
            'institute_id' => $instituteId,
            'academic_session_id' => !empty($validated['academic_session_id']) ? (int) $validated['academic_session_id'] : null,
            'staff_member_id' => (int) $staffMember->id,
            'expense_account_id' => (int) $expenseAccount->id,
            'payment_account_id' => $paymentAccountId,
            'bank_account_id' => $bankAccountId,
            'salary_month' => (int) $validated['salary_month'],
            'salary_year' => (int) $validated['salary_year'],
            'basic_salary' => $basic,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_payable' => $netPayable,
            'paid_amount' => $paidAmount,
            'payment_date' => $paymentDate,
            'payment_mode' => $paymentMode,
            'remarks' => $validated['remarks'] ?? null,
            'status' => $status,
            'created_by' => auth()->id(),
        ]);

        if ($salaryRecord->status === SalaryRecord::STATUS_PAID) {
            $journalEntry = JournalService::safePostSalaryPayment($salaryRecord->fresh(['staffMember', 'expenseAccount', 'bankAccount']));
            if ($journalEntry && !$salaryRecord->journal_entry_id) {
                $salaryRecord->update(['journal_entry_id' => $journalEntry->id]);
            }
        }

        return redirect()
            ->route('finance.salary.index')
            ->with('success', $salaryRecord->status === SalaryRecord::STATUS_PAID
                ? 'Salary record create ho gaya aur payment post ho gayi.'
                : 'Salary record create ho gaya. Ab pay screen se payment kar sakte ho.');
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

        $salaryRecord->update([
            'payment_account_id' => $paymentAccountId,
            'bank_account_id' => $bankAccountId,
            'paid_amount' => (float) $salaryRecord->net_payable,
            'payment_date' => $validated['payment_date'],
            'payment_mode' => $validated['payment_mode'],
            'remarks' => $validated['remarks'] ?? $salaryRecord->remarks,
            'status' => SalaryRecord::STATUS_PAID,
        ]);

        $fresh = $salaryRecord->fresh(['staffMember', 'expenseAccount', 'bankAccount']);
        $journalEntry = JournalService::safePostSalaryPayment($fresh);
        if ($journalEntry && !$salaryRecord->journal_entry_id) {
            $salaryRecord->update(['journal_entry_id' => $journalEntry->id]);
        }

        // Debit institute wallet for this salary payment
        InstituteWalletService::debitSalary($salaryRecord->fresh(['staffMember']));

        return redirect()
            ->route('finance.salary.index')
            ->with('success', $journalEntry
                ? 'Salary payment record ho gayi, wallet se debit ho gaya aur journal post ho gaya.'
                : 'Salary paid mark ho gayi, lekin accounting posting pending hai.');
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
                ? 'Salary reversal complete ho gaya, wallet me credit wapas aa gaya aur journal reverse ho gaya.'
                : 'Salary reversal mark ho gaya. Accounting reversal pending ya missing thi.');
    }
}
