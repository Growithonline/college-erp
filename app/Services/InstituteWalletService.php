<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Expense;
use App\Models\InstituteManualIncome;
use App\Models\InstituteTransaction;
use App\Models\InstituteWallet;
use App\Models\SalaryRecord;
use Illuminate\Support\Facades\DB;

class InstituteWalletService
{
    public static function creditManualIncome(InstituteManualIncome $income): void
    {
        $instituteId = $income->institute_id;
        $sessionId   = $income->academic_session_id;

        DB::transaction(function () use ($income, $instituteId, $sessionId) {
            $wallet = InstituteWallet::firstOrCreate(
                ['institute_id' => $instituteId, 'academic_session_id' => $sessionId],
                ['main_b' => 0.00]
            );
            // Lock the row to prevent concurrent balance updates
            $wallet = InstituteWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $opBal = (float) $wallet->main_b;
            $clBal = round($opBal + (float) $income->amount, 2);

            InstituteTransaction::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $sessionId,
                'des'                 => 'Manual income: ' . ($income->category?->name ?? 'Uncategorized')
                                        . ($income->description ? ' - ' . $income->description : ''),
                'credit'              => $income->amount,
                'debit'               => 0.00,
                'type'                => InstituteTransaction::CREDIT,
                'date'                => $income->date->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'source_type'         => 'manual_income',
                'source_id'           => $income->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
        });
    }

    public static function getWalletSummary(int $instituteId, int $sessionId): array
    {
        $wallet = InstituteWallet::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->first();

        $balance = (float) ($wallet?->main_b ?? 0);

        $txBase = InstituteTransaction::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId);

        $totalCredit = (float) (clone $txBase)->where('type', InstituteTransaction::CREDIT)->sum('credit');
        $totalDebit  = (float) (clone $txBase)->where('type', InstituteTransaction::DEBIT)->sum('debit');

        $todayCredit = (float) (clone $txBase)
            ->where('type', InstituteTransaction::CREDIT)
            ->whereDate('date', now()->toDateString())
            ->sum('credit');

        $todayDebit = (float) (clone $txBase)
            ->where('type', InstituteTransaction::DEBIT)
            ->whereDate('date', now()->toDateString())
            ->sum('debit');

        $bySource = (clone $txBase)
            ->where('type', InstituteTransaction::CREDIT)
            ->selectRaw('source_type, SUM(credit) as total')
            ->groupBy('source_type')
            ->pluck('total', 'source_type')
            ->toArray();

