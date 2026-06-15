<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseApprovalLimit;
use App\Models\ExpenseCategoryL1;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Models\SalaryRecord;
use App\Services\AuditLogService;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StaffFinanceController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function permCheck(string $perm): void
    {
        if (!$this->staff()->hasPermission($perm)) {
            abort(403, 'Permission denied.');
        }
    }

    private function instituteId(): int
    {
        return $this->staff()->institute_id;
    }

    private function ensureAllowedPayrollCategory(?string $category): void
    {
        if (!$category) {
            return;
        }

        abort_if(!$this->staff()->canAccessPayrollCategory($category), 403, 'This staff category is outside your payroll scope.');
    }

    private function financeTablesReady(): bool
    {
        foreach (['accounts', 'journal_entries', 'expenses'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }
        return true;
    }

    // ── Expenses ──────────────────────────────────────────────────────
    public function expenses(): View|RedirectResponse
    {
        if (!$this->staff()->canViewFinance()) {
            abort(403, 'Permission denied.');
        }

        if (!$this->financeTablesReady()) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Finance module abhi migrate nahi hua hai.');
        }

        $instituteId = $this->instituteId();
        $expenses = Expense::with(['expenseAccount', 'paymentAccount', 'bankAccount'])
            ->where('institute_id', $instituteId)
            ->latest('expense_date')->latest('id')
            ->limit(50)->get();

        $expenseToday = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereDate('expense_date', today())
            ->sum('amount');

        $expenseThisMonth = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        $pendingPostingCount = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereNull('journal_entry_id')
            ->count();

        return view('institute.finance.expenses.index', compact(
            'expenses', 'expenseToday', 'expenseThisMonth', 'pendingPostingCount'
        ))->with('layout', 'staff.layout')->with('rp', 'staff.finance');
    }

    public function createExpense(): View|RedirectResponse
    {
        if (!$this->staff()->canCreateExpense()) {
            abort(403, 'Permission denied.');
        }

        if (!$this->financeTablesReady()) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Finance module abhi migrate nahi hua hai.');
        }

        $instituteId = $this->instituteId();

        $expenseAccounts = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')->where('is_active', true)
            ->whereDoesntHave('children')->orderBy('code')->get();

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderBy('name')->get();

        $settings = FinanceSetting::where('institute_id', $instituteId)->first();

        $l1Categories = ExpenseCategoryL1::where('institute_id', $instituteId)
            ->active()->orderBy('name')->get();

        // Staff approval limit for this role
        $staff = $this->staff();
        $autoApproveLimit = (float) (ExpenseApprovalLimit::where('institute_id', $instituteId)
            ->where('staff_role_id', $staff->staff_role_id)
            ->value('max_auto_approve_amount') ?? 0);

        $activeSessionId = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->value('id');

        $walletBalance = $activeSessionId
            ? InstituteWalletService::getBalance($instituteId, $activeSessionId)
            : null;

        $subCategoryAjaxUrl = route('staff.finance.ajax.sub-categories');
        $vendorAjaxUrl      = route('staff.finance.ajax.vendors');

        return view('institute.finance.expenses.create', compact(
            'expenseAccounts', 'bankAccounts', 'sessions', 'settings',
            'l1Categories', 'autoApproveLimit', 'walletBalance', 'activeSessionId',
            'subCategoryAjaxUrl', 'vendorAjaxUrl'
        ))->with('layout', 'staff.layout')->with('rp', 'staff.finance');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        if (!$this->staff()->canCreateExpense()) {
            abort(403, 'Permission denied.');
        }

        $instituteId = $this->instituteId();
        $validated = $request->validate([
            'academic_session_id'    => 'nullable|integer',
            'expense_date'           => 'required|date',
            'expense_account_id'     => 'required|integer',
            'expense_category_l1_id' => ['nullable', 'integer', Rule::exists('expense_categories_l1', 'id')->where('institute_id', $instituteId)],
            'expense_category_l2_id' => ['nullable', 'integer', Rule::exists('expense_categories_l2', 'id')->where('institute_id', $instituteId)],
            'expense_vendor_id'      => ['nullable', 'integer', Rule::exists('expense_vendors', 'id')->where('institute_id', $instituteId)],
            'amount'                 => 'required|numeric|min:0.01',
            'payment_mode'           => 'required|in:cash,bank',
            'bank_account_id'        => 'nullable|integer|required_if:payment_mode,bank',
            'vendor_name'            => 'nullable|string|max:150',
            'bill_no'                => 'nullable|string|max:100',
            'description'            => 'required|string|max:2000',
        ]);

        $sessionId = null;
        if (!empty($validated['academic_session_id'])) {
            $session = AcademicSession::where('institute_id', $instituteId)
                ->findOrFail((int) $validated['academic_session_id']);
            $sessionId = $session->id;
        }

        $expenseAccount = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')->where('is_active', true)
            ->whereDoesntHave('children')
            ->findOrFail((int) $validated['expense_account_id']);

        $paymentAccountId = null;
        $bankAccount = null;

        if ($validated['payment_mode'] === 'cash') {
            $settings = FinanceSetting::where('institute_id', $instituteId)->first();
            $paymentAccountId = $settings?->cash_account_id;
        } else {
            $bankAccount = InstituteBankAccount::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->findOrFail((int) $validated['bank_account_id']);
            $paymentAccountId = $bankAccount->gl_account_id;
        }

        $staff  = $this->staff();
        $amount = round((float) $validated['amount'], 2);

        // Check staff's approval limit
        $autoApproveLimit = (float) (ExpenseApprovalLimit::where('institute_id', $instituteId)
            ->where('staff_role_id', $staff->staff_role_id)
            ->value('max_auto_approve_amount') ?? 0);

        $needsApproval = $amount > $autoApproveLimit;

        // Wallet session
        $walletSessionId = $sessionId
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        // Wallet balance check — only if auto-approving
        if (!$needsApproval && $walletSessionId) {
            $balance = InstituteWalletService::getBalance($instituteId, $walletSessionId);
            if ($balance < $amount) {
                return back()->withInput()
                    ->withErrors(['amount' => 'Wallet balance insufficient. Available: Rs ' . number_format($balance, 2)]);
            }
        }

        $expense = Expense::create([
            'institute_id'            => $instituteId,
            'academic_session_id'     => $sessionId,
            'expense_account_id'      => $expenseAccount->id,
            'payment_account_id'      => $paymentAccountId,
            'bank_account_id'         => $bankAccount?->id,
            'expense_date'            => $validated['expense_date'],
            'amount'                  => $amount,
            'payment_mode'            => $validated['payment_mode'],
            'vendor_name'             => $validated['vendor_name'] ?? null,
            'bill_no'                 => $validated['bill_no'] ?? null,
            'description'             => $validated['description'],
            'expense_category_l1_id'  => $validated['expense_category_l1_id'] ?? null,
            'expense_category_l2_id'  => $validated['expense_category_l2_id'] ?? null,
            'expense_vendor_id'       => $validated['expense_vendor_id'] ?? null,
            'approval_status'         => $needsApproval ? Expense::STATUS_PENDING : Expense::STATUS_AUTO_APPROVED,
            'created_by'              => $staff->id,
        ]);

        AuditLogService::log($instituteId, 'finance', 'expense_created', 'Expense created.', $expense, [
            'amount'         => $amount,
            'payment_mode'   => $expense->payment_mode,
            'needs_approval' => $needsApproval,
        ]);

        if (!$needsApproval) {
            if ($walletSessionId && !$expense->academic_session_id) {
                $expense->update(['academic_session_id' => $walletSessionId]);
            }
            // debitExpense is internally transactional with lockForUpdate
            InstituteWalletService::debitExpense($expense->fresh(['categoryL2', 'vendor']));

            $journalEntry = JournalService::safePostExpense(
                $expense->fresh(['expenseAccount', 'paymentAccount', 'bankAccount'])
            );
            if ($journalEntry && !$expense->journal_entry_id) {
                $expense->update(['journal_entry_id' => $journalEntry->id]);
            }

            return redirect()->route('staff.finance.expenses.index')
                ->with('success', 'Expense save ho gaya, wallet se debit ho gaya aur GL entry post ho gayi.');
        }

        return redirect()->route('staff.finance.expenses.index')
            ->with('info', "Expense Rs {$amount} approval ke liye admin ke paas gayi hai.");
    }

    // ── Salary Book ──────────────────────────────────────────────────
    public function salary(): View|RedirectResponse
    {
        if (!$this->staff()->canManageSalary() && !$this->staff()->canViewFinance()) {
            abort(403, 'Permission denied.');
        }

        if (!Schema::hasTable('salary_records')) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Salary module abhi migrate nahi hua hai.');
        }

        $instituteId = $this->instituteId();
        $salaryQuery = SalaryRecord::with(['staffMember.role', 'expenseAccount', 'bankAccount'])
            ->where('institute_id', $instituteId);

        if ($this->staff()->hasRestrictedPayrollCategories()) {
            $salaryQuery->whereHas('staffMember', fn ($q) => $q->whereIn('staff_category', $this->staff()->allowedPayrollCategories()));
        }

        $salaryRecords = $salaryQuery
            ->orderByDesc('salary_year')->orderByDesc('salary_month')->orderByDesc('id')
            ->get();

        $totalPayable = (float) $salaryRecords->sum('net_payable');
        $totalPaid = (float) $salaryRecords->where('status', SalaryRecord::STATUS_PAID)->sum('paid_amount');
        $pendingCount = $salaryRecords->filter(fn($r) => in_array($r->status, [
            SalaryRecord::STATUS_PENDING,
            SalaryRecord::STATUS_DRAFT,
            SalaryRecord::STATUS_APPROVED,
        ]))->count();

        return view('institute.finance.salary.index', compact(
            'salaryRecords', 'totalPayable', 'totalPaid', 'pendingCount'
        ))->with('layout', 'staff.layout')->with('rp', 'staff.finance');
    }

    // ── Pay Salary ───────────────────────────────────────────────────
    public function paySalary(SalaryRecord $salaryRecord): View|RedirectResponse
    {
        if (!$this->staff()->canPaySalary()) {
            abort(403, 'Permission denied.');
        }
        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);

        if (!Schema::hasTable('salary_records')) {
            return redirect()->route('staff.finance.salary.index');
        }

        $salaryRecord->load(['staffMember.role', 'expenseAccount', 'bankAccount']);
        $this->ensureAllowedPayrollCategory($salaryRecord->staffMember?->staff_category);

        $bankAccounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
        $settings = FinanceSetting::where('institute_id', $this->instituteId())->first();

        return view('institute.finance.salary.pay', compact('salaryRecord', 'bankAccounts', 'settings'))
            ->with('layout', 'staff.layout')->with('rp', 'staff.finance');
    }

    public function markSalaryPaid(Request $request, SalaryRecord $salaryRecord): RedirectResponse
    {
        if (!$this->staff()->canPaySalary()) {
            abort(403, 'Permission denied.');
        }
        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
        $salaryRecord->loadMissing('staffMember');
        $this->ensureAllowedPayrollCategory($salaryRecord->staffMember?->staff_category);

        $validated = $request->validate([
            'payment_date'    => 'required|date',
            'payment_mode'    => 'required|in:cash,bank',
            'bank_account_id' => 'nullable|integer|required_if:payment_mode,bank',
            'remarks'         => 'nullable|string|max:500',
        ]);

        $paymentAccountId = null;
        $bankAccountId = null;

        if ($validated['payment_mode'] === 'cash') {
            $settings = FinanceSetting::where('institute_id', $this->instituteId())->first();
            $paymentAccountId = $settings?->cash_account_id;
        } else {
            $bankAccount = InstituteBankAccount::where('institute_id', $this->instituteId())
                ->where('is_active', true)->findOrFail((int) $validated['bank_account_id']);
            $bankAccountId    = $bankAccount->id;
            $paymentAccountId = $bankAccount->gl_account_id;
        }

        PayrollService::markSalaryPaid(
            $salaryRecord,
            $validated['payment_date'],
            $validated['payment_mode'],
            $validated['remarks'] ?? null,
            $paymentAccountId,
            $bankAccountId
        );
        AuditLogService::log($this->instituteId(), 'finance', 'salary_paid', 'Salary marked paid.', $salaryRecord, [
            'staff_member_id' => $salaryRecord->staff_member_id,
            'payment_mode' => $validated['payment_mode'],
            'payment_date' => $validated['payment_date'],
        ]);

        return redirect()->route('staff.finance.salary.index')
            ->with('success', $salaryRecord->staffMember?->name . ' ki salary mark paid ho gayi.');
    }
}
