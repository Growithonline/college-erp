<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\FeeRefund;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentAdvance;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;

// What happens to cash a student paid beyond their fees when a SESSION promotion closes the session
// (after the promotion review has already netted excess against any pending fee, so whatever is
// left over has nothing to be set against):
//   - the student moves on to another session  -> it is carried there as an ADVANCE and set
//     against the new session's fees (WalletService::applyAdvancePool());
//   - the student leaves (final year / dropped) -> a REFUND request is raised for the admin.
// Wallets: the old session gives the amount up, the new one is credited (refunds stay on the old
// wallet until the refund is paid). Books: the credit balance on Fees Receivable becomes a Student
// Advance liability. Every wallet row carries the promotion log id, so reversing the promotion
// reverses these too — see reverseForLog() for the rest.
class AdvanceService
{
    /** Advance carried INTO $sessionId that has not been reversed. */
    public static function pool(Student $student, int $sessionId): float
    {
        return round((float) StudentAdvance::where('student_id', $student->id)
            ->where('to_session_id', $sessionId)
            ->where('status', StudentAdvance::CARRIED)
            ->sum('amount'), 2);
    }

    /** @return array{type: string, amount: float}|null  null when there is no leftover excess */
    public static function settle(Student $student, AcademicSession $fromSession, ?AcademicSession $toSession, PromotionLog $log): ?array
    {
        $plan     = ExcessAdjustmentService::plan($student, (int) $fromSession->id);
        $leftover = round($plan['excess_total'] - $plan['adjustable_total'] + ($plan['unused_advance'] ?? 0), 2);

        if ($leftover <= 0.005) {
            return null;
        }

        self::wallet($student, (int) $fromSession->id, StudentTransaction::DEBIT, $leftover,
            $toSession ? 'Advance carried to ' . $toSession->name : 'Excess paid held for refund', $log);

        if ($toSession) {
            self::wallet($student, (int) $toSession->id, StudentTransaction::CREDIT, $leftover,
                'Advance carried from ' . $fromSession->name, $log);

            StudentAdvance::create([
                'institute_id'     => $student->institute_id,
                'student_id'       => $student->id,
                'from_session_id'  => $fromSession->id,
                'to_session_id'    => $toSession->id,
                'promotion_log_id' => $log->id,
                'amount'           => $leftover,
                'status'           => StudentAdvance::CARRIED,
            ]);
            $type = 'advance';
        } else {
            FeeRefund::create([
                'institute_id'        => $student->institute_id,
                'student_id'          => $student->id,
                'academic_session_id' => $fromSession->id,
                'promotion_log_id'    => $log->id,
                'amount'              => $leftover,
                'status'              => FeeRefund::PENDING,
                'reason'              => 'Excess fee paid at course exit',
            ]);
            $type = 'refund';
        }

        JournalService::safePostAdvanceReclass(
            $student,
            (int) $fromSession->id,
            $leftover,
            self::journalKey($log, $student),
            ucfirst($type) . ' from excess fee paid by ' . $student->name
        );

        AuditLogService::log(
            (int) $student->institute_id,
            'fee_advance',
            $type === 'advance' ? 'carried' : 'refund_requested',
            ucfirst($type) . ' of ' . number_format($leftover, 2) . ' for ' . $student->name,
            $student,
            ['promotion_log_id' => $log->id, 'from_session' => $fromSession->id, 'to_session' => $toSession?->id]
        );

        return ['type' => $type, 'amount' => $leftover];
    }

    /**
     * Called when a promotion is reversed. The wallet rows are reversed generically (they carry
     * the promotion log id); this takes the advance / refund request and the journal reclass back.
     */
    public static function reverseForLog(PromotionLog $log, Student $student): void
    {
        StudentAdvance::where('promotion_log_id', $log->id)
            ->where('status', StudentAdvance::CARRIED)
            ->update(['status' => StudentAdvance::REVERSED]);

        $refunds = FeeRefund::where('promotion_log_id', $log->id)
            ->whereIn('status', [FeeRefund::PENDING, FeeRefund::APPROVED])
            ->update(['status' => FeeRefund::CANCELLED, 'remarks' => 'Cancelled: promotion reversed']);

        $hadAny = StudentAdvance::where('promotion_log_id', $log->id)->exists()
            || FeeRefund::where('promotion_log_id', $log->id)->exists();

        if ($hadAny) {
            JournalService::safeReverseAdvanceReclass(
                (int) $student->institute_id,
                self::journalKey($log, $student),
                'Reversal: advance reclass of ' . $student->name
            );
        }
    }

    /** True when a refund raised by this promotion has already been paid out (reversal must stop). */
    public static function hasPaidRefund(PromotionLog $log): bool
    {
        return FeeRefund::where('promotion_log_id', $log->id)->where('status', FeeRefund::PAID)->exists();
    }

    public static function journalKey(PromotionLog $log, Student $student): string
    {
        return 'advance-reclass:log:' . $log->id . ':student:' . $student->id;
    }

    private static function wallet(Student $student, int $sessionId, int $type, float $amount, string $des, PromotionLog $log): void
    {
        StudentWallet::firstOrCreate(
            ['student_id' => $student->id, 'academic_session_id' => $sessionId],
            ['institute_id' => $student->institute_id, 'main_b' => 0.00]
        );

        $wallet = StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->lockForUpdate()
            ->first();

        $opBal = (float) $wallet->main_b;
        $clBal = $type === StudentTransaction::DEBIT ? round($opBal - $amount, 2) : round($opBal + $amount, 2);

        StudentTransaction::create([
            'student_id'          => $student->id,
            'institute_id'        => $student->institute_id,
            'academic_session_id' => $sessionId,
            'des'                 => $des,
            'credit'              => $type === StudentTransaction::CREDIT ? $amount : 0.00,
            'debit'               => $type === StudentTransaction::DEBIT ? $amount : 0.00,
            'type'                => $type,
            'date'                => now()->toDateString(),
            'op_bal'              => $opBal,
            'cl_bal'              => $clBal,
            'promotion_log_id'    => $log->id,
            'by_user_id'          => null,
        ]);

        $wallet->main_b = $clBal;
        $wallet->save();
    }
}
