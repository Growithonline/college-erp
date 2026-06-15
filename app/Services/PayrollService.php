<?php

namespace App\Services;

use App\Models\SalaryRecord;
use App\Models\StaffMember;
use App\Models\AttendanceLockRecord;
use App\Services\InstituteWalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\JournalService;
use App\Models\StaffLoan;

class PayrollService
{
    /**
     * Generate salary draft from attendance for a month
     */
    public static function generateSalaryDraft($instituteId, $year, $month, $category = null)
    {
        return DB::transaction(function () use ($instituteId, $year, $month, $category) {
            $activeSessionId = \App\Models\AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');

            $staffQuery = StaffMember::where('institute_id', $instituteId)
                ->where('status', true);

            if ($category) {
                $staffQuery->where('staff_category', $category);
            }

            $staffMembers = $staffQuery->get();
            $salaryRecords = [];
            $warnings = [];

            // Bulk-fetch all attendance summaries in a single query to avoid N+1
            $staffIds = $staffMembers->pluck('id')->toArray();
            $attendanceSummaries = AttendanceService::getBulkAttendanceSummaries($instituteId, $year, $month, $staffIds);

            // Pre-compute effective days to check (same for all staff in the month)
            $totalDaysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $monthEnd = Carbon::createFromDate($year, $month, $totalDaysInMonth)->endOfDay();
            $today = Carbon::today();
            $baseEffectiveDays = $monthEnd->isFuture()
                ? $today->day   // current month: only days elapsed so far
                : $totalDaysInMonth; // past month: full month

            foreach ($staffMembers as $staff) {
                $attendance = $attendanceSummaries[$staff->id];

                // Completeness check — adjust effective days for joining date
                $effectiveDays = $baseEffectiveDays;
                if ($staff->joining_date) {
                    $joining = Carbon::parse($staff->joining_date);
                    if ($joining->year === $year && $joining->month === $month) {
                        // Staff joined mid-month — only check from joining day
                        $effectiveDays = max(0, $baseEffectiveDays - $joining->day + 1);
                    } elseif ($joining->isAfter($monthEnd)) {
                        // Staff hasn't joined yet this month — skip
                        $effectiveDays = 0;
                    }
                }

                $markedDays = $attendance['marked_days'];
                $unmarkedDays = max(0, $effectiveDays - $markedDays);

                if ($unmarkedDays > 0) {
                    $warnings[] = [
                        'staff_id'      => $staff->id,
                        'staff_name'    => $staff->name,
                        'marked_days'   => $markedDays,
                        'unmarked_days' => $unmarkedDays,
                        'total_days'    => $effectiveDays,
                        'message'       => "{$staff->name}: {$unmarkedDays} din ka attendance mark nahi hai ({$markedDays}/{$effectiveDays} din marked)",
                    ];
                }

                $salaryData = self::calculateSalary($staff, $attendance, $year, $month);

                if (!$salaryData) {
                    continue;
                }

                $salaryData['academic_session_id'] = $activeSessionId;

                if (!$salaryData['expense_account_id']) {
                    $warnings[] = [
                        'staff_id'   => $staff->id,
                        'staff_name' => $staff->name,
                        'type'       => 'missing_expense_account',
                        'message'    => "{$staff->name}: Salary expense account configure nahi hai. Staff profile mein expense head set karein.",
                    ];
                }

                $record = SalaryRecord::firstOrNew([
                    'staff_member_id' => $staff->id,
                    'salary_month' => $month,
                    'salary_year' => $year,
                ]);

                // Skip only PAID records — they cannot be regenerated
                if ($record->status === SalaryRecord::STATUS_PAID) {
                    $salaryRecords[] = $record;
                    continue;
                }

                $salaryData['status'] = ($record->exists && $record->status === SalaryRecord::STATUS_APPROVED)
                    ? SalaryRecord::STATUS_APPROVED
                    : SalaryRecord::STATUS_DRAFT;

                // REVERSED record: allow re-generation — clear reversal metadata (journal history is preserved separately)
                if ($record->exists && $record->status === SalaryRecord::STATUS_REVERSED) {
                    $salaryData = array_merge($salaryData, [
                        'reversed_at'               => null,
                        'reversed_by'               => null,
                        'reversal_reason'           => null,
                        'reversal_journal_entry_id' => null,
                    ]);
                }

                $record->fill($salaryData);
                if (!$record->exists) {
                    $record->created_by = auth()->id();
                }
                $record->save();

                $salaryRecords[] = $record;
            }

            return ['records' => $salaryRecords, 'warnings' => $warnings];
        });
    }

