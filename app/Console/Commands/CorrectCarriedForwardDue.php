<?php

namespace App\Console\Commands;

use App\Models\PromotionLog;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// One-off correction of a SESSION promotion's "Previous Due (...)" debit that was booked
// higher than what the student really owed in the old session (the old yearly-fee double
// count). The original debit is never edited: a "Carried due correction: <label>" credit
// brings the new session's wallet down, the promotion log's snapshot is lowered, and the
// Fee Collection page nets that credit off the Previous Due line (see
// WalletService::buildPromotionAwareFeeState()). A session promotion posts no journal entry
// (PromotionController::carryForwardDue()), so there is nothing to adjust in the books.
// Dry-run by default; --apply writes. Re-running is safe — an already corrected debit is skipped.
class CorrectCarriedForwardDue extends Command
{
    protected $signature = 'fees:correct-carried-due
        {--txn= : id of the "Previous Due (...)" debit transaction to correct}
        {--to= : the correct carried amount (what the student really owed in the old session)}
        {--apply : Actually write the changes (default is dry-run)}
        {--yes : Skip the confirmation prompt when --apply is used}';

    protected $description = 'Lower one session-promotion "Previous Due" debit that was carried forward too high (ledger credit + wallet + promotion log). Dry-run unless --apply.';

    public function handle(): int
    {
        $txnId = (int) $this->option('txn');
        $to    = round((float) $this->option('to'), 2);

        if ($txnId <= 0 || $this->option('to') === null || $to < 0) {
            $this->error('Required: --txn=<id> --to=<correct amount>.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        if ($apply && !$this->option('yes')
            && !$this->confirm("Correct Previous Due transaction #{$txnId} down to {$to}? Take a DB backup first.")) {
            return self::FAILURE;
        }

        try {
            $note = DB::transaction(fn() => $this->process($txnId, $to, $apply));
        } catch (\Throwable $e) {
            $this->error('ERROR: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(($apply ? 'APPLIED: ' : 'DRY-RUN: ') . $note);

        return self::SUCCESS;
    }

    private function process(int $txnId, float $to, bool $apply): string
    {
        $txn = StudentTransaction::where('id', $txnId)->lockForUpdate()->first();

        if (!$txn || (int) $txn->type !== StudentTransaction::DEBIT
            || !preg_match('/^Previous Due \([0-9]{4}-[0-9]{2},/', (string) $txn->des)) {
            throw new \RuntimeException("#{$txnId} is not a session-promotion \"Previous Due (...)\" debit.");
        }

        $from = round((float) $txn->debit, 2);
        if ($to >= $from) {
            throw new \RuntimeException("--to ({$to}) must be lower than the booked amount ({$from}).");
        }
        $diff = round($from - $to, 2);

        $correctionDes = 'Carried due correction: ' . $txn->des;
        $already = StudentTransaction::where('student_id', $txn->student_id)
            ->where('academic_session_id', $txn->academic_session_id)
            ->where('des', $correctionDes)
            ->exists();
        if ($already) {
            throw new \RuntimeException('already corrected.');
        }

        $log = $txn->promotion_log_id ? PromotionLog::where('id', $txn->promotion_log_id)->lockForUpdate()->first() : null;
        if (!$log || $log->is_reversed || $log->promotion_type !== 'session') {
            throw new \RuntimeException('linked promotion log is missing, reversed or not a session promotion.');
        }
        if (round((float) $log->dues_carried_forward, 2) !== $from) {
            throw new \RuntimeException("promotion log #{$log->id} carries {$log->dues_carried_forward}, expected {$from}.");
        }

        $wallet = StudentWallet::where('student_id', $txn->student_id)
            ->where('academic_session_id', $txn->academic_session_id)
            ->lockForUpdate()
            ->first();
        if (!$wallet) {
            throw new \RuntimeException('no wallet for the session.');
        }

        $lastClBal = StudentTransaction::where('student_id', $txn->student_id)
            ->where('academic_session_id', $txn->academic_session_id)
            ->orderByDesc('id')
            ->value('cl_bal');
        if ($lastClBal === null || round((float) $lastClBal, 2) !== round((float) $wallet->main_b, 2)) {
            throw new \RuntimeException("wallet {$wallet->main_b} does not match the last ledger balance {$lastClBal}.");
        }

        $newBalance = round((float) $wallet->main_b + $diff, 2);
        $summary = "student {$txn->student_id}: carried {$from} -> {$to} (credit {$diff}), wallet {$wallet->main_b} -> {$newBalance}, promotion log #{$log->id} {$from} -> {$to}";

        if (!$apply) {
            return $summary;
        }

        StudentTransaction::create([
            'student_id'          => $txn->student_id,
            'institute_id'        => $txn->institute_id,
            'academic_session_id' => $txn->academic_session_id,
            'des'                 => $correctionDes,
            'credit'              => $diff,
            'debit'               => 0.00,
            'type'                => StudentTransaction::CREDIT,
            'date'                => now()->toDateString(),
            'op_bal'              => $wallet->main_b,
            'cl_bal'              => $newBalance,
            'by_user_id'          => null,
        ]);

        $wallet->main_b = $newBalance;
        $wallet->save();

        $log->update([
            'dues_carried_forward' => $to,
            'remarks'              => trim(($log->remarks ? $log->remarks . ' | ' : '') . "Carried due corrected {$from} -> {$to} (old-session due was overstated)"),
        ]);

        return $summary;
    }
}
