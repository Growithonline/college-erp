<?php

namespace App\Http\Controllers\Institute\Finance;

use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Concerns\HasInstituteId;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseApprovalLimit;
use App\Models\ExpenseCategoryL1;
use App\Models\ExpenseVendorWorkOrder;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Services\InstituteWalletService;
use App\Services\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use HasInstituteId;

    private function ensureFinanceTablesReady(): ?RedirectResponse
    {
        foreach (['accounts', 'journal_entries', 'expenses'] as $table) {
            if (!Schema::hasTable($table)) {
                return redirect()
                    ->route('institute.dashboard')
                    ->with('error', 'Finance module has not been migrated yet. Please run the finance migrations first.');
            }
        }
        return null;
    }

    /**
     * Get the max auto-approve amount for current user.
     * Institute admin (web guard) → unlimited (PHP_INT_MAX)
     * Staff → check expense_approval_limits table for their role
     */
    private function userAutoApproveLimit(): float
    {
        // Institute admin logged in via web guard — no limit
        if (auth()->guard('web')->check()) {
            return PHP_INT_MAX;
        }

        // Staff user
        $staff = auth()->guard('staff')->user();
        if (!$staff) {
            return 0;
        }

        $limit = ExpenseApprovalLimit::where('institute_id', $this->instituteId())
            ->where('staff_role_id', $staff->staff_role_id)
            ->value('max_auto_approve_amount');

        return (float) ($limit ?? 0);
    }

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        $expenses = Expense::with(['expenseAccount', 'paymentAccount', 'bankAccount', 'categoryL1', 'categoryL2', 'vendor', 'reversalJournalEntry'])
            ->where('institute_id', $instituteId)
            ->latest('expense_date')
            ->latest('id')
            ->limit(50)
            ->get();

        $expenseToday = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereNotIn('approval_status', [Expense::STATUS_PENDING, Expense::STATUS_REJECTED])
            ->whereDate('expense_date', today())
            ->sum('amount');

        $expenseThisMonth = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereNotIn('approval_status', [Expense::STATUS_PENDING, Expense::STATUS_REJECTED])
            ->whereYear('expense_date', now()->year)
            ->whereMonth('expense_date', now()->month)
            ->sum('amount');

        $pendingApprovalCount = Expense::where('institute_id', $instituteId)
            ->where('approval_status', Expense::STATUS_PENDING)
            ->count();

        $pendingPostingCount = Expense::where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->whereIn('approval_status', [Expense::STATUS_AUTO_APPROVED, Expense::STATUS_APPROVED])
            ->whereNull('journal_entry_id')
            ->count();

        return view('institute.finance.expenses.index', compact(
            'expenses', 'expenseToday', 'expenseThisMonth', 'pendingApprovalCount', 'pendingPostingCount'
        ));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();

        $expenseAccounts = Account::where('institute_id', $instituteId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get();

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')->orderBy('name')
            ->get();

        $activeSessionId = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)->value('id');

        $settings = FinanceSetting::where('institute_id', $instituteId)->first();

        $l1Categories = ExpenseCategoryL1::where('institute_id', $instituteId)
            ->active()->orderBy('name')->get();

        $autoApproveLimit = $this->userAutoApproveLimit();

        // Current wallet balance for active session
        $walletBalance = $activeSessionId
            ? InstituteWalletService::getBalance($instituteId, $activeSessionId)
            : null;

        $subCategoryAjaxUrl = route('finance.wallet.ajax.sub-categories');
        $vendorAjaxUrl      = route('finance.wallet.ajax.vendors');
        $workOrderAjaxUrl   = route('finance.wallet.ajax.work-orders');

        return view('institute.finance.expenses.create', compact(
            'expenseAccounts', 'bankAccounts', 'sessions', 'settings',
            'l1Categories', 'autoApproveLimit', 'walletBalance', 'activeSessionId',
            'subCategoryAjaxUrl', 'vendorAjaxUrl', 'workOrderAjaxUrl'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'academic_session_id'   => 'nullable|integer',
            'expense_date'          => 'required|date',
            'expense_account_id'    => 'required|integer',
            'expense_category_l1_id'=> ['nullable', 'integer', Rule::exists('expense_categories_l1', 'id')->where('institute_id', $instituteId)],
            'expense_category_l2_id'=> ['nullable', 'integer', Rule::exists('expense_categories_l2', 'id')->where('institute_id', $instituteId)],
            'expense_vendor_id'     => ['nullable', 'integer', Rule::exists('expense_vendors', 'id')->where('institute_id', $instituteId)],
            'expense_vendor_work_order_id' => ['nullable', 'integer', Rule::exists('expense_vendor_work_orders', 'id')->where('institute_id', $instituteId)],
            'amount'                => 'required|numeric|min:0.01',
            'payment_mode'          => 'required|in:cash,bank',
            'bank_account_id'       => 'nullable|integer|required_if:payment_mode,bank',
            'vendor_name'           => 'nullable|string|max:150',
            'bill_no'               => 'nullable|string|max:100',
            'description'           => 'required|string|max:2000',
        ]);

        if (FinanceSetting::isDateLocked($instituteId, $validated['expense_date'])) {
            return back()->withInput()->withErrors([
                'expense_date' => 'This date falls in a locked accounting period and cannot accept new expenses.',
            ]);
        }

        $sessionId = null;
        if (!empty($validated['academic_session_id'])) {
            $session = AcademicSession::where('institute_id', $instituteId)
                ->findOrFail((int) $validated['academic_session_id']);
            $sessionId = (int) $session->id;
        }

        // If no session selected, fall back to active session for wallet check
        $walletSessionId = $sessionId
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

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

        $amount = round((float) $validated['amount'], 2);

        // Determine approval status
        $autoApproveLimit = $this->userAutoApproveLimit();
        $needsApproval    = $amount > $autoApproveLimit;

        // Wallet balance check — only block if auto-approved (staff with no approval needed) and balance insufficient
        if (!$needsApproval && $walletSessionId) {
            $balance = InstituteWalletService::getBalance($instituteId, $walletSessionId);
            if ($balance < $amount) {
                return back()
                    ->withInput()
                    ->withErrors(['amount' => "Wallet balance insufficient. Available: Rs " . number_format($balance, 2)]);
            }
        }

        $baseData = [
            'institute_id'           => $instituteId,
            'expense_account_id'     => (int) $expenseAccount->id,
            'payment_account_id'     => $paymentAccountId,
            'bank_account_id'        => $bankAccount?->id,
            'expense_date'           => $validated['expense_date'],
            'amount'                 => $amount,
            'payment_mode'           => $validated['payment_mode'],
            'vendor_name'            => $validated['vendor_name'] ?? null,
            'bill_no'                => $validated['bill_no'] ?? null,
            'description'            => $validated['description'],
            'expense_category_l1_id' => $validated['expense_category_l1_id'] ?? null,
            'expense_category_l2_id' => $validated['expense_category_l2_id'] ?? null,
            'expense_vendor_id'      => $validated['expense_vendor_id'] ?? null,
            'expense_vendor_work_order_id' => $validated['expense_vendor_work_order_id'] ?? null,
            'created_by'             => auth()->id(),
        ];

        if ($needsApproval) {
            Expense::create($baseData + [
                'academic_session_id' => $sessionId,
                'approval_status'     => Expense::STATUS_PENDING,
            ]);

            return redirect()->route('finance.expenses.index')
                ->with('info', "Expense of Rs {$amount} is pending approval from an admin.");
        }

        // Auto-approved: create + wallet debit happen atomically — either both succeed or neither does
        try {
            $expense = DB::transaction(function () use ($baseData, $sessionId, $walletSessionId) {
                $expense = Expense::create($baseData + [
                    'academic_session_id' => $sessionId ?? $walletSessionId,
                    'approval_status'     => Expense::STATUS_AUTO_APPROVED,
                ]);

                if ($walletSessionId) {
                    InstituteWalletService::debitExpense($expense->fresh(['categoryL2', 'vendor']));

                    if ($expense->expense_vendor_work_order_id) {
                        ExpenseVendorWorkOrder::find($expense->expense_vendor_work_order_id)
                            ?->debit((float) $expense->amount, $expense->id, 'Expense: ' . $expense->description, auth()->id());
                    }
                }

                return $expense;
            });
        } catch (InsufficientWalletBalanceException $e) {
            return back()->withInput()->withErrors([
                'amount' => 'Wallet balance insufficient. Available: Rs ' . number_format($e->available, 2),
            ]);
        }

        $journalEntry = JournalService::safePostExpense($expense->fresh(['expenseAccount', 'paymentAccount', 'bankAccount']));
        if ($journalEntry && !$expense->journal_entry_id) {
            $expense->update(['journal_entry_id' => $journalEntry->id]);
        }

        return redirect()->route('finance.expenses.index')
            ->with('success', 'Expense saved, wallet debited, and accounting entry posted.');
    }

    public function retryPosting(Expense $expense): RedirectResponse
    {
        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->journal_entry_id, 422, 'This expense has already been posted.');
        abort_if($expense->is_reversed, 422, 'A reversed expense cannot be posted.');
        abort_if(!$expense->isApproved(), 422, 'Only an approved expense can be posted.');

        $journalEntry = JournalService::safePostExpense($expense->fresh(['expenseAccount', 'paymentAccount', 'bankAccount']));

        if ($journalEntry) {
            $expense->update(['journal_entry_id' => $journalEntry->id]);
            return back()->with('success', 'Accounting entry posted.');
        }

        return back()->with('error', 'Posting is still failing — check the expense/cash/bank account GL mapping in Finance Settings.');
    }

    public function reverseForm(Expense $expense): View|RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->is_reversed, 422, 'Expense already reversed.');

        return view('institute.finance.expenses.reverse', compact('expense'));
    }

    public function reverse(Request $request, Expense $expense): RedirectResponse
    {
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        abort_if($expense->institute_id !== $this->instituteId(), 403);
        abort_if($expense->is_reversed, 422, 'Expense already reversed.');
        abort_if(
            FinanceSetting::isDateLocked($this->instituteId(), $expense->expense_date),
            422,
            'This expense falls in a locked accounting period (' . $expense->expense_date?->format('d M Y') . ') and cannot be reversed.'
        );

        $validated = $request->validate([
            'reversal_reason' => 'required|string|max:255',
        ]);

        $reversalEntry = JournalService::safeReverseExpense($expense, $validated['reversal_reason']);

        // Capture BEFORE creditExpenseReversal() flips wallet_debited — this is the only
        // reliable signal that the expense actually debited the wallet (a still-PENDING,
        // never-debited expense can also reach this method, since reverse() doesn't gate
        // on approval_status).
        $hadDebitedWallet = (bool) $expense->wallet_debited;

        // Credit wallet back if this expense had debited the wallet
        InstituteWalletService::creditExpenseReversal($expense->fresh());

        if ($hadDebitedWallet && $expense->expense_vendor_work_order_id) {
            ExpenseVendorWorkOrder::find($expense->expense_vendor_work_order_id)
                ?->creditBack((float) $expense->amount, $expense->id, 'Expense reversed: ' . $validated['reversal_reason'], auth()->id());
        }

        $expense->update([
            'is_reversed'               => true,
            'reversal_journal_entry_id' => $reversalEntry?->id,
            'reversed_at'               => now(),
            'reversed_by'               => auth()->id(),
            'reversal_reason'           => $validated['reversal_reason'],
        ]);

        return redirect()->route('finance.expenses.index')
            ->with('success', $reversalEntry
                ? 'Expense reversed, wallet credited back.'
                : 'Expense marked as reversed. Accounting reversal was pending or missing.');
    }
}
