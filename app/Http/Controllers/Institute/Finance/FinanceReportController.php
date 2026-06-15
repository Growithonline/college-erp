<?php

namespace App\Http\Controllers\Institute\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Account;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FinanceSetting;
use App\Models\InstituteBankAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Library\LibraryFinePayment;
use App\Models\SalaryRecord;
use App\Services\AccountingSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
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

    private function checkStaffPermission(): void
    {
        $staff = auth()->guard('staff')->user();
        if ($staff && !$staff->hasAnyPermission(['finance_reports', 'ledger_view', 'finance_view'])) {
            abort(403, 'Permission denied.');
        }
    }

    private function ensureFinanceTablesReady(): ?RedirectResponse
    {
        foreach (['accounts', 'journal_entries', 'journal_entry_lines'] as $table) {
            if (!Schema::hasTable($table)) {
                $dashboard = auth()->guard('staff')->check() ? 'staff.dashboard' : 'institute.dashboard';
                return redirect()->route($dashboard)
                    ->with('error', 'Finance reports abhi ready nahi hain.');
            }
        }

        return null;
    }

    private function staffLayout(): array
    {
        if (auth()->guard('staff')->check()) {
            return ['layout' => 'staff.layout', 'rp' => 'staff.finance'];
        }
        return [];
    }

    public function ledger(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $accounts = $this->accounts($instituteId);
        $sessions = $this->sessions($instituteId);

        $accountId = $request->integer('account_id');
        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $selectedAccount = $accountId
            ? Account::where('institute_id', $instituteId)->find($accountId)
            : null;

        ['openingBalance' => $openingBalance, 'rows' => $rows, 'closingBalance' => $closingBalance] =
            $selectedAccount
                ? $this->ledgerDataForAccount($instituteId, $selectedAccount, $sessionId, $dateFrom, $dateTo)
                : ['openingBalance' => 0.0, 'rows' => collect(), 'closingBalance' => 0.0];

        if ($this->wantsCsv($request)) {
            return $this->exportCsv(
                ['Date', 'Narration', 'Reference Type', 'Reference ID', 'Debit', 'Credit', 'Running Balance'],
                $this->ledgerCsvRows($dateFrom, $rows, $openingBalance, $closingBalance),
                'ledger-report.csv'
            );
        }

        return view('institute.finance.reports.ledger', compact(
            'accounts',
            'sessions',
            'selectedAccount',
            'sessionId',
            'dateFrom',
            'dateTo',
            'openingBalance',
            'rows',
            'closingBalance'
        ))->with($this->staffLayout());
    }

    public function trialBalance(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $sessionId = $request->integer('session_id') ?: null;
        $dateTo = $request->input('date_to', now()->toDateString());

        $accounts = Account::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $totals = $this->lineTotalsByAccount($instituteId, null, $dateTo, $sessionId);

        $rows = $accounts->map(function (Account $account) use ($totals) {
            $debit = (float) ($totals[$account->id]['debit'] ?? 0);
            $credit = (float) ($totals[$account->id]['credit'] ?? 0);
            $net = $this->calculateSignedBalance($debit, $credit, $account);

            $debitBalance = $net >= 0 ? $net : 0;
            $creditBalance = $net < 0 ? abs($net) : 0;

            return [
                'account' => $account,
                'debit_total' => $debit,
                'credit_total' => $credit,
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];
        })->filter(fn ($row) => $row['debit_balance'] > 0 || $row['credit_balance'] > 0)->values();

        $totalDebit = round((float) $rows->sum('debit_balance'), 2);
        $totalCredit = round((float) $rows->sum('credit_balance'), 2);

        if ($this->wantsCsv($request)) {
            $csvRows = $rows->map(fn ($row) => [
                $row['account']->code,
                $row['account']->name,
                ucfirst($row['account']->type),
                $row['debit_balance'],
                $row['credit_balance'],
            ])->all();

            return $this->exportCsv(
                ['Code', 'Account', 'Type', 'Net Debit', 'Net Credit'],
                $csvRows,
                'trial-balance.csv'
            );
        }

        return view('institute.finance.reports.trial-balance', compact(
            'sessions',
            'sessionId',
            'dateTo',
            'rows',
            'totalDebit',
            'totalCredit'
        ))->with($this->staffLayout());
    }

    public function profitAndLoss(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $accounts = Account::with('parent')
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get();

        $totals = $this->lineTotalsByAccount($instituteId, $dateFrom, $dateTo, $sessionId);

        $incomeRows = collect();
        $expenseRows = collect();

        foreach ($accounts as $account) {
            $debit = (float) ($totals[$account->id]['debit'] ?? 0);
            $credit = (float) ($totals[$account->id]['credit'] ?? 0);

            if ($account->type === 'income') {
                $amount = round($credit - $debit, 2);
                if ($amount != 0.0) {
                    $incomeRows->push(['account' => $account, 'amount' => $amount]);
                }
            }

            if ($account->type === 'expense') {
                $amount = round($debit - $credit, 2);
                if ($amount != 0.0) {
                    $expenseRows->push(['account' => $account, 'amount' => $amount]);
                }
            }
        }

        $incomeRows = $incomeRows->sortBy(fn ($row) => $row['account']->code)->values();
        $expenseRows = $expenseRows->sortBy(fn ($row) => $row['account']->code)->values();
        $groupedIncomeRows = $this->groupProfitAndLossRows($incomeRows);
        $groupedExpenseRows = $this->groupProfitAndLossRows($expenseRows);

        $totalIncome = round((float) $incomeRows->sum('amount'), 2);
        $totalExpense = round((float) $expenseRows->sum('amount'), 2);
        $netResult = round($totalIncome - $totalExpense, 2);

        if ($this->wantsCsv($request)) {
            return $this->exportCsv(
                ['Section', 'Parent Head', 'Account Code', 'Account', 'Amount'],
                array_merge(
                    $this->profitLossCsvRows('Income', $groupedIncomeRows),
                    $this->profitLossCsvRows('Expense', $groupedExpenseRows)
                ),
                'profit-loss.csv'
            );
        }

        return view('institute.finance.reports.profit-loss', compact(
            'sessions',
            'sessionId',
            'dateFrom',
            'dateTo',
            'incomeRows',
            'expenseRows',
            'groupedIncomeRows',
            'groupedExpenseRows',
            'totalIncome',
            'totalExpense',
            'netResult'
        ))->with($this->staffLayout());
    }

    public function dayBook(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = $this->sessions($instituteId);
        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $entries = JournalEntry::with(['lines.account'])
            ->where('institute_id', $instituteId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $totalDebit = round((float) $entries->sum('total_debit'), 2);
        $totalCredit = round((float) $entries->sum('total_credit'), 2);

        if ($this->wantsCsv($request)) {
            $csvRows = $entries->map(fn (JournalEntry $entry) => [
                optional($entry->date)->format('Y-m-d'),
                $entry->narration ?: 'Journal Entry',
                $entry->reference_type,
                $entry->reference_id,
                $entry->lines->map(fn ($line) => trim(
                    ($line->account?->code ? $line->account->code . ' - ' : '')
                    . ($line->account?->name ?? 'Account')
                    . ' (' . ucfirst($line->entry_type) . ' ' . number_format((float) $line->amount, 2, '.', '') . ')'
                ))->implode(' | '),
                $entry->total_debit,
                $entry->total_credit,
            ])->all();

            return $this->exportCsv(
                ['Date', 'Narration', 'Reference Type', 'Reference ID', 'Lines', 'Total Debit', 'Total Credit'],
                $csvRows,
                'day-book.csv'
            );
        }

        return view('institute.finance.reports.day-book', compact(
            'sessions',
            'sessionId',
            'dateFrom',
            'dateTo',
            'entries',
            'totalDebit',
            'totalCredit'
        ))->with($this->staffLayout());
    }

    public function cashBook(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = $this->sessions($instituteId);
        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $settings = FinanceSetting::where('institute_id', $instituteId)->first();
        $cashAccount = $settings?->cashAccount;

        ['openingBalance' => $openingBalance, 'rows' => $rows, 'closingBalance' => $closingBalance] =
            $cashAccount
                ? $this->ledgerDataForAccount($instituteId, $cashAccount, $sessionId, $dateFrom, $dateTo)
                : ['openingBalance' => 0.0, 'rows' => collect(), 'closingBalance' => 0.0];

        if ($this->wantsCsv($request)) {
            return $this->exportCsv(
                ['Date', 'Narration', 'Reference Type', 'Reference ID', 'Debit', 'Credit', 'Running Balance'],
                $this->ledgerCsvRows($dateFrom, $rows, $openingBalance, $closingBalance),
                'cash-book.csv'
            );
        }

        return view('institute.finance.reports.cash-book', compact(
            'sessions',
            'sessionId',
            'dateFrom',
            'dateTo',
            'cashAccount',
            'openingBalance',
            'rows',
            'closingBalance'
        ))->with($this->staffLayout());
    }

    public function bankBook(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = $this->sessions($instituteId);
        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $bankAccountId = $request->integer('bank_account_id');

        $bankAccounts = InstituteBankAccount::with('glAccount')
            ->where('institute_id', $instituteId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedBankAccount = $bankAccountId
            ? $bankAccounts->firstWhere('id', $bankAccountId)
            : null;
        $selectedGlAccount = $selectedBankAccount?->glAccount;

        ['openingBalance' => $openingBalance, 'rows' => $rows, 'closingBalance' => $closingBalance] =
            $selectedGlAccount
                ? $this->ledgerDataForAccount($instituteId, $selectedGlAccount, $sessionId, $dateFrom, $dateTo)
                : ['openingBalance' => 0.0, 'rows' => collect(), 'closingBalance' => 0.0];

        if ($this->wantsCsv($request)) {
            return $this->exportCsv(
                ['Date', 'Narration', 'Reference Type', 'Reference ID', 'Debit', 'Credit', 'Running Balance'],
                $this->ledgerCsvRows($dateFrom, $rows, $openingBalance, $closingBalance),
                'bank-book.csv'
            );
        }

        return view('institute.finance.reports.bank-book', compact(
            'sessions',
            'sessionId',
            'dateFrom',
            'dateTo',
            'bankAccounts',
            'selectedBankAccount',
            'selectedGlAccount',
            'openingBalance',
            'rows',
            'closingBalance'
        ))->with($this->staffLayout());
    }

    public function reconciliation(Request $request): View|RedirectResponse|StreamedResponse
    {
        $this->checkStaffPermission();
        if ($redirect = $this->ensureFinanceTablesReady()) {
            return $redirect;
        }

        $instituteId = $this->instituteId();
        AccountingSetupService::bootstrapInstitute($instituteId);

        $sessions = $this->sessions($instituteId);
        $sessionId = $request->integer('session_id') ?: null;
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $feeJournalReferenceIds = JournalEntry::query()
            ->where('institute_id', $instituteId)
            ->where('reference_type', 'fee_invoice_collection')
            ->whereNull('reversal_of_entry_id')
            ->where('status', JournalEntry::STATUS_POSTED)
            ->pluck('reference_id');

        $expenseJournalReferenceIds = JournalEntry::query()
            ->where('institute_id', $instituteId)
            ->where('reference_type', 'expense_payment')
            ->whereNull('reversal_of_entry_id')
            ->where('status', JournalEntry::STATUS_POSTED)
            ->pluck('reference_id');

        $salaryJournalReferenceIds = JournalEntry::query()
            ->where('institute_id', $instituteId)
            ->where('reference_type', 'salary_payment')
            ->whereNull('reversal_of_entry_id')
            ->where('status', JournalEntry::STATUS_POSTED)
            ->pluck('reference_id');

        $feeInvoicesQuery = FeeInvoice::with('student:id,name')
            ->where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $expensesQuery = Expense::with(['expenseAccount:id,code,name'])
            ->where('institute_id', $instituteId)
            ->where('is_reversed', false)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        $salaryQuery = SalaryRecord::with(['staffMember:id,name'])
            ->where('institute_id', $instituteId)
            ->where('status', SalaryRecord::STATUS_PAID)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $operationalFeeCollection = round((float) (clone $feeInvoicesQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(paid_amount, 0) + COALESCE(discount, 0)), 0) as total')
            ->value('total'), 2);
        $journalFeeCollection = round((float) $this->originalJournalEntriesQuery($instituteId, 'fee_invoice_collection', $dateFrom, $dateTo, $sessionId)
            ->sum('total_debit'), 2);

        $operationalExpense = round((float) (clone $expensesQuery)->sum('amount'), 2);
        $journalExpense = round((float) $this->originalJournalEntriesQuery($instituteId, 'expense_payment', $dateFrom, $dateTo, $sessionId)
            ->sum('total_debit'), 2);

        $operationalSalary = round((float) (clone $salaryQuery)->sum('paid_amount'), 2);
        $journalSalary = round((float) $this->originalJournalEntriesQuery($instituteId, 'salary_payment', $dateFrom, $dateTo, $sessionId)
            ->sum('total_debit'), 2);

        $missingFeeInvoicesCount = (clone $feeInvoicesQuery)
            ->whereRaw('(COALESCE(paid_amount, 0) + COALESCE(discount, 0)) > 0')
            ->whereNotIn('id', $feeJournalReferenceIds)
            ->count();

        $missingFeeInvoices = (clone $feeInvoicesQuery)
            ->whereRaw('(COALESCE(paid_amount, 0) + COALESCE(discount, 0)) > 0')
            ->whereNotIn('id', $feeJournalReferenceIds)
            ->latest('payment_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $missingExpensesCount = (clone $expensesQuery)
            ->whereNotIn('id', $expenseJournalReferenceIds)
            ->count();

        $missingExpenses = (clone $expensesQuery)
            ->whereNotIn('id', $expenseJournalReferenceIds)
            ->latest('expense_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $missingSalariesCount = (clone $salaryQuery)
            ->whereNotIn('id', $salaryJournalReferenceIds)
            ->count();

        $missingSalaries = (clone $salaryQuery)
            ->whereNotIn('id', $salaryJournalReferenceIds)
            ->latest('payment_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $sections = collect([
            [
                'label' => 'Fee Collection',
                'operational_total' => $operationalFeeCollection,
                'journal_total' => $journalFeeCollection,
                'difference' => round($operationalFeeCollection - $journalFeeCollection, 2),
                'missing_count' => $missingFeeInvoicesCount,
            ],
            [
                'label' => 'Expenses',
                'operational_total' => $operationalExpense,
                'journal_total' => $journalExpense,
                'difference' => round($operationalExpense - $journalExpense, 2),
                'missing_count' => $missingExpensesCount,
            ],
            [
                'label' => 'Salary',
                'operational_total' => $operationalSalary,
                'journal_total' => $journalSalary,
                'difference' => round($operationalSalary - $journalSalary, 2),
                'missing_count' => $missingSalariesCount,
            ],
        ]);

        $overallDifference = round((float) $sections->sum('difference'), 2);
        $totalMissing = (int) $sections->sum('missing_count');

        if ($this->wantsCsv($request)) {
            $csvRows = $sections->map(fn ($section) => [
                $section['label'],
                $section['operational_total'],
                $section['journal_total'],
                $section['difference'],
                $section['missing_count'],
            ])->all();

            return $this->exportCsv(
                ['Module', 'Operational Total', 'Journal Total', 'Difference', 'Missing Journals'],
                $csvRows,
                'finance-reconciliation.csv'
            );
        }

        return view('institute.finance.reports.reconciliation', compact(
            'sessions',
            'sessionId',
            'dateFrom',
            'dateTo',
            'sections',
            'overallDifference',
            'totalMissing',
            'missingFeeInvoices',
            'missingExpenses',
            'missingSalaries'
        ))->with($this->staffLayout());
    }

    private function accounts(int $instituteId)
    {
        return Account::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function sessions(int $instituteId)
    {
        return AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    private function ledgerDataForAccount(
        int $instituteId,
        Account $selectedAccount,
        ?int $sessionId,
        string $dateFrom,
        string $dateTo
    ): array {
        $baseQuery = JournalEntryLine::query()
            ->where('account_id', $selectedAccount->id)
            ->whereHas('journalEntry', function ($query) use ($instituteId, $sessionId) {
                $query->where('institute_id', $instituteId)
                    ->where('status', JournalEntry::STATUS_POSTED)
                    ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId));
            });

        $openingBalance = $this->calculateSignedBalance(
            debits: (float) (clone $baseQuery)->where('entry_type', 'debit')
                ->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '<', $dateFrom))
                ->sum('amount'),
            credits: (float) (clone $baseQuery)->where('entry_type', 'credit')
                ->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '<', $dateFrom))
                ->sum('amount'),
            account: $selectedAccount
        );

        $lines = (clone $baseQuery)
            ->with(['journalEntry'])
            ->whereHas('journalEntry', function ($query) use ($dateFrom, $dateTo) {
                $query->whereDate('date', '>=', $dateFrom)
                    ->whereDate('date', '<=', $dateTo);
            })
            ->orderBy(
                JournalEntry::select('date')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id')
            )
            ->orderBy('journal_entry_id')
            ->orderBy('line_no')
            ->get();

        $runningBalance = $openingBalance;
        $rows = $lines->map(function (JournalEntryLine $line) use (&$runningBalance, $selectedAccount) {
            $debit = (float) ($line->entry_type === 'debit' ? $line->amount : 0);
            $credit = (float) ($line->entry_type === 'credit' ? $line->amount : 0);
            $runningBalance += $this->calculateSignedDelta($debit, $credit, $selectedAccount);

            return [
                'date' => $line->journalEntry?->date,
                'narration' => $line->journalEntry?->narration ?: $line->narration,
                'reference_type' => $line->journalEntry?->reference_type,
                'reference_id' => $line->journalEntry?->reference_id,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        });

        return [
            'openingBalance' => $openingBalance,
            'rows' => $rows,
            'closingBalance' => $runningBalance,
        ];
    }

    private function lineTotalsByAccount(int $instituteId, ?string $dateFrom, ?string $dateTo, ?int $sessionId): array
    {
        return JournalEntryLine::query()
            ->select('account_id')
            ->selectRaw("SUM(CASE WHEN entry_type = 'debit' THEN amount ELSE 0 END) as debit_total")
            ->selectRaw("SUM(CASE WHEN entry_type = 'credit' THEN amount ELSE 0 END) as credit_total")
            ->whereHas('journalEntry', function ($query) use ($instituteId, $dateFrom, $dateTo, $sessionId) {
                $query->where('institute_id', $instituteId)
                    ->where('status', JournalEntry::STATUS_POSTED)
                    ->when($dateFrom, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($q) => $q->whereDate('date', '<=', $dateTo))
                    ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId));
            })
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->account_id => [
                    'debit' => round((float) $row->debit_total, 2),
                    'credit' => round((float) $row->credit_total, 2),
                ],
            ])
            ->all();
    }

    private function calculateSignedBalance(float $debits, float $credits, Account $account): float
    {
        if ($account->normal_side === 'credit') {
            return round($credits - $debits, 2);
        }

        return round($debits - $credits, 2);
    }

    private function calculateSignedDelta(float $debits, float $credits, Account $account): float
    {
        return $this->calculateSignedBalance($debits, $credits, $account);
    }

    private function wantsCsv(Request $request): bool
    {
        return strtolower((string) $request->input('export')) === 'csv';
    }

    private function groupProfitAndLossRows(Collection $rows): Collection
    {
        return $rows
            ->groupBy(function (array $row) {
                $account = $row['account'];

                return $account->parent?->name ?? $account->name;
            })
            ->map(function (Collection $group, string $label) {
                return [
                    'label' => $label,
                    'total' => round((float) $group->sum('amount'), 2),
                    'rows' => $group->values(),
                ];
            })
            ->sortBy(fn (array $group) => $group['rows']->first()['account']->code ?? $group['label'])
            ->values();
    }

    private function ledgerCsvRows(string $dateFrom, Collection $rows, float $openingBalance, float $closingBalance): array
    {
        $csvRows = [[
            $dateFrom,
            'Opening Balance',
            'opening',
            '',
            '',
            '',
            $openingBalance,
        ]];

        foreach ($rows as $row) {
            $csvRows[] = [
                optional($row['date'])->format('Y-m-d'),
                $row['narration'] ?: 'Journal Entry',
                $row['reference_type'] ?? 'manual',
                $row['reference_id'] ?? '',
                $row['debit'] ?: '',
                $row['credit'] ?: '',
                $row['balance'],
            ];
        }

        $csvRows[] = [
            '',
            'Closing Balance',
            'closing',
            '',
            '',
            '',
            $closingBalance,
        ];

        return $csvRows;
    }

    private function profitLossCsvRows(string $section, Collection $groups): array
    {
        $rows = [];

        foreach ($groups as $group) {
            foreach ($group['rows'] as $row) {
                $rows[] = [
                    $section,
                    $group['label'],
                    $row['account']->code,
                    $row['account']->name,
                    $row['amount'],
                ];
            }
        }

        return $rows;
    }

    private function originalJournalEntriesQuery(
        int $instituteId,
        string $referenceType,
        string $dateFrom,
        string $dateTo,
        ?int $sessionId
    ) {
        return JournalEntry::query()
            ->where('institute_id', $instituteId)
            ->where('reference_type', $referenceType)
            ->whereNull('reversal_of_entry_id')
            ->where('status', JournalEntry::STATUS_POSTED)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo);
    }

    private function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function incomeBook(Request $request): View|RedirectResponse
    {
        $this->checkStaffPermission();

        $instituteId = $this->instituteId();
        $dateFrom    = $request->filled('date_from') && strtotime($request->input('date_from'))
                        ? $request->input('date_from')
                        : now()->startOfMonth()->toDateString();
        $dateTo      = $request->filled('date_to') && strtotime($request->input('date_to'))
                        ? $request->input('date_to')
                        : now()->toDateString();
        if ($dateTo < $dateFrom) { $dateTo = $dateFrom; }
        $type        = in_array($request->input('type'), ['all', 'fee', 'library_fine'])
                        ? $request->input('type') : 'all';

        // Fee collections
        $feeRows = collect();
        if ($type === 'all' || $type === 'fee') {
            $feeRows = FeeInvoice::where('institute_id', $instituteId)
                ->where('is_cancelled', false)
                ->where('paid_amount', '>', 0)
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->with(['student:id,name,student_uid'])
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get()
                ->map(fn($inv) => [
                    'date'         => $inv->payment_date,
                    'type'         => 'fee',
                    'type_label'   => 'Fee Collection',
                    'description'  => ($inv->student?->name ?? 'Student') . ' — ' . $inv->invoice_no,
                    'amount'       => (float) $inv->paid_amount,
                    'payment_mode' => $inv->payment_mode,
                    'reference'    => $inv->invoice_no,
                ]);
        }

        // Library fine collections
        $fineRows = collect();
        if ($type === 'all' || $type === 'library_fine') {
            $fineRows = LibraryFinePayment::where('institute_id', $instituteId)
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->with(['member:id,name,member_code'])
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get()
                ->groupBy('receipt_no')
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'date'         => $first->payment_date,
                        'type'         => 'library_fine',
                        'type_label'   => 'Library Fine',
                        'description'  => ($first->member?->name ?? 'Member') . ' (' . ($first->member?->member_code ?? '') . ') — ' . $first->receipt_no,
                        'amount'       => (float) $group->sum('amount'),
                        'payment_mode' => $first->payment_mode,
                        'reference'    => $first->receipt_no,
                    ];
                })
                ->values();
        }

        $entries = $feeRows->merge($fineRows)
            ->sortBy('date')
            ->values();

        $totals = [
            'fee'          => $feeRows->sum('amount'),
            'library_fine' => $fineRows->sum('amount'),
            'grand'        => $entries->sum('amount'),
        ];

        $staffLayout = $this->staffLayout();

        return view('institute.finance.reports.income-book', compact(
            'entries', 'totals', 'dateFrom', 'dateTo', 'type', 'staffLayout'
        ));
    }
}