    /**
     * Calculate salary for a staff member (with PF, ESI, HRA, DA, TA, loans)
     */
    public static function calculateSalary(StaffMember $staff, array $attendance, int $year, int $month): ?array
    {
        if (!$staff->monthly_salary && !$staff->daily_wage) {
            return null;
        }

        $daysInMonth    = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $basicSalary    = 0;
        $absenceDeduction = 0;

        // ── 1. Basic salary ───────────────────────────────────────────────
        if ($staff->payroll_type === 'monthly') {
            $basicSalary = (float) ($staff->monthly_salary ?? 0);

            if ($basicSalary > 0) {
                $dailyRate      = $basicSalary / $daysInMonth;
                $payableDays    = $attendance['payable_days'] ?? 0;
                $weekOffDays    = $attendance['week_off']     ?? 0;
                $holidayDays    = $attendance['holiday']      ?? 0;
                $nonDeductible  = $payableDays + $weekOffDays + $holidayDays;
                $absenceDeduction = max(0, $daysInMonth - $nonDeductible) * $dailyRate;
            }
        } else {
            $workedDays  = $attendance['worked_days'] ?? 0;
            $basicSalary = $workedDays * (float) ($staff->daily_wage ?? 0);
        }

        // ── 2. Allowances ─────────────────────────────────────────────────
        $overtimeHours    = $attendance['total_overtime'] ?? 0;
        $overtimeRate     = $staff->payroll_type === 'monthly'
            ? ($staff->monthly_salary ?? 0) / (26 * 8)
            : ($staff->daily_wage    ?? 0) / 8;
        $overtimeAmount   = round($overtimeHours * $overtimeRate, 2);

        $hra    = round($basicSalary * ((int) ($staff->hra_percent ?? 0) / 100), 2);
        $da     = round($basicSalary * ((int) ($staff->da_percent  ?? 0) / 100), 2);
        $ta     = round((float) ($staff->ta_amount      ?? 0), 2);
        $medical = round((float) ($staff->medical_amount ?? 0), 2);

        $totalAllowances = $overtimeAmount + $hra + $da + $ta + $medical;

        // ── 3. Gross salary ───────────────────────────────────────────────
        $gross = $basicSalary + $totalAllowances;

        // ── 4. PF (Provident Fund) ────────────────────────────────────────
        // PF ceiling: ₹15,000 of basic per EPFO rules
        $pfEmployee = 0.0;
        $pfEmployer = 0.0;
        if ($staff->pf_applicable) {
            $pfBase     = min($basicSalary, 15000);
            $pfEmployee = round($pfBase * 0.12, 2);
            $pfEmployer = round($pfBase * 0.12, 2);
        }

        // ── 5. ESI (Employee State Insurance) ────────────────────────────
        // Applicable if gross ≤ ₹21,000/month
        $esiEmployee = 0.0;
        $esiEmployer = 0.0;
        if ($gross > 0 && $gross <= 21000) {
            $esiEmployee = round($gross * 0.0075, 2);
            $esiEmployer = round($gross * 0.0325, 2);
        }

        // ── 6. TDS & Professional Tax ─────────────────────────────────────
        $tds              = round((float) ($staff->tds_monthly             ?? 0), 2);
        $professionalTax  = round((float) ($staff->professional_tax_monthly ?? 0), 2);

        // ── 7. Loan deductions ────────────────────────────────────────────
        $loanDeduction = 0.0;
        $activeLoans   = StaffLoan::getActiveLoansForStaff($staff->id);
        foreach ($activeLoans as $loan) {
            $emi = min((float) $loan->monthly_deduction, (float) $loan->outstanding_amount);
            $loanDeduction = round($loanDeduction + $emi, 2);
        }

        // ── 8. Totals ─────────────────────────────────────────────────────
        $totalDeductions = round(
            $absenceDeduction + $pfEmployee + $esiEmployee + $tds + $professionalTax + $loanDeduction,
            2
        );

        $netPayable = round($basicSalary + $totalAllowances - $totalDeductions, 2);

        return [
            'institute_id'       => $staff->institute_id,
            'staff_member_id'    => $staff->id,
            'salary_month'       => $month,
            'salary_year'        => $year,
            'basic_salary'       => round($basicSalary, 2),
            // Allowance total (stored in existing 'allowances' column)
            'allowances'         => round($totalAllowances, 2),
            // Allowance breakdown
            'hra'                => $hra,
            'da'                 => $da,
            'ta'                 => $ta,
            'medical'            => $medical,
            'overtime_amount'    => $overtimeAmount,
            // Deductions total (stored in existing 'deductions' column)
            'deductions'         => $totalDeductions,
            // Deduction breakdown
            'absence_deduction'  => round($absenceDeduction, 2),
            'pf_employee'        => $pfEmployee,
            'pf_employer'        => $pfEmployer,
            'esi_employee'       => $esiEmployee,
            'esi_employer'       => $esiEmployer,
            'tds'                => $tds,
            'professional_tax'   => $professionalTax,
            'loan_deduction'     => $loanDeduction,
            'net_payable'        => $netPayable,
            'paid_amount'        => 0,
            'expense_account_id' => self::resolveExpenseAccountId($staff),
            'status'             => SalaryRecord::STATUS_DRAFT,
        ];
    }

