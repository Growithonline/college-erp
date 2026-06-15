<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Expense;
use App\Models\ExpenseCategoryL1;
use App\Models\ExpenseCategoryL2;
use App\Models\FinanceSetting;
use App\Models\InstituteManualIncome;
use App\Models\InstituteTransaction;
use App\Models\InstituteWallet;
use App\Models\SalaryRecord;
use App\Models\Center;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\StaffMember;
use App\Models\User;
use App\Exports\IncomeReportExport;
use App\Exports\WalletLedgerExport;
use App\Models\ChannelPartner;
use App\Models\ContraEntry;
use Illuminate\Support\Facades\DB;
use App\Models\ChequePayment;
use App\Models\Institute;
use App\Models\InstituteBankAccount;
use App\Services\InstituteWalletService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletDashboardController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private static array $sourceLabels = [
        'fee_invoice'     => 'Fee Collection',
        'library_fine'    => 'Library Fine',
        'manual_income'   => 'Manual Income',
        'expense'         => 'Expense',
        'expense_reversal'=> 'Expense Reversal',
        'salary'          => 'Salary Payment',
        'salary_reversal' => 'Salary Reversal',
    ];

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('start_date')->get();

        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        $activeSession = $sessions->firstWhere('id', $sessionId);

        $summary = $sessionId
            ? InstituteWalletService::getWalletSummary($instituteId, (int) $sessionId)
            : ['balance' => 0, 'total_income' => 0, 'total_expense' => 0,
               'today_income' => 0, 'today_expense' => 0, 'by_source' => []];

        // Low balance threshold from finance settings
        $setting   = FinanceSetting::where('institute_id', $instituteId)->first();
        $threshold = (float) ($setting?->wallet_low_balance_threshold ?? 0);
        $lowBalance = $threshold > 0 && $summary['balance'] < $threshold;

        // Month-wise last 6 months income & expense for chart
        $monthlyData = $this->getMonthlyData($instituteId, $sessionId);

        // Pending expense approvals count
        $pendingApprovals = Expense::where('institute_id', $instituteId)
            ->where('approval_status', Expense::STATUS_PENDING)
            ->count();

        $recentTransactions = $sessionId
            ? InstituteTransaction::where('institute_id', $instituteId)
                ->where('academic_session_id', $sessionId)
                ->orderByDesc('date')->orderByDesc('id')
                ->limit(15)->get()
            : collect();

        // ── Phase 7: Smart Alerts & Widgets ──────────────────────────────

        // Cash in hand = cash fee income - cash expenses - contra (cash deposited to bank)
        $cashIncome = (float) FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereRaw('LOWER(payment_mode) = ?', ['cash'])
            ->where('is_cancelled', false)
            ->sum('paid_amount');

        $cashExpenses = (float) Expense::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereRaw('LOWER(payment_mode) = ?', ['cash'])
            ->whereNotIn('approval_status', [Expense::STATUS_PENDING, Expense::STATUS_REJECTED])
            ->where('is_reversed', false)
            ->sum('amount');

        $contraTotal = (float) ContraEntry::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('amount');

        $cashInHand = $cashIncome - $cashExpenses - $contraTotal;

        // Bank-wise balance (non-cash income + contra deposits - bank expenses)
        $bankBalances = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order')->get()
            ->map(function ($bank) use ($instituteId, $sessionId) {
                $nonCashIncome = (float) FeeInvoice::where('institute_id', $instituteId)
                    ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                    ->where('bank_account_id', $bank->id)
                    ->where('is_cancelled', false)
                    ->sum('paid_amount');

                $contraIn = (float) ContraEntry::where('institute_id', $instituteId)
                    ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                    ->where('to_bank_account_id', $bank->id)
                    ->sum('amount');

                return [
                    'bank'      => $bank,
                    'income'    => $nonCashIncome,
                    'contra_in' => $contraIn,
                    'balance'   => $nonCashIncome + $contraIn,
                ];
            })->filter(fn($b) => $b['balance'] > 0);

        // Cheque alerts
        $chequePending = ChequePayment::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->pending()->get();

        $pendingChequesCount = $chequePending->count();
        $pendingChequesTotal = (float) $chequePending->sum('amount');

        $staleChequesCount = $chequePending
            ->filter(fn($c) => $c->cheque_date && $c->cheque_date->lt(now()->subDays(7)))
            ->count();

        $bouncedThisMonth = ChequePayment::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->bounced()
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // Month-end reconciliation reminder (last 5 days of month)
        $isMonthEnd = now()->day >= (now()->daysInMonth - 4);

        // Large transactions today (> 1,00,000)
        $largeTxnToday = InstituteTransaction::where('institute_id', $instituteId)
            ->whereDate('date', now()->toDateString())
            ->where(fn($q) => $q->where('credit', '>', 100000)->orWhere('debit', '>', 100000))
            ->count();

        return view('institute.finance.wallet.dashboard', compact(
            'sessions', 'activeSession', 'sessionId', 'summary',
            'recentTransactions', 'threshold', 'lowBalance',
            'monthlyData', 'pendingApprovals',
            // Phase 7
            'cashInHand', 'cashIncome', 'cashExpenses', 'contraTotal',
            'bankBalances',
            'pendingChequesCount', 'pendingChequesTotal',
            'staleChequesCount', 'bouncedThisMonth',
            'isMonthEnd', 'largeTxnToday'
        ))->with('sourceLabels', self::$sourceLabels);
    }

    public function ledger(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();

        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        $today = now()->toDateString();
        // 'filtered' flag = form was submitted (even with empty dates = "All")
        // No flag = first page load → default to today
        if ($request->has('filtered')) {
            $from = $request->input('from') ?: null;
            $to   = $request->input('to')   ?: null;
        } else {
            $from = $today;
            $to   = $today;
        }
        $sourceType    = $request->input('source_type');
        $paymentType   = $request->input('payment_type');   // cash | non_cash | ''
        $bankAccountId = $request->input('bank_account_id');
        $flow          = $request->input('flow');           // income | expense | ''
        $amountMin     = $request->input('amount_min');
        $amountMax     = $request->input('amount_max');
        $collectorKey  = $request->input('collector');      // staff_ID | center_ID | ''
        $export        = $request->input('export');

        // Collectors for dropdown (staff + centers who collected fees)
        $collectorOptions = collect();
        $feeStaffIds = FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereNotNull('collected_by_staff_id')->distinct()->pluck('collected_by_staff_id');
        $feeCenterIds = FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->whereNotNull('collected_by_center_id')->distinct()->pluck('collected_by_center_id');

        StaffMember::whereIn('id', $feeStaffIds)->get()
            ->each(fn($s) => $collectorOptions->push(['key' => 'staff_' . $s->id, 'label' => $s->name . ' (Staff)']));
        Center::whereIn('id', $feeCenterIds)->get()
            ->each(fn($c) => $collectorOptions->push(['key' => 'center_' . $c->id, 'label' => $c->name . ' (Center)']));

        // Bank accounts for filter dropdown
        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')->get();

        // Opening balance = cl_bal of last transaction before $from
        $openingBalance = null;
        if ($from) {
            $openingBalance = (float) (InstituteTransaction::where('institute_id', $instituteId)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->whereDate('date', '<', $from)
                ->orderByDesc('date')->orderByDesc('id')
                ->value('cl_bal') ?? 0);
        }

        $query = InstituteTransaction::where('institute_id', $instituteId)->with('session');

        if ($sessionId)    { $query->where('academic_session_id', $sessionId); }
        if ($from)         { $query->whereDate('date', '>=', $from); }
        if ($to)           { $query->whereDate('date', '<=', $to); }
        if ($sourceType)   { $query->where('source_type', $sourceType); }

        // Phase 2 filters
        if ($flow === 'income')  { $query->where('type', InstituteTransaction::CREDIT); }
        if ($flow === 'expense') { $query->where('type', InstituteTransaction::DEBIT); }

        if ($paymentType === 'cash') {
            $query->where(function ($q) {
                $q->where('source_type', '!=', 'fee_invoice')
                  ->orWhereHas('invoice', fn($q) => $q->where('payment_mode', 'CASH'));
            });
        } elseif ($paymentType === 'non_cash') {
            $query->where('source_type', 'fee_invoice')
                  ->whereHas('invoice', fn($q) => $q->where('payment_mode', '!=', 'CASH'));
        }

        if ($bankAccountId) {
            $query->where('source_type', 'fee_invoice')
                  ->whereHas('invoice', fn($q) => $q->where('bank_account_id', $bankAccountId));
        }

        if ($amountMin) {
            // Only compare against the column that actually holds the value for this row's direction
            $query->where(fn($q) => $q
                ->where(fn($q) => $q->where('type', InstituteTransaction::CREDIT)->where('credit', '>=', $amountMin))
                ->orWhere(fn($q) => $q->where('type', InstituteTransaction::DEBIT)->where('debit', '>=', $amountMin)));
        }
        if ($amountMax) {
            $query->where(fn($q) => $q
                ->where(fn($q) => $q->where('type', InstituteTransaction::CREDIT)->where('credit', '<=', $amountMax))
                ->orWhere(fn($q) => $q->where('type', InstituteTransaction::DEBIT)->where('debit', '<=', $amountMax)));
        }

        if ($collectorKey && str_contains($collectorKey, '_')) {
            [$type, $id] = explode('_', $collectorKey, 2);
            $query->where('source_type', 'fee_invoice')
                  ->whereHas('invoice', function ($q) use ($type, $id) {
                      if ($type === 'staff')  { $q->where('collected_by_staff_id',  $id); }
                      if ($type === 'center') { $q->where('collected_by_center_id', $id); }
                  });
        }

        if ($export) {
            $allTx         = $query->orderByDesc('date')->orderByDesc('id')->get();
            $allSd         = $this->enrichTransactions($allTx->all());
            $sessionName   = $sessions->firstWhere('id', $sessionId)?->name ?? '';
            $instituteName = Institute::find($instituteId)?->name ?? '';
            return $this->exportLedger($allTx, $allSd, $sessionName, $instituteName, $from, $to, $export);
        }

        $transactions = $query->orderByDesc('date')->orderByDesc('id')->paginate(50)->withQueryString();

        $allSourceTypes = InstituteTransaction::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->distinct()->pluck('source_type')->filter()->values();

        $sourceData = $this->enrichTransactions($transactions->items());

        return view('institute.finance.wallet.ledger', compact(
            'sessions', 'sessionId', 'from', 'to', 'sourceType',
            'paymentType', 'bankAccountId', 'bankAccounts',
            'flow', 'amountMin', 'amountMax',
            'collectorKey', 'collectorOptions',
            'transactions', 'allSourceTypes', 'sourceData', 'openingBalance'
        ))->with('sourceLabels', self::$sourceLabels);
    }

    private function exportLedger(
        Collection $transactions,
        array $sourceData,
        string $sessionName,
        string $instituteName,
        ?string $from,
        ?string $to,
        string $format
    ) {
        $filterLabel = $sessionName
            . ($from ? ' | From: ' . $from : '')
            . ($to   ? ' | To: '   . $to   : '');

        $rows = $transactions->map(function ($tx, $i) use ($sourceData) {
            $sd = $sourceData[$tx->id] ?? [];
            return [
                $i + 1,
                $tx->session?->name ?? '-',
                $tx->date->format('d-m-Y'),
                $tx->des,
                $sd['category']     ?? '-',
                $sd['receipt_no']   ?? '-',
                $sd['payment_ref']  ?? '-',
                $sd['pay_type']     ?? '-',
                $sd['bank_account'] ?? '-',
                $tx->credit > 0 ? (float) $tx->credit : '',
                $tx->debit  > 0 ? (float) $tx->debit  : '',
                (float) $tx->op_bal,
                (float) $tx->cl_bal,
                $sd['user_name']    ?? '-',
            ];
        });

        $headers = ['#', 'Session', 'Date', 'Remark', 'Category', 'Receipt No.', 'Ref. No. (UTR/Cheque)', 'Type', 'Bank Account', 'Income', 'Expense', 'Op. Balance', 'Balance', 'User Name'];

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, 'wallet-ledger.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'excel') {
            return Excel::download(
                new WalletLedgerExport($rows),
                'wallet-ledger.xlsx'
            );
        }

        // pdf
        $pdf = Pdf::loadView('institute.finance.wallet.ledger-pdf', compact(
            'transactions', 'sourceData', 'filterLabel', 'instituteName'
        ))->setPaper('a3', 'landscape');

        return $pdf->download('wallet-ledger.pdf');
    }

    private function enrichTransactions(array $txItems): array
    {
        $txCollection = collect($txItems);

        $feeIds    = $txCollection->where('source_type', 'fee_invoice')->pluck('source_id')->unique();
        $expIds    = $txCollection->whereIn('source_type', ['expense', 'expense_reversal'])->pluck('source_id')->unique();
        $incIds    = $txCollection->where('source_type', 'manual_income')->pluck('source_id')->unique();
        $salIds    = $txCollection->whereIn('source_type', ['salary', 'salary_reversal'])->pluck('source_id')->unique();

        $feeMap = FeeInvoice::with('bankAccount')->whereIn('id', $feeIds)->get()->keyBy('id');

        // Fee heads: group items by invoice_id, build summary string
        $feeItemsMap = collect();
        if ($feeIds->isNotEmpty()) {
            $feeItemsMap = FeeInvoiceItem::whereIn('fee_invoice_id', $feeIds)
                ->get()
                ->groupBy('fee_invoice_id')
                ->map(fn($items) => $items->map(fn($i) => $i->fee_name . ': ₹' . number_format((float)$i->amount, 0))->implode(' | '));
        }
        $expMap = Expense::with('categoryL1', 'categoryL2', 'vendor')
            ->whereIn('id', $expIds)->get()->keyBy('id');
        $incMap = InstituteManualIncome::with('category')
            ->whereIn('id', $incIds)->get()->keyBy('id');
        $salMap = SalaryRecord::whereIn('id', $salIds)->get()->keyBy('id');

        $byUserIds = $txCollection->pluck('by_user_id')->filter()->unique();
        $webUsers  = User::whereIn('id', $byUserIds)->pluck('name', 'id');
        $staffUsers = StaffMember::whereIn('id', $byUserIds)->pluck('name', 'id');

        $result = [];
        foreach ($txItems as $tx) {
            $uid = $tx->by_user_id;
            $userName = $uid ? ($webUsers[$uid] ?? $staffUsers[$uid] ?? '-') : '-';

            $d = [
                'category'    => '-',
                'receipt_no'  => '-',
                'payment_ref' => '-',
                'pay_type'    => '-',
                'bank_account'=> '-',
                'fee_heads'   => '',
                'user_name'   => $userName,
            ];

            switch ($tx->source_type) {
                case 'fee_invoice':
                    $inv  = $feeMap[$tx->source_id] ?? null;
                    $mode = strtoupper($inv?->payment_mode ?? '');
                    $d['category']     = 'INCOME - FEE';
                    $d['receipt_no']   = $inv?->invoice_no ?? '-';
                    $d['pay_type']     = $mode ?: '-';
                    $d['payment_ref']  = ($inv?->transaction_ref && $inv->transaction_ref !== '')
                                            ? $inv->transaction_ref : '-';
                    $d['bank_account'] = ($mode && $mode !== 'CASH')
                                            ? ($inv?->bankAccount?->account_name
                                               ?? $inv?->bankAccount?->bank_name
                                               ?? '-')
                                            : '-';
                    $d['fee_heads']    = $feeItemsMap[$tx->source_id] ?? '';
                    if ($inv?->collected_by) {
                        $d['user_name'] = $inv->collected_by;
                    }
                    break;

                case 'expense':
                    $exp   = $expMap[$tx->source_id] ?? null;
                    $parts = array_filter([
                        'EXPENSE',
                        $exp?->categoryL1?->name,
                        $exp?->categoryL2?->name ?? ($exp?->vendor?->name ?? ($exp?->vendor_name ?? null)),
                    ]);
                    $d['category']   = implode(' - ', $parts) ?: 'EXPENSE';
                    $d['receipt_no'] = $exp?->bill_no ?? '-';
                    $d['pay_type']   = strtoupper($exp?->payment_mode ?? '-');
                    break;

                case 'expense_reversal':
                    $exp   = $expMap[$tx->source_id] ?? null;
                    $parts = array_filter([
                        'EXPENSE REVERSAL',
                        $exp?->categoryL1?->name,
                        $exp?->categoryL2?->name ?? ($exp?->vendor?->name ?? null),
                    ]);
                    $d['category']   = implode(' - ', $parts) ?: 'EXPENSE REVERSAL';
                    $d['receipt_no'] = $exp?->bill_no ?? '-';
                    $d['pay_type']   = strtoupper($exp?->payment_mode ?? '-');
                    break;

                case 'manual_income':
                    $inc             = $incMap[$tx->source_id] ?? null;
                    $d['category']   = 'INCOME - ' . strtoupper($inc?->category?->name ?? 'MANUAL');
                    $d['receipt_no'] = $inc?->receipt_no ?? '-';
                    break;

                case 'salary':
                    $sal             = $salMap[$tx->source_id] ?? null;
                    $d['category']   = 'EXPENSE - SALARY';
                    $d['pay_type']   = strtoupper($sal?->payment_mode ?? '-');
                    break;

                case 'salary_reversal':
                    $d['category'] = 'SALARY REVERSAL';
                    break;

                case 'library_fine':
                    $d['category'] = 'INCOME - LIBRARY FINE';
                    break;

                default:
                    $d['category'] = strtoupper($tx->source_type ?? '-');
            }

            $result[$tx->id] = $d;
        }

        return $result;
    }

    private static array $paymentModeLabels = [
        'cash'   => 'Cash',
        'upi'    => 'UPI',
        'online' => 'Online',
        'cheque' => 'Cheque',
        'dd'     => 'DD',
        'neft'   => 'NEFT/RTGS',
        'rtgs'   => 'RTGS',
    ];

    private static array $paymentModeColors = [
        'cash'   => 'success',
        'upi'    => 'primary',
        'online' => 'info',
        'cheque' => 'warning',
        'dd'     => 'secondary',
        'neft'   => 'dark',
        'rtgs'   => 'dark',
    ];

    public function incomeReport(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions  = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        // Phase 1: enhanced filters
        $today      = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        if ($request->has('filtered')) {
            $from = $request->input('from') ?: null;
            $to   = $request->input('to')   ?: null;
        } else {
            $from = $monthStart;
            $to   = $today;
        }
        $paymentType   = $request->input('payment_type');   // cash | non_cash | ''
        $collectorType = $request->input('collector_type'); // staff | center | partner | ''
        $export        = $request->input('export');

        // ── Existing: InstituteTransaction base (all income sources) ──────
        $txBase = InstituteTransaction::where('institute_id', $instituteId)
            ->where('type', InstituteTransaction::CREDIT)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('date', '<=', $to));

        $bySource = (clone $txBase)
            ->selectRaw('source_type, SUM(credit) as total, COUNT(*) as count')
            ->groupBy('source_type')->orderByDesc('total')->get();

        $grandTotal = (float) $bySource->sum('total');

        $manualByCategory = InstituteManualIncome::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('date', '<=', $to))
            ->selectRaw('income_category_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('income_category_id')->with('category')->orderByDesc('total')->get();

        $monthWise = (clone $txBase)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(credit) as total")
            ->groupBy('month')->orderBy('month')->get();

        // ── FeeInvoice base (for Phases 2-4) ─────────────────────────────
        $feeBase = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where('paid_amount', '>', 0)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('payment_date', '<=', $to));

        if ($paymentType === 'cash') {
            $feeBase->whereRaw('LOWER(payment_mode) = ?', ['cash']);
        } elseif ($paymentType === 'non_cash') {
            $feeBase->whereRaw('LOWER(payment_mode) != ?', ['cash'])
                    ->where('payment_mode', '!=', '');
        }

        if ($collectorType === 'staff')   { $feeBase->whereNotNull('collected_by_staff_id'); }
        if ($collectorType === 'center')  { $feeBase->whereNotNull('collected_by_center_id'); }
        if ($collectorType === 'partner') { $feeBase->whereNotNull('collected_by_partner_id'); }

        // Phase 2: Payment mode summary + Bank-wise breakdown
        $feeByMode = (clone $feeBase)
            ->selectRaw('payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('payment_mode')->orderByDesc('total')->get()
            ->map(fn($r) => (object)['mode' => strtolower($r->payment_mode ?? 'other'), 'cnt' => $r->cnt, 'total' => (float)$r->total]);

        $feeTotal = (float) $feeByMode->sum('total');

        $bankWiseRows = (clone $feeBase)
            ->whereNotNull('bank_account_id')
            ->selectRaw('bank_account_id, payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('bank_account_id', 'payment_mode')
            ->get()
            ->groupBy('bank_account_id');

        $bankIds        = $bankWiseRows->keys()->filter();
        $bankAccountsMap = InstituteBankAccount::whereIn('id', $bankIds)->get()->keyBy('id');

        // Phase 3: Staff-wise
        $staffWiseRows = (clone $feeBase)
            ->whereNotNull('collected_by_staff_id')
            ->selectRaw('collected_by_staff_id, payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('collected_by_staff_id', 'payment_mode')
            ->get()->groupBy('collected_by_staff_id');

        $staffMap = StaffMember::whereIn('id', $staffWiseRows->keys()->filter())
            ->pluck('name', 'id');

        // Phase 4: Center-wise + Partner-wise
        $centerWiseRows = (clone $feeBase)
            ->whereNotNull('collected_by_center_id')
            ->selectRaw('collected_by_center_id, payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('collected_by_center_id', 'payment_mode')
            ->get()->groupBy('collected_by_center_id');

        $centerMap = Center::whereIn('id', $centerWiseRows->keys()->filter())
            ->pluck('name', 'id');

        $partnerWiseRows = (clone $feeBase)
            ->whereNotNull('collected_by_partner_id')
            ->selectRaw('collected_by_partner_id, payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('collected_by_partner_id', 'payment_mode')
            ->get()->groupBy('collected_by_partner_id');

        $partnerMap = ChannelPartner::whereIn('id', $partnerWiseRows->keys()->filter())
            ->get()->keyBy('id');

        $instituteName = Institute::find($instituteId)?->name ?? '';

        // Phase 5: Export
        if ($export) {
            return $this->exportIncomeReport(
                $instituteName, $sessions->firstWhere('id', $sessionId)?->name ?? '',
                $from, $to, $grandTotal,
                $bySource, $manualByCategory,
                $feeByMode, $feeTotal,
                $bankWiseRows, $bankAccountsMap,
                $staffWiseRows, $staffMap,
                $centerWiseRows, $centerMap,
                $partnerWiseRows, $partnerMap,
                $export
            );
        }

        return view('institute.finance.wallet.reports.income-report', compact(
            'sessions', 'sessionId', 'from', 'to',
            'paymentType', 'collectorType',
            'bySource', 'grandTotal', 'manualByCategory', 'monthWise',
            'feeByMode', 'feeTotal',
            'bankWiseRows', 'bankAccountsMap',
            'staffWiseRows', 'staffMap',
            'centerWiseRows', 'centerMap',
            'partnerWiseRows', 'partnerMap',
            'instituteName'
        ))->with([
            'sourceLabels'    => self::$sourceLabels,
            'modeLabels'      => self::$paymentModeLabels,
            'modeColors'      => self::$paymentModeColors,
        ]);
    }

    private function exportIncomeReport(
        string $instituteName, string $sessionName,
        ?string $from, ?string $to, float $grandTotal,
        $bySource, $manualByCategory,
        $feeByMode, float $feeTotal,
        $bankWiseRows, $bankAccountsMap,
        $staffWiseRows, $staffMap,
        $centerWiseRows, $centerMap,
        $partnerWiseRows, $partnerMap,
        string $format
    ) {
        $filterLabel = $sessionName
            . ($from ? ' | From: ' . $from : '')
            . ($to   ? ' | To: '   . $to   : '');

        if ($format === 'pdf') {
            $sourceLabels = self::$sourceLabels;
            $modeLabels   = self::$paymentModeLabels;

            $pdf = Pdf::loadView('institute.finance.wallet.reports.income-report-pdf', compact(
                'instituteName', 'filterLabel', 'grandTotal',
                'bySource', 'manualByCategory',
                'feeByMode', 'feeTotal',
                'bankWiseRows', 'bankAccountsMap',
                'staffWiseRows', 'staffMap',
                'centerWiseRows', 'centerMap',
                'partnerWiseRows', 'partnerMap',
                'sourceLabels', 'modeLabels'
            ))->setPaper('a3', 'landscape');

            return $pdf->download('income-report.pdf');
        }

        // Build flat rows for CSV/Excel
        $sections = $this->buildIncomeReportRows(
            $bySource, $manualByCategory, $feeByMode,
            $bankWiseRows, $bankAccountsMap,
            $staffWiseRows, $staffMap,
            $centerWiseRows, $centerMap,
            $partnerWiseRows, $partnerMap
        );

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($sections, $filterLabel, $instituteName, $grandTotal) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [$instituteName . ' — Income Report', $filterLabel, 'Grand Total: ' . $grandTotal]);
                fputcsv($out, []);
                foreach ($sections as $section) {
                    fputcsv($out, [$section['title']]);
                    fputcsv($out, $section['headers']);
                    foreach ($section['rows'] as $row) { fputcsv($out, $row); }
                    fputcsv($out, []);
                }
                fclose($out);
            }, 'income-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return Excel::download(
            new IncomeReportExport($instituteName, $filterLabel, $grandTotal, $sections),
            'income-report.xlsx'
        );
    }

    private function buildIncomeReportRows(
        $bySource, $manualByCategory, $feeByMode,
        $bankWiseRows, $bankAccountsMap,
        $staffWiseRows, $staffMap,
        $centerWiseRows, $centerMap,
        $partnerWiseRows, $partnerMap
    ): array {
        $modeLabels = self::$paymentModeLabels;

        $sections = [];

        // Income by source
        $sections[] = [
            'title'   => 'Income by Source',
            'headers' => ['Source', 'Count', 'Amount'],
            'rows'    => $bySource->map(fn($r) => [
                self::$sourceLabels[$r->source_type] ?? $r->source_type,
                $r->count, number_format((float)$r->total, 2),
            ])->toArray(),
        ];

        // Fee payment mode
        $sections[] = [
            'title'   => 'Fee Collection by Payment Mode',
            'headers' => ['Mode', 'Transactions', 'Amount'],
            'rows'    => $feeByMode->map(fn($r) => [
                $modeLabels[$r->mode] ?? ucfirst($r->mode),
                $r->cnt, number_format($r->total, 2),
            ])->toArray(),
        ];

        // Bank-wise
        $bankRows = [];
        foreach ($bankWiseRows as $bankId => $rows) {
            $bankName = $bankAccountsMap[$bankId]?->account_name
                ?? $bankAccountsMap[$bankId]?->bank_name ?? 'Bank ' . $bankId;
            foreach ($rows as $r) {
                $bankRows[] = [$bankName, $modeLabels[strtolower($r->payment_mode ?? '')] ?? $r->payment_mode, $r->cnt, number_format((float)$r->total, 2)];
            }
        }
        $sections[] = ['title' => 'Bank-wise Breakdown', 'headers' => ['Bank', 'Mode', 'Transactions', 'Amount'], 'rows' => $bankRows];

        // Staff-wise
        $staffRows = [];
        foreach ($staffWiseRows as $staffId => $rows) {
            $name = $staffMap[$staffId] ?? 'Staff #' . $staffId;
            foreach ($rows as $r) {
                $staffRows[] = [$name, $modeLabels[strtolower($r->payment_mode ?? '')] ?? $r->payment_mode, $r->cnt, number_format((float)$r->total, 2)];
            }
        }
        $sections[] = ['title' => 'Staff-wise Collection', 'headers' => ['Staff Name', 'Mode', 'Transactions', 'Amount'], 'rows' => $staffRows];

        // Center-wise
        $centerRows = [];
        foreach ($centerWiseRows as $centerId => $rows) {
            $name = $centerMap[$centerId] ?? 'Center #' . $centerId;
            foreach ($rows as $r) {
                $centerRows[] = [$name, $modeLabels[strtolower($r->payment_mode ?? '')] ?? $r->payment_mode, $r->cnt, number_format((float)$r->total, 2)];
            }
        }
        $sections[] = ['title' => 'Center-wise Collection', 'headers' => ['Center Name', 'Mode', 'Transactions', 'Amount'], 'rows' => $centerRows];

        // Partner-wise
        $partnerRows = [];
        foreach ($partnerWiseRows as $partnerId => $rows) {
            $partner = $partnerMap[$partnerId] ?? null;
            $name    = $partner?->name ?? 'Partner #' . $partnerId;
            foreach ($rows as $r) {
                $partnerRows[] = [$name, $modeLabels[strtolower($r->payment_mode ?? '')] ?? $r->payment_mode, $r->cnt, number_format((float)$r->total, 2)];
            }
        }
        $sections[] = ['title' => 'Partner-wise Collection', 'headers' => ['Partner', 'Mode', 'Transactions', 'Amount'], 'rows' => $partnerRows];

        return $sections;
    }

    public function expenseReport(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();

        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        $from   = $request->input('from');
        $to     = $request->input('to');
        $l1Id   = $request->input('l1_id');
        $export = $request->input('export');

        // Only approved/auto-approved, non-reversed expenses
        $baseQuery = Expense::where('institute_id', $instituteId)
            ->whereNotIn('approval_status', [Expense::STATUS_PENDING, Expense::STATUS_REJECTED])
            ->where('is_reversed', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('expense_date', '<=', $to));

        $grandTotal = (float) (clone $baseQuery)->sum('amount');

        $byL1 = (clone $baseQuery)
            ->selectRaw('expense_category_l1_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('expense_category_l1_id')
            ->orderByDesc('total')
            ->with('categoryL1')
            ->get();

        $byL2 = collect();
        $selectedL1 = null;
        if ($l1Id) {
            $selectedL1 = ExpenseCategoryL1::find($l1Id);
            $byL2 = (clone $baseQuery)
                ->where('expense_category_l1_id', $l1Id)
                ->selectRaw('expense_category_l2_id, expense_vendor_id, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('expense_category_l2_id', 'expense_vendor_id')
                ->orderByDesc('total')
                ->with('categoryL2', 'vendor')
                ->get();
        }

        $monthWise = (clone $baseQuery)
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $l1Categories = ExpenseCategoryL1::where('institute_id', $instituteId)->active()->orderBy('name')->get();

        $sessionName  = $sessions->firstWhere('id', $sessionId)?->name ?? '';
        $filterLabel  = trim(($sessionName ? "Session: {$sessionName}" : '')
            . ($from ? "  From: {$from}" : '')
            . ($to   ? "  To: {$to}"     : ''));
        $instituteName = Institute::find($instituteId)?->name ?? 'Institute';

        if ($export === 'csv') {
            $rows   = $byL1->map(fn($r) => [
                $r->categoryL1?->name ?? 'Uncategorized',
                $r->count,
                round((float)$r->total, 2),
                $grandTotal > 0 ? round((float)$r->total / $grandTotal * 100, 1) . '%' : '0%',
            ]);
            $headers = ['Category (L1)', 'Count', 'Amount (Rs)', '% of Total'];

            $filename = 'expense-report-' . now()->format('Y-m-d') . '.csv';
            $callback = function () use ($headers, $rows, $instituteName, $filterLabel, $grandTotal, $monthWise) {
                $out = fopen('php://output', 'w');
                fputcsv($out, [$instituteName . ' — Expense Report']);
                fputcsv($out, [$filterLabel]);
                fputcsv($out, ['Total Expense', 'Rs ' . number_format($grandTotal, 2)]);
                fputcsv($out, []);
                fputcsv($out, ['By Category']);
                fputcsv($out, ['Category (L1)', 'Count', 'Amount (Rs)', '% of Total']);
                foreach ($rows as $r) { fputcsv($out, $r); }
                if ($monthWise->isNotEmpty()) {
                    fputcsv($out, []);
                    fputcsv($out, ['Month-wise']);
                    fputcsv($out, ['Month', 'Amount (Rs)']);
                    foreach ($monthWise as $m) {
                        fputcsv($out, [\Carbon\Carbon::parse($m->month . '-01')->format('F Y'), round((float)$m->total, 2)]);
                    }
                }
                fclose($out);
            };
            return response()->streamDownload($callback, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($export === 'pdf') {
            $pdf = Pdf::loadView('institute.finance.wallet.reports.expense-report-pdf', compact(
                'instituteName', 'filterLabel', 'grandTotal', 'byL1', 'byL2', 'selectedL1', 'monthWise'
            ))->setPaper('a4', 'portrait');
            return $pdf->download('expense-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('institute.finance.wallet.reports.expense-report', compact(
            'sessions', 'sessionId', 'from', 'to',
            'byL1', 'byL2', 'grandTotal', 'monthWise',
            'l1Categories', 'selectedL1', 'l1Id'
        ));
    }

    public function sessionComparison()
    {
        $instituteId = $this->instituteId();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('start_date')->get();

        // For each session get wallet summary
        $comparison = $sessions->map(function ($session) use ($instituteId) {
            $wallet = InstituteWallet::where('institute_id', $instituteId)
                ->where('academic_session_id', $session->id)->first();

            $txBase = InstituteTransaction::where('institute_id', $instituteId)
                ->where('academic_session_id', $session->id);

            $totalIncome  = (float) (clone $txBase)->where('type', InstituteTransaction::CREDIT)->sum('credit');
            $totalExpense = (float) (clone $txBase)->where('type', InstituteTransaction::DEBIT)->sum('debit');
            $balance      = (float) ($wallet?->main_b ?? 0);

            return [
                'session'       => $session,
                'total_income'  => $totalIncome,
                'total_expense' => $totalExpense,
                'balance'       => $balance,
                'surplus'       => $totalIncome - $totalExpense,
            ];
        });

        return view('institute.finance.wallet.reports.session-comparison', compact('comparison'));
    }

    public function updateThreshold(Request $request)
    {
        $data = $request->validate([
            'wallet_low_balance_threshold' => 'required|numeric|min:0',
        ]);

        $instituteId = $this->instituteId();

        FinanceSetting::where('institute_id', $instituteId)
            ->update(['wallet_low_balance_threshold' => $data['wallet_low_balance_threshold']]);

        return back()->with('success', 'Low balance alert threshold saved successfully.');
    }

    public function expenseCategoryLedger(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions     = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $l1Categories = ExpenseCategoryL1::where('institute_id', $instituteId)->active()->orderBy('name')->get();

        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        $l1Id = $request->input('l1_id');
        $l2Id = $request->input('l2_id');
        $from = $request->input('from');
        $to   = $request->input('to');

        $selectedL1  = $l1Id ? ExpenseCategoryL1::where('institute_id', $instituteId)->find($l1Id) : null;
        $l2Options   = $l1Id
            ? ExpenseCategoryL2::where('l1_id', $l1Id)->orderBy('name')->get()
            : collect();

        $rows        = collect();
        $grandDebit  = 0.0;
        $grandCredit = 0.0;

        if ($l1Id) {
            $expenses = Expense::where('institute_id', $instituteId)
                ->where('expense_category_l1_id', $l1Id)
                ->whereNotIn('approval_status', [Expense::STATUS_PENDING, Expense::STATUS_REJECTED])
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->when($l2Id,      fn($q) => $q->where('expense_category_l2_id', $l2Id))
                ->when($from,      fn($q) => $q->whereDate('expense_date', '>=', $from))
                ->when($to,        fn($q) => $q->whereDate('expense_date', '<=', $to))
                ->with('categoryL1', 'categoryL2', 'vendor', 'session')
                ->orderBy('expense_date')->orderBy('id')
                ->get();

            $running = 0.0;
            foreach ($expenses as $exp) {
                $parts    = array_filter([
                    $exp->categoryL1?->name,
                    $exp->categoryL2?->name ?? ($exp->vendor?->name ?? ($exp->vendor_name ?? null)),
                ]);
                $category = 'EXPENSE - ' . (implode(' - ', $parts) ?: '-');

                if ($exp->is_reversed) {
                    $credit = (float) $exp->amount;
                    $debit  = 0.0;
                } else {
                    $debit  = (float) $exp->amount;
                    $credit = 0.0;
                }

                $opBal   = $running;
                $running = round($running + $debit - $credit, 2);

                $grandDebit  += $debit;
                $grandCredit += $credit;

                $rows->push([
                    'expense'    => $exp,
                    'category'   => $category,
                    'receipt_no' => $exp->bill_no ?? '-',
                    'pay_type'   => strtoupper($exp->payment_mode ?? '-'),
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'op_bal'     => $opBal,
                    'balance'    => $running,
                ]);
            }
        }

        return view('institute.finance.wallet.expense-category-ledger', compact(
            'sessions', 'sessionId', 'l1Categories', 'selectedL1',
            'l1Id', 'l2Id', 'l2Options', 'from', 'to',
            'rows', 'grandDebit', 'grandCredit'
        ));
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function getMonthlyData(int $instituteId, ?int $sessionId): array
    {
        if (!$sessionId) {
            return ['labels' => [], 'income' => [], 'expense' => []];
        }

        $rows = InstituteTransaction::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month,
                SUM(CASE WHEN type = ? AND source_type NOT IN ('expense_reversal','salary_reversal') THEN credit ELSE 0 END) as income,
                SUM(CASE WHEN type = ? THEN debit ELSE 0 END)
                    - SUM(CASE WHEN source_type = 'expense_reversal' THEN credit ELSE 0 END)
                    - SUM(CASE WHEN source_type = 'salary_reversal'  THEN credit ELSE 0 END) as expense",
                [InstituteTransaction::CREDIT, InstituteTransaction::DEBIT]
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels'  => $rows->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m . '-01')->format('M Y'))->toArray(),
            'income'  => $rows->pluck('income')->map(fn($v) => round((float)$v, 2))->toArray(),
            'expense' => $rows->pluck('expense')->map(fn($v) => round((float)$v, 2))->toArray(),
        ];
    }
}
