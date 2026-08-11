<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\CoursePart;
use App\Models\DailyReportHeader;
use App\Models\EmployeeSalaryDisbursement;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use App\Models\Institute;
use App\Models\InstituteBankAccount;
use App\Models\InstituteManualIncome;
use App\Models\InstituteTransaction;
use App\Models\ReportParticular;
use App\Models\SalaryRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DailyRegisterController extends Controller
{
    // Fixed non-cash payment mode columns for the PDF's bank-wise pivot table —
    // matches FeeInvoice.payment_mode's enum values, excluding 'cash' (shown
    // separately as "By Hand Receipt").
    private const NON_CASH_MODES = ['UPI', 'ONLINE', 'NEFT', 'RTGS', 'CHEQUE', 'DD'];

    private function instituteId(): int
    {
        // Group-Admin has no institute of their own — resolve from the {institute}
        // route parameter instead, scoped to institutes owned by their group.
        if (auth()->guard('group_admin')->check()) {
            $routeInstitute = request()->route('institute');
            $institute = $routeInstitute instanceof Institute ? $routeInstitute : Institute::find($routeInstitute);
            abort_unless(
                $institute && $institute->group_id === auth()->guard('group_admin')->user()->group_id,
                403
            );
            return (int) $institute->id;
        }

        return auth()->user()->institute_id;
    }

    private function isGroupAdminPanel(): bool
    {
        return auth()->guard('group_admin')->check();
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();
        $date = $request->input('date') ?: now()->toDateString();

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $sessionId = $request->input('session_id') ?: AcademicSession::viewSessionId($instituteId);

        $particulars = ReportParticular::where('institute_id', $instituteId)
            ->active()
            ->with(['course', 'feeType', 'incomeCategory', 'expenseCategoryL1'])
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $header = DailyReportHeader::firstOrNew(['institute_id' => $instituteId, 'report_date' => $date]);

        // Fee types already claimed by a flat particular (e.g. "TC Fee") must be
        // excluded from course-wise totals — otherwise an invoice item counted in
        // its own flat row also gets swept into "any invoice this student paid",
        // double-counting the same rupee.
        $claimedFeeTypeIds = $particulars->where('section', 'income')
            ->where('source_type', 'fee_invoice')
            ->pluck('fee_type_id')->filter()->unique()->values();

        $incomeRows = collect();
        $totalIncome = 0.0;
        foreach ($particulars->where('section', 'income') as $particular) {
            $row = $this->buildIncomeRow($particular, $instituteId, $sessionId, $date, $claimedFeeTypeIds);
            $incomeRows->push($row);
            $totalIncome += $row['amount'];
        }

        $expenseRows = collect();
        $totalExpense = 0.0;
        foreach ($particulars->where('section', 'expense') as $particular) {
            $row = $this->buildExpenseRow($particular, $instituteId, $sessionId, $date);
            $expenseRows->push($row);
            $totalExpense += $row['amount'];
        }

        // Course-wise rows carry a year_data map (1, 2, 3...) — the table needs a
        // fixed set of "Year" columns wide enough for whichever configured course
        // has the most years, so every row lines up under the same header.
        $maxYears = 0;
        foreach ($incomeRows as $row) {
            if (!empty($row['year_data'])) {
                $maxYears = max($maxYears, max(array_keys($row['year_data'])));
            }
        }
        $yearLabels = [];
        for ($y = 1; $y <= $maxYears; $y++) {
            $yearLabels[$y] = $this->yearLabel($y);
        }

        $receiptModeSplit = $this->receiptModeSplit($instituteId, $sessionId, $date);
        $receiptRange = $this->receiptNumberRange($instituteId, $sessionId, $date);

        $lastBalance = (float) (InstituteTransaction::where('institute_id', $instituteId)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('date', '<', $date)
            ->orderByDesc('date')->orderByDesc('id')
            ->value('cl_bal') ?? 0);

        $grandTotal = $lastBalance + $totalIncome - $totalExpense;

        $exportData = [
            'incomeRows'       => $incomeRows,
            'expenseRows'      => $expenseRows,
            'totalIncome'      => $totalIncome,
            'totalExpense'     => $totalExpense,
            'lastBalance'      => $lastBalance,
            'grandTotal'       => $grandTotal,
            'receiptModeSplit' => $receiptModeSplit,
            'receiptRange'     => $receiptRange,
            'header'           => $header,
            'date'             => $date,
            'instituteName'    => Institute::find($instituteId)?->name ?? '',
            'yearLabels'       => $yearLabels,
            'nonCashModes'     => self::NON_CASH_MODES,
            'showZeroRows'     => $request->boolean('show_zero'),
        ];

        if ($request->filled('export')) {
            return $this->export($request->input('export'), $exportData);
        }

        $dailyRegisterView = $this->isGroupAdminPanel() ? 'group_admin.reports.daily-report' : 'institute.finance.wallet.daily-register';

        return view($dailyRegisterView, $exportData + compact(
            'sessions', 'sessionId'
        ));
    }

    public function saveHeader(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'report_date'       => 'required|date',
            'book_no'           => 'nullable|string|max:50',
            'online_range_from' => 'nullable|string|max:50',
            'online_range_to'   => 'nullable|string|max:50',
            'sr_no'             => 'nullable|string|max:50',
            'activities'        => 'nullable|string|max:2000',
        ]);

        foreach (['book_no', 'online_range_from', 'online_range_to', 'sr_no', 'activities'] as $field) {
            if (!empty($data[$field])) {
                $data[$field] = strtoupper($data[$field]);
            }
        }

        DailyReportHeader::updateOrCreate(
            ['institute_id' => $instituteId, 'report_date' => $data['report_date']],
            $data + ['created_by' => auth()->id()]
        );

        return redirect()->route('finance.wallet.daily-register.index', ['date' => $data['report_date']])
            ->with('success', 'Header saved!');
    }

    // ── Row builders ─────────────────────────────────────────────────────

    private function buildIncomeRow(ReportParticular $particular, int $instituteId, ?int $sessionId, string $date, $claimedFeeTypeIds): array
    {
        if ($particular->source_type === 'fee_invoice' && $particular->course_id) {
            return $this->buildCourseRow($particular, $instituteId, $sessionId, $date, $claimedFeeTypeIds);
        }

        if ($particular->source_type === 'fee_invoice' && ($particular->fee_type_id || $particular->item_type)) {
            $base = FeeInvoiceItem::query()
                ->join('fee_invoices', 'fee_invoices.id', '=', 'fee_invoice_items.fee_invoice_id')
                ->where('fee_invoices.institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('fee_invoices.academic_session_id', $sessionId))
                ->where('fee_invoices.is_cancelled', false)
                ->whereDate('fee_invoices.payment_date', $date);

            if ($particular->fee_type_id) {
                $base->where('fee_invoice_items.fee_type_id', $particular->fee_type_id);
            } else {
                // Transport/Practical invoice items aren't linked to a FeeType at all —
                // only item_type identifies them (see TransportBillingController,
                // TransportAllocationController, PracticalFeeTokenController).
                $base->where('fee_invoice_items.item_type', $particular->item_type);
            }

            $count = (clone $base)->distinct()->count('fee_invoices.id');
            $amount = (float) (clone $base)->sum('fee_invoice_items.amount');

            return $this->row($particular, $count, $amount);
        }

        if ($particular->source_type === 'manual_income') {
            $base = InstituteManualIncome::where('institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where('income_category_id', $particular->income_category_id)
                ->whereDate('date', $date);

            return $this->row($particular, (clone $base)->count(), (float) (clone $base)->sum('amount'));
        }

        if ($particular->source_type === 'library_fine') {
            $base = InstituteTransaction::where('institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where('source_type', 'library_fine')
                ->whereDate('date', $date);

            return $this->row($particular, (clone $base)->count(), (float) (clone $base)->sum('credit'));
        }

        return $this->row($particular, 0, 0.0);
    }

    /**
     * One row per COURSE (not per course+year) — covers every year of the course,
     * so the admin doesn't need a separate particular for each year. The report
     * renders one "Year" table column per year, each holding that year's own
     * semester/trimester breakdown.
     */
    private function buildCourseRow(ReportParticular $particular, int $instituteId, ?int $sessionId, string $date, $claimedFeeTypeIds): array
    {
        $course = $particular->course;

        // Modular/certificate courses use CoursePart.year_number to count MONTHS,
        // not academic years (see CourseController::generateCourseParts) — a
        // per-month breakdown doesn't map to "1st Year/2nd Year" columns, so these
        // are aggregated as a single flat "Year 1" total instead.
        if ($course && $course->isShortTerm()) {
            $base = $this->courseIncomeItemsQuery($instituteId, $sessionId, $date, $claimedFeeTypeIds)
                ->whereHas('invoice.student.stream', fn ($q) => $q->where('course_id', $particular->course_id));

            $count = (clone $base)->distinct()->count('fee_invoice_id');
            $amount = (float) (clone $base)->sum('amount');

            return [
                'particular' => $particular,
                'name'       => $particular->name,
                'count'      => $count,
                'amount'     => $amount,
                'year_data'  => [1 => [
                    'sub_columns' => [['label' => 'Year', 'count' => $count, 'amount' => $amount]],
                    'count'       => $count,
                    'amount'      => $amount,
                ]],
            ];
        }

        $numYears = (int) (CoursePart::where('course_id', $particular->course_id)->max('year_number') ?: 1);

        $yearData = [];
        $totalCount = 0;
        $totalAmount = 0.0;

        for ($year = 1; $year <= $numYears; $year++) {
            $parts = CoursePart::where('course_id', $particular->course_id)
                ->where('year_number', $year)
                ->orderBy('part_number')
                ->get();

            [$subColumns, $yearCount, $yearAmount] = $this->partsBreakdown($parts, $particular, $course, $instituteId, $sessionId, $date, $claimedFeeTypeIds);

            $yearData[$year] = ['sub_columns' => $subColumns, 'count' => $yearCount, 'amount' => $yearAmount];
            $totalCount += $yearCount;
            $totalAmount += $yearAmount;
        }

        return [
            'particular' => $particular,
            'name'       => $particular->name,
            'count'      => $totalCount,
            'amount'     => $totalAmount,
            'year_data'  => $yearData,
        ];
    }

    /**
     * Base query for "general course income" invoice items — excludes anything
     * that belongs to its own dedicated particular (Transport/Practical item
     * types, or a FeeType already claimed by a flat particular), so the same
     * rupee is never counted in both a course row and a flat row.
     */
    private function courseIncomeItemsQuery(int $instituteId, ?int $sessionId, string $date, $claimedFeeTypeIds)
    {
        $query = FeeInvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($instituteId, $sessionId, $date) {
                $q->where('institute_id', $instituteId)
                    ->when($sessionId, fn ($q2) => $q2->where('academic_session_id', $sessionId))
                    ->where('is_cancelled', false)
                    ->whereDate('payment_date', $date);
            })
            ->whereNotIn('item_type', ['transport', 'practical']);

        if ($claimedFeeTypeIds->isNotEmpty()) {
            $query->where(fn ($q) => $q->whereNull('fee_type_id')->orWhereNotIn('fee_type_id', $claimedFeeTypeIds));
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Support\Collection<CoursePart>  $parts  the semester/trimester parts of a single year
     * @return array{0: array, 1: int, 2: float} [sub_columns, count, amount]
     */
    private function partsBreakdown($parts, ReportParticular $particular, $course, int $instituteId, ?int $sessionId, string $date, $claimedFeeTypeIds): array
    {
        // Course::semesterOptionsForYear() always stores semester=0 ("Annual") for
        // yearly/modular courses — it can't distinguish year in that case, so year
        // must be resolved via the student's own course_part_id instead. For
        // semester/trimester courses, FeeInvoice.semester stores the absolute
        // part_number directly and is set fresh at collection time, so it's the
        // more reliable signal (matches what the invoice list itself displays).
        $isYearlyType = !$course || $course->effectiveSemestersPerYear() <= 1;

        $subColumns = [];
        $count = 0;
        $amount = 0.0;

        foreach ($parts as $index => $part) {
            $base = $this->courseIncomeItemsQuery($instituteId, $sessionId, $date, $claimedFeeTypeIds);

            if ($isYearlyType) {
                $base->whereHas('invoice.student', fn ($q) => $q->where('course_part_id', $part->id));
            } else {
                $base->whereHas('invoice', fn ($q) => $q->where('semester', $part->part_number))
                     ->whereHas('invoice.student.stream', fn ($q) => $q->where('course_id', $particular->course_id));
            }

            $c = (clone $base)->distinct()->count('fee_invoice_id');
            $a = (float) (clone $base)->sum('amount');

            $subColumns[] = ['label' => $this->partLabel($course, $index), 'count' => $c, 'amount' => $a];
            $count += $c;
            $amount += $a;
        }

        return [$subColumns, $count, $amount];
    }

    private function partLabel($course, int $index): string
    {
        if (!$course || $course->isShortTerm()) {
            return 'Year';
        }

        return ($course->structure_type === 'trimester' ? 'Tri ' : 'Sem ') . ($index + 1);
    }

    private function yearLabel(int $n): string
    {
        $suffix = match (true) {
            $n === 1 => 'st',
            $n === 2 => 'nd',
            $n === 3 => 'rd',
            default  => 'th',
        };

        return "{$n}{$suffix} Year";
    }

    private function buildExpenseRow(ReportParticular $particular, int $instituteId, ?int $sessionId, string $date): array
    {
        if ($particular->source_type === 'expense') {
            // expense_category_l1_id may be null here — an intentional "Uncategorized"
            // catch-all row (see ReportParticularController); Eloquent's where(col, null)
            // compiles to a NULL-safe whereNull automatically.
            $original = Expense::where('institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where('expense_category_l1_id', $particular->expense_category_l1_id)
                ->where('wallet_debited', true)
                ->whereDate('expense_date', $date);

            $reversedToday = Expense::where('institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where('expense_category_l1_id', $particular->expense_category_l1_id)
                ->whereNotNull('reversed_at')
                ->whereDate('reversed_at', $date);

            $net = $this->netOfReversals($original, $reversedToday, 'amount');

            return $this->row($particular, $net['count'], $net['amount']);
        }

        if ($particular->source_type === 'salary') {
            $count = 0;
            $amount = 0.0;

            if (in_array($particular->salary_scope, ['both', 'teaching'], true)) {
                $original = SalaryRecord::where('institute_id', $instituteId)
                    ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                    ->where('wallet_debited', true)
                    ->whereDate('payment_date', $date);

                $reversedToday = SalaryRecord::where('institute_id', $instituteId)
                    ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                    ->whereNotNull('reversed_at')
                    ->whereDate('reversed_at', $date);

                $net = $this->netOfReversals($original, $reversedToday, 'net_payable');
                $count += $net['count'];
                $amount += $net['amount'];
            }

            if (in_array($particular->salary_scope, ['both', 'non_teaching'], true)) {
                $original = EmployeeSalaryDisbursement::where('institute_id', $instituteId)
                    ->where('wallet_debited', true)
                    ->whereDate('payment_date', $date);

                $reversedToday = EmployeeSalaryDisbursement::where('institute_id', $instituteId)
                    ->whereNotNull('reversed_at')
                    ->whereDate('reversed_at', $date);

                $net = $this->netOfReversals($original, $reversedToday, 'net_salary');
                $count += $net['count'];
                $amount += $net['amount'];
            }

            return $this->row($particular, $count, $amount);
        }

        return $this->row($particular, 0, 0.0);
    }

    /**
     * Cash-basis total for a date: original wallet-debited amount posted that date,
     * minus any reversal whose reversed_at (not the original expense/payment date)
     * falls on that date — so a later reversal reduces the day it actually happened
     * on, instead of silently erasing history on the original day.
     */
    private function netOfReversals($original, $reversedToday, string $amountColumn): array
    {
        return [
            'count'  => (clone $original)->count(),
            'amount' => (float) (clone $original)->sum($amountColumn) - (float) (clone $reversedToday)->sum($amountColumn),
        ];
    }

    private function row(ReportParticular $particular, int $count, float $amount): array
    {
        return [
            'particular' => $particular,
            'name'       => $particular->name,
            'count'      => $count,
            'amount'     => $amount,
            'year_data'  => null,
        ];
    }

    /**
     * First and last invoice_no generated that date — mirrors the paper register's
     * "Rec. Range" (the span of receipt-book numbers used that day). Includes
     * cancelled invoices too, since a cancelled entry still consumes a receipt
     * number in sequence.
     */
    private function receiptNumberRange(int $instituteId, ?int $sessionId, string $date): array
    {
        $base = FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->whereDate('payment_date', $date);

        return [
            'from' => (clone $base)->orderBy('created_at')->orderBy('id')->value('invoice_no'),
            'to'   => (clone $base)->orderByDesc('created_at')->orderByDesc('id')->value('invoice_no'),
        ];
    }

    /**
     * Reference-only cross-tab (By Hand vs Computerized receipt) — same fee income already
     * itemized in the course/category rows above, sliced a different way for reconciliation.
     * Not added into the Grand Total to avoid double-counting.
     */
    private function receiptModeSplit(int $instituteId, ?int $sessionId, string $date): array
    {
        $rows = FeeInvoice::where('institute_id', $instituteId)
            ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->where('is_cancelled', false)
            ->whereDate('payment_date', $date)
            ->selectRaw('bank_account_id, payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('bank_account_id', 'payment_mode')
            ->get();

        $cashRows = $rows->filter(fn ($r) => strtolower($r->payment_mode ?? '') === 'cash');
        $nonCashRows = $rows->filter(fn ($r) => strtolower($r->payment_mode ?? '') !== 'cash');

        $bankIds = $nonCashRows->pluck('bank_account_id')->filter()->unique();
        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->whereIn('id', $bankIds)->get()->keyBy('id');

        $bankWise = $nonCashRows
            ->groupBy(fn ($r) => $r->bank_account_id ?: 0)
            ->map(function ($group, $bankId) use ($bankAccounts) {
                $bank = $bankId ? ($bankAccounts[$bankId] ?? null) : null;

                return [
                    'bank_name'    => $bank?->bank_name ?? $bank?->display_label ?? $bank?->account_name ?? ($bankId ? ('Bank #' . $bankId) : 'Unassigned / Other Online'),
                    'count'        => (int) $group->sum('cnt'),
                    'amount'       => (float) $group->sum('total'),
                    'modes'        => $group->map(fn ($r) => [
                        'mode'   => strtoupper($r->payment_mode ?? 'OTHER'),
                        'count'  => (int) $r->cnt,
                        'amount' => (float) $r->total,
                    ])->values(),
                    // Keyed by mode name for O(1) lookup when rendering a fixed
                    // set of mode columns (PDF pivot table).
                    'modes_by_key' => $group->mapWithKeys(fn ($r) => [
                        strtoupper($r->payment_mode ?? 'OTHER') => [
                            'count'  => (int) $r->cnt,
                            'amount' => (float) $r->total,
                        ],
                    ]),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'by_hand' => [
                'count'  => (int) $cashRows->sum('cnt'),
                'amount' => (float) $cashRows->sum('total'),
            ],
            'computerized' => [
                'count'  => (int) $nonCashRows->sum('cnt'),
                'amount' => (float) $nonCashRows->sum('total'),
            ],
            'bank_wise' => $bankWise,
        ];
    }

    // ── Export ───────────────────────────────────────────────────────────

    private function export(string $format, array $data)
    {
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('institute.finance.wallet.daily-register-pdf', $data)->setPaper('a4', 'landscape');

            return $pdf->stream('daily-register-' . $data['date'] . '.pdf');
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($data) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [$data['instituteName'] . ' — Daily Register', $data['date']]);
                fputcsv($out, []);
                fputcsv($out, ['INCOME']);
                fputcsv($out, ['Particular', 'Count', 'Amount']);
                foreach ($data['incomeRows'] as $row) {
                    fputcsv($out, [$row['name'], $row['count'], number_format($row['amount'], 2, '.', '')]);
                }
                fputcsv($out, ['Total Income', '', number_format($data['totalIncome'], 2, '.', '')]);
                fputcsv($out, []);
                fputcsv($out, ['EXPENSE']);
                fputcsv($out, ['Particular', 'Count', 'Amount']);
                foreach ($data['expenseRows'] as $row) {
                    fputcsv($out, [$row['name'], $row['count'], number_format($row['amount'], 2, '.', '')]);
                }
                fputcsv($out, ['Total Expense', '', number_format($data['totalExpense'], 2, '.', '')]);
                fputcsv($out, []);
                fputcsv($out, ['Last Balance', '', number_format($data['lastBalance'], 2, '.', '')]);
                fputcsv($out, ['Grand Total', '', number_format($data['grandTotal'], 2, '.', '')]);
                fclose($out);
            }, 'daily-register-' . $data['date'] . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        abort(404);
    }
}