        return [
            'balance'       => $balance,
            'total_income'  => $totalCredit,
            'total_expense' => $totalDebit,
            'today_income'  => $todayCredit,
            'today_expense' => $todayDebit,
            'by_source'     => $bySource,
        ];
    }

    public static function getLedger(int $instituteId, int $sessionId, ?string $from = null, ?string $to = null): \Illuminate\Database\Eloquent\Collection
    {
        return InstituteTransaction::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    public static function getBalance(int $instituteId, int $sessionId): float
    {
        $wallet = InstituteWallet::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->first();

        return (float) ($wallet?->main_b ?? 0);
    }

    public static function debitExpense(Expense $expense): void
    {
        $instituteId = $expense->institute_id;
        $sessionId   = $expense->academic_session_id;

        if (!$sessionId) {
            $sessionId = AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');
        }

        if (!$sessionId) {
            return;
        }

        $finalSessionId = $sessionId;

        DB::transaction(function () use ($expense, $instituteId, $finalSessionId) {
            $wallet = InstituteWallet::firstOrCreate(
                ['institute_id' => $instituteId, 'academic_session_id' => $finalSessionId],
                ['main_b' => 0.00]
            );
            $wallet = InstituteWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $opBal       = (float) $wallet->main_b;
            $clBal       = round($opBal - (float) $expense->amount, 2);
            $description = 'Expense: ' . ($expense->categoryL2?->name
                ?? $expense->vendor?->name
                ?? $expense->vendor_name
                ?? $expense->description);

            InstituteTransaction::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $finalSessionId,
                'des'                 => $description,
                'credit'              => 0.00,
                'debit'               => $expense->amount,
                'type'                => InstituteTransaction::DEBIT,
                'date'                => $expense->expense_date->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'source_type'         => 'expense',
                'source_id'           => $expense->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
            $expense->update(['wallet_debited' => true]);
        });
    }

    public static function creditExpenseReversal(Expense $expense): void
    {
        $instituteId = $expense->institute_id;
        $sessionId   = $expense->academic_session_id;

        if (!$sessionId) {
            $sessionId = AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');
        }

        if (!$sessionId) {
            return;
        }

        $finalSessionId = $sessionId;

        DB::transaction(function () use ($expense, $instituteId, $finalSessionId) {
            // Re-read expense with lock to get fresh wallet_debited value
            $freshExpense = Expense::where('id', $expense->id)->lockForUpdate()->first();

            if (!$freshExpense || !$freshExpense->wallet_debited) {
                return;
            }

            $wallet = InstituteWallet::where('institute_id', $instituteId)
                ->where('academic_session_id', $finalSessionId)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                return;
            }

            $opBal       = (float) $wallet->main_b;
            $clBal       = round($opBal + (float) $expense->amount, 2);
            $description = 'Expense reversed: ' . ($expense->categoryL2?->name ?? $expense->description);

            InstituteTransaction::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $finalSessionId,
                'des'                 => $description,
                'credit'              => $expense->amount,
                'debit'               => 0.00,
                'type'                => InstituteTransaction::CREDIT,
                'date'                => now()->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'source_type'         => 'expense_reversal',
                'source_id'           => $expense->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
            $freshExpense->update(['wallet_debited' => false]);
        });
    }

    public static function debitSalary(SalaryRecord $salary): void
    {
        $instituteId = $salary->institute_id;
        $sessionId   = $salary->academic_session_id;

        if (!$sessionId) {
            $sessionId = AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');
        }

        if (!$sessionId) {
            return;
        }

        $finalSessionId = $sessionId;

        DB::transaction(function () use ($salary, $instituteId, $finalSessionId) {
            // Re-read salary with lock — prevents double-debit on concurrent requests
            $freshSalary = SalaryRecord::where('id', $salary->id)
                ->lockForUpdate()
                ->first();

            if (!$freshSalary || $freshSalary->wallet_debited) {
                return;
            }

            $wallet = InstituteWallet::firstOrCreate(
                ['institute_id' => $instituteId, 'academic_session_id' => $finalSessionId],
                ['main_b' => 0.00]
            );
            $wallet = InstituteWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $opBal     = (float) $wallet->main_b;
            $amount    = (float) $freshSalary->net_payable;
            $clBal     = round($opBal - $amount, 2);
            $staffName = $freshSalary->staffMember?->name ?? $salary->staffMember?->name ?? 'Staff';
            $month     = str_pad($freshSalary->salary_month, 2, '0', STR_PAD_LEFT) . '/' . $freshSalary->salary_year;

            InstituteTransaction::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $finalSessionId,
                'des'                 => "Salary paid: {$staffName} - {$month}",
                'credit'              => 0.00,
                'debit'               => $amount,
                'type'                => InstituteTransaction::DEBIT,
                'date'                => $freshSalary->payment_date
                                            ? $freshSalary->payment_date->toDateString()
                                            : now()->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'source_type'         => 'salary',
                'source_id'           => $freshSalary->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
            $freshSalary->update(['wallet_debited' => true]);
        });
    }

    public static function creditSalaryReversal(SalaryRecord $salary): void
    {
        $instituteId = $salary->institute_id;
        $sessionId   = $salary->academic_session_id;

        if (!$sessionId) {
            $sessionId = AcademicSession::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->value('id');
        }

        if (!$sessionId) {
            return;
        }

        $finalSessionId = $sessionId;

        DB::transaction(function () use ($salary, $instituteId, $finalSessionId) {
            // Re-read salary with lock — ensures we have the latest wallet_debited
            $freshSalary = SalaryRecord::where('id', $salary->id)
                ->lockForUpdate()
                ->first();

            if (!$freshSalary || !$freshSalary->wallet_debited) {
                return;
            }

            $wallet = InstituteWallet::where('institute_id', $instituteId)
                ->where('academic_session_id', $finalSessionId)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                return;
            }

            $opBal     = (float) $wallet->main_b;
            $amount    = (float) $freshSalary->net_payable;
            $clBal     = round($opBal + $amount, 2);
            $staffName = $freshSalary->staffMember?->name ?? $salary->staffMember?->name ?? 'Staff';
            $month     = str_pad($freshSalary->salary_month, 2, '0', STR_PAD_LEFT) . '/' . $freshSalary->salary_year;

            InstituteTransaction::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $finalSessionId,
                'des'                 => "Salary reversed: {$staffName} - {$month}",
                'credit'              => $amount,
                'debit'               => 0.00,
                'type'                => InstituteTransaction::CREDIT,
                'date'                => now()->toDateString(),
                'op_bal'              => $opBal,
                'cl_bal'              => $clBal,
                'source_type'         => 'salary_reversal',
                'source_id'           => $freshSalary->id,
                'by_user_id'          => self::resolveActorId(),
            ]);

            $wallet->update(['main_b' => $clBal]);
            $freshSalary->update(['wallet_debited' => false]);
        });
    }

    private static function resolveActorId(): ?int
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            $id = auth()->guard($guard)->id();
            if ($id !== null) {
                return (int) $id;
            }
        }
        return null;
    }
}