    /**
     * Get salary draft for review
     */
    public static function getSalaryDraft($instituteId, $year, $month, $category = null)
    {
        $query = SalaryRecord::where('institute_id', $instituteId)
            ->where('salary_year', $year)
            ->where('salary_month', $month)
            ->where('status', SalaryRecord::STATUS_DRAFT);

        if ($category) {
            $query->whereHas('staffMember', function ($q) use ($category) {
                $q->where('staff_category', $category);
            });
        }

        return $query->with('staffMember', 'expenseAccount')->get();
    }

    /**
     * Approve and finalize salary for a staff member
     */
    public static function approveSalary(SalaryRecord $salaryRecord): SalaryRecord
    {
        if (in_array($salaryRecord->status, [SalaryRecord::STATUS_PAID, SalaryRecord::STATUS_REVERSED], true)) {
            throw new \RuntimeException('Paid or reversed salary record cannot be approved.');
        }

        $salaryRecord->update(['status' => SalaryRecord::STATUS_APPROVED]);
        return $salaryRecord;
    }

    /**
     * Mark salary as paid
     */
    public static function markSalaryPaid(
        SalaryRecord $salaryRecord,
        $paymentDate = null,
        $paymentMode = null,
        $remarks = null,
        $paymentAccountId = null,
        $bankAccountId = null
    ): SalaryRecord
    {
        if ($salaryRecord->status === SalaryRecord::STATUS_REVERSED) {
            throw new \RuntimeException('Reversed salary record cannot be paid.');
        }

        if ($salaryRecord->journal_entry_id) {
            throw new \RuntimeException('Salary already posted.');
        }

        if (!$salaryRecord->expense_account_id) {
            throw new \RuntimeException('Salary expense head is missing for this salary record.');
        }

        if (!$paymentAccountId) {
            throw new \RuntimeException('Payment account mapping is missing. Configure cash or bank GL account first.');
        }

        // Wrap status update + GL posting + wallet debit atomically
        DB::transaction(function () use ($salaryRecord, $paymentDate, $paymentMode, $remarks, $paymentAccountId, $bankAccountId) {
            $salaryRecord->update([
                'status'             => SalaryRecord::STATUS_PAID,
                'paid_amount'        => (float) $salaryRecord->net_payable,
                'payment_date'       => $paymentDate ?? now(),
                'payment_mode'       => $paymentMode ?? 'cash',
                'remarks'            => $remarks,
                'payment_account_id' => $paymentAccountId,
                'bank_account_id'    => $bankAccountId,
            ]);

            $freshRecord  = $salaryRecord->fresh(['staffMember', 'expenseAccount', 'paymentAccount', 'bankAccount']);
            $journalEntry = JournalService::safePostSalaryPayment($freshRecord);
            if ($journalEntry) {
                $salaryRecord->update(['journal_entry_id' => $journalEntry->id]);
            }

            // debitSalary uses its own nested transaction + lockForUpdate
            InstituteWalletService::debitSalary($salaryRecord->fresh(['staffMember']));
        });

        // EMI deduction after payment is committed (not part of atomic block)
        if ($salaryRecord->loan_deduction > 0) {
            StaffLoan::getActiveLoansForStaff($salaryRecord->staff_member_id)
                ->each(fn($loan) => $loan->deductEmi());
        }

        return $salaryRecord->fresh();
    }

