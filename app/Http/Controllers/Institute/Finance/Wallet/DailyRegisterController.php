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
use App\Models\InstituteManualIncome;
use App\Models\InstituteTransaction;
use App\Models\ReportParticular;
use App\Models\SalaryRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DailyRegisterController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
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

        $incomeRows = collect();
        $totalIncome = 0.0;
        foreach ($particulars->where('section', 'income') as $particular) {
            $row = $this->buildIncomeRow($particular, $instituteId, $sessionId, $date);
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

        $receiptModeSplit = $this->receiptModeSplit($instituteId, $sessionId, $date);

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
            'header'           => $header,
            'date'             => $date,
            'instituteName'    => Institute::find($instituteId)?->name ?? '',
        ];

        if ($request->filled('export')) {
            return $this->export($request->input('export'), $exportData);
        }

        return view('institute.finance.wallet.daily-register', $exportData + compact(
            'sessions', 'sessionId'
        ));
    }

    public function saveHeader(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'report_date'       => 'required|date',
            'book_no'           => 'nullable|string|max:50',
            'rec_range_from'    => 'nullable|string|max:50',
            'rec_range_to'      => 'nullable|string|max:50',
            'online_range_from' => 'nullable|string|max:50',
            'online_range_to'   => 'nullable|string|max:50',
            'sr_no'             => 'nullable|string|max:50',
            'activities'        => 'nullable|string|max:2000',
        ]);

        DailyReportHeader::updateOrCreate(
            ['institute_id' => $instituteId, 'report_date' => $data['report_date']],
            $data + ['created_by' => auth()->id()]
        );

        return redirect()->route('finance.wallet.daily-register.index', ['date' => $data['report_date']])
            ->with('success', 'Header saved!');
    }

    // ── Row builders ─────────────────────────────────────────────────────

    private function buildIncomeRow(ReportParticular $particular, int $instituteId, ?int $sessionId, string $date): array
    {
        if ($particular->source_type === 'fee_invoice' && $particular->course_id) {
            return $this->buildCourseRow($particular, $instituteId, $sessionId, $date);
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

    private function buildCourseRow(ReportParticular $particular, int $instituteId, ?int $sessionId, string $date): array
    {
        $course = $particular->course;

        $parts = CoursePart::where('course_id', $particular->course_id)
            ->where('year_number', $particular->year_number)
            ->orderBy('part_number')
            ->get();

        $subColumns = [];
        $totalCount = 0;
        $totalAmount = 0.0;

        // Course::semesterOptionsForYear() always stores semester=0 ("Annual") for
        // yearly/modular courses — it can't distinguish year in that case, so year
        // must be resolved via the student's own course_part_id instead. For
        // semester/trimester courses, FeeInvoice.semester stores the absolute
        // part_number directly and is set fresh at collection time, so it's the
        // more reliable signal (matches what the invoice list itself displays).
        $isYearlyType = !$course || $course->effectiveSemestersPerYear() <= 1;

        foreach ($parts as $index => $part) {
            $base = FeeInvoice::where('institute_id', $instituteId)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->where('is_cancelled', false)
                ->whereDate('payment_date', $date);

            if ($isYearlyType) {
                $base->whereHas('student', fn ($q) => $q->where('course_part_id', $part->id));
            } else {
                $base->where('semester', $part->part_number)
                     ->whereHas('student.stream', fn ($q) => $q->where('course_id', $particular->course_id));
            }

            $count = (clone $base)->count();
            $amount = (float) (clone $base)->sum('paid_amount');

            $subColumns[] = ['label' => $this->partLabel($course, $index), 'count' => $count, 'amount' => $amount];
            $totalCount += $count;
            $totalAmount += $amount;
        }

        return [
            'particular'  => $particular,
            'name'        => $particular->name,
            'count'       => $totalCount,
            'amount'      => $totalAmount,
            'sub_columns' => $subColumns,
        ];
    }

    private function partLabel($course, int $index): string
    {
        if (!$course || $course->isShortTerm()) {
            return 'Year';
        }

        return ($course->structure_type === 'trimester' ? 'Tri ' : 'Sem ') . ($index + 1);
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
            'particular'  => $particular,
            'name'        => $particular->name,
            'count'       => $count,
            'amount'      => $amount,
            'sub_columns' => null,
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
            ->selectRaw('payment_mode, COUNT(*) as cnt, SUM(paid_amount) as total')
            ->groupBy('payment_mode')
            ->get();

        $byHand = $rows->first(fn ($r) => strtolower($r->payment_mode ?? '') === 'cash');
        $computerized = $rows->filter(fn ($r) => strtolower($r->payment_mode ?? '') !== 'cash');

        return [
            'by_hand'      => ['count' => (int) ($byHand->cnt ?? 0), 'amount' => (float) ($byHand->total ?? 0)],
            'computerized' => ['count' => (int) $computerized->sum('cnt'), 'amount' => (float) $computerized->sum('total')],
        ];
    }

    // ── Export ───────────────────────────────────────────────────────────

    private function export(string $format, array $data)
    {
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('institute.finance.wallet.daily-register-pdf', $data)->setPaper('a4', 'landscape');

            return $pdf->download('daily-register-' . $data['date'] . '.pdf');
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
