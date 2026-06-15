<?php

namespace App\Http\Controllers\Institute\Payroll;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\Institute;
use App\Models\InstituteBankAccount;
use App\Models\SalaryRecord;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function ensureSalaryRecordAccess(SalaryRecord $salaryRecord): void
    {
        abort_if($salaryRecord->institute_id !== $this->instituteId(), 403);
    }

    /**
     * Generate salary draft
     */
    public function generateDraft(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'category' => 'nullable|in:Teaching,Office,Non-Teaching,Guest',
        ]);

        try {
            $result = PayrollService::generateSalaryDraft(
                $instituteId,
                $validated['year'],
                $validated['month'],
                $validated['category'] ?? null
            );

            $count = count($result['records']);
            $warnings = $result['warnings'];

            return response()->json([
                'success'  => true,
                'message'  => "Salary draft generated for {$count} staff members" . (count($warnings) ? ' (attendance incomplete for some staff — see warnings)' : ''),
                'count'    => $count,
                'warnings' => $warnings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * View salary draft for review
     */
    public function draftView(Request $request)
    {
        $instituteId = $this->instituteId();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $category = $request->input('category');

        $actionableStatuses = [
            SalaryRecord::STATUS_DRAFT,
            SalaryRecord::STATUS_APPROVED,
            SalaryRecord::STATUS_PENDING,
        ];

        $summary = PayrollService::getPayrollSummary($instituteId, $year, $month, $category, $actionableStatuses);

        $staffIds = $summary['records']->pluck('staff_member_id')->toArray();
        $attendanceSummaries = [];
        if (!empty($staffIds)) {
            $attendanceSummaries = AttendanceService::getBulkAttendanceSummaries(
                $instituteId, $year, $month, $staffIds
            );
        }

        return view('institute.payroll.payroll.draft', [
            'summary'              => $summary,
            'attendanceSummaries'  => $attendanceSummaries,
            'year'                 => $year,
            'month'                => $month,
            'category'             => $category,
            'categories'           => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
        ]);
    }

    /**
     * Approve salary
     */
    public function approveSalary(Request $request, SalaryRecord $salaryRecord)
    {
        try {
            $this->ensureSalaryRecordAccess($salaryRecord);
            PayrollService::approveSalary($salaryRecord);

            return response()->json([
                'success' => true,
                'message' => 'Salary approved',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark salary paid
     */
    public function markPaid(Request $request, SalaryRecord $salaryRecord)
    {
        $this->ensureSalaryRecordAccess($salaryRecord);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:cash,bank',
            'bank_account_id' => 'nullable|integer|required_if:payment_mode,bank',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $paymentAccountId = null;
            $bankAccountId = null;

            if ($validated['payment_mode'] === 'cash') {
                $settings = FinanceSetting::where('institute_id', $this->instituteId())->first();
                $paymentAccountId = $settings?->cash_account_id;
            } else {
                $bankAccount = InstituteBankAccount::where('institute_id', $this->instituteId())
                    ->where('is_active', true)
                    ->findOrFail((int) $validated['bank_account_id']);
                $bankAccountId = $bankAccount->id;
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

            return response()->json([
                'success' => true,
                'message' => 'Salary marked as paid',
                'journal_entry' => $salaryRecord->fresh()->journal_entry_id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reverse salary payment
     */
    public function reverse(Request $request, SalaryRecord $salaryRecord)
    {
        $this->ensureSalaryRecordAccess($salaryRecord);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            PayrollService::reverseSalary($salaryRecord, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'Salary payment reversed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download payslip PDF for a single salary record
     */
    public function payslip(SalaryRecord $salaryRecord)
    {
        $this->ensureSalaryRecordAccess($salaryRecord);
        $salaryRecord->load('staffMember.role', 'expenseAccount', 'bankAccount');

        $institute = Institute::find($this->instituteId());

        $pdf = Pdf::loadView('institute.payroll.payslip.pdf', [
            'record'    => $salaryRecord,
            'institute' => $institute,
        ])->setPaper('a4', 'portrait');

        $filename = 'payslip-' . $salaryRecord->staffMember->name . '-'
            . $salaryRecord->salary_year . '-'
            . str_pad($salaryRecord->salary_month, 2, '0', STR_PAD_LEFT)
            . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Bulk pay all approved salary records for a month
     */
    public function bulkPay(Request $request)
    {
        $instituteId = $this->instituteId();

        $validated = $request->validate([
            'year'            => 'required|integer|min:2020|max:2100',
            'month'           => 'required|integer|min:1|max:12',
            'category'        => 'nullable|in:Teaching,Office,Non-Teaching,Guest',
            'payment_date'    => 'required|date',
            'payment_mode'    => 'required|in:cash,bank',
            'bank_account_id' => 'nullable|integer|required_if:payment_mode,bank',
        ]);

        $paymentAccountId = null;
        $bankAccountId    = null;

        if ($validated['payment_mode'] === 'cash') {
            $settings         = FinanceSetting::where('institute_id', $instituteId)->first();
            $paymentAccountId = $settings?->cash_account_id;
            if (!$paymentAccountId) {
                return response()->json(['success' => false, 'message' => 'Cash account mapping missing in finance settings.'], 422);
            }
        } else {
            $bankAccount      = InstituteBankAccount::where('institute_id', $instituteId)->where('is_active', true)->findOrFail((int) $validated['bank_account_id']);
            $bankAccountId    = $bankAccount->id;
            $paymentAccountId = $bankAccount->gl_account_id;
            if (!$paymentAccountId) {
                return response()->json(['success' => false, 'message' => 'Selected bank account GL mapping is missing.'], 422);
            }
        }

        $query = SalaryRecord::where('institute_id', $instituteId)
            ->where('salary_year', $validated['year'])
            ->where('salary_month', $validated['month'])
            ->whereIn('status', [SalaryRecord::STATUS_APPROVED, SalaryRecord::STATUS_DRAFT]);

        if (!empty($validated['category'])) {
            $query->whereHas('staffMember', fn($q) => $q->where('staff_category', $validated['category']));
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Koi approved/draft salary record nahi mila is month ke liye.'], 422);
        }

        $paid     = 0;
        $failures = [];

        DB::transaction(function () use ($records, $validated, $paymentAccountId, $bankAccountId, &$paid, &$failures) {
            foreach ($records as $record) {
                try {
                    PayrollService::markSalaryPaid(
                        $record,
                        $validated['payment_date'],
                        $validated['payment_mode'],
                        'Bulk payment',
                        $paymentAccountId,
                        $bankAccountId
                    );
                    $paid++;
                } catch (\Exception $e) {
                    $failures[] = ['staff' => $record->staffMember->name ?? $record->staff_member_id, 'reason' => $e->getMessage()];
                }
            }
        });

        return response()->json([
            'success'  => true,
            'message'  => "{$paid} staff ki salary paid mark ho gayi." . (count($failures) ? ' Kuch failures hue — details dekho.' : ''),
            'paid'     => $paid,
            'failures' => $failures,
        ]);
    }

    /**
     * Payroll summary report
     */
    public function summary(Request $request)
    {
        $instituteId = $this->instituteId();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $category = $request->input('category');

        $summary = PayrollService::getPayrollSummary($instituteId, $year, $month, $category);

        return view('institute.payroll.payroll.summary', [
            'summary' => $summary,
            'year' => $year,
            'month' => $month,
            'category' => $category,
            'categories' => ['Teaching', 'Office', 'Non-Teaching', 'Guest'],
        ]);
    }
}