    /**
     * Reverse salary payment
     */
    public static function reverseSalary(SalaryRecord $salaryRecord, $reason = null): SalaryRecord
    {
        if ($salaryRecord->status !== SalaryRecord::STATUS_PAID) {
            throw new \RuntimeException('Only paid salary can be reversed.');
        }

        // Wrap journal reversal + status update + wallet credit atomically
        DB::transaction(function () use ($salaryRecord, $reason) {
            $reversalEntry = JournalService::safeReverseSalaryPayment($salaryRecord, $reason);

            // Restore loan outstanding amounts
            $loanDeduction = (float) ($salaryRecord->loan_deduction ?? 0);
            if ($loanDeduction > 0) {
                StaffLoan::where('staff_member_id', $salaryRecord->staff_member_id)
                    ->where('status', StaffLoan::STATUS_ACTIVE)
                    ->get()
                    ->each(function ($loan) use ($loanDeduction) {
                        $restored = min($loanDeduction, (float) $loan->principal_amount - (float) $loan->outstanding_amount);
                        if ($restored > 0) {
                            $loan->outstanding_amount = round((float) $loan->outstanding_amount + $restored, 2);
                            $loan->status = StaffLoan::STATUS_ACTIVE;
                            $loan->save();
                        }
                    });
            }

            $salaryRecord->update([
                'status'                    => SalaryRecord::STATUS_REVERSED,
                'reversal_journal_entry_id' => $reversalEntry?->id,
                'reversed_at'               => now(),
                'reversed_by'               => auth()->id(),
                'reversal_reason'           => $reason,
            ]);

            // creditSalaryReversal uses its own nested transaction + lockForUpdate
            InstituteWalletService::creditSalaryReversal($salaryRecord->fresh(['staffMember']));
        });

        return $salaryRecord;
    }

    /**
     * Get payroll summary for reporting
     */
    public static function getPayrollSummary($instituteId, $year, $month, $category = null, array $statuses = [])
    {
        $query = SalaryRecord::where('institute_id', $instituteId)
            ->where('salary_year', $year)
            ->where('salary_month', $month);

        if ($category) {
            $query->whereHas('staffMember', function ($q) use ($category) {
                $q->where('staff_category', $category);
            });
        }

        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        $records = $query->with('staffMember')->get();

        return [
            'period' => sprintf('%s-%02d', $year, $month),
            'category' => $category,
            'total_records' => $records->count(),
            'draft_count' => $records->where('status', SalaryRecord::STATUS_DRAFT)->count(),
            'approved_count' => $records->where('status', SalaryRecord::STATUS_APPROVED)->count(),
            'pending_count' => $records->where('status', SalaryRecord::STATUS_PENDING)->count(),
            'paid_count' => $records->where('status', SalaryRecord::STATUS_PAID)->count(),
            'reversed_count' => $records->where('status', SalaryRecord::STATUS_REVERSED)->count(),
            'total_basic' => round($records->sum('basic_salary'), 2),
            'total_allowances' => round($records->sum('allowances'), 2),
            'total_deductions' => round($records->sum('deductions'), 2),
            'total_net_payable' => round($records->sum('net_payable'), 2),
            'total_paid' => round($records->where('status', SalaryRecord::STATUS_PAID)->sum('paid_amount'), 2),
            'records' => $records,
        ];
    }

    private static function resolveExpenseAccountId(StaffMember $staff): ?int
    {
        if ($staff->salary_expense_head_id) {
            return (int) $staff->salary_expense_head_id;
        }

        $defaultCode = $staff->staff_category === 'Teaching' ? '3001' : '3002';

        return \App\Models\Account::where('institute_id', $staff->institute_id)
            ->where('code', $defaultCode)
            ->value('id');
    }
}
