<?php

namespace App\Console\Commands;

use App\Models\FeeInvoice;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// One-off correction for students admitted at an old fee rule amount, when the rule was
// later lowered (e.g. TUTION FEE 5000 -> 4000) and nobody has paid anything yet. For each
// matching student it posts a credit "Fee adjustment" transaction (history is never edited),
// moves the wallet balance, lowers the Sem-promotion carried-forward snapshot and posts an
// adjusting journal entry. Dry-run by default; --apply writes. Re-running is safe: a student
// already adjusted is skipped, and every other precondition (single charge row equal to
// --from, no payments/invoices, wallet exactly -from, carried due equal to --from) must
// hold or the student is skipped and reported — never partially changed.
class CorrectChargedFeeAmount extends Command
{
    protected $signature = 'fees:correct-charged-amount
        {--institute= : Institute id}
        {--stream= : course_stream_id of the students}
        {--session= : academic_session_id the fee was charged in}
        {--label= : Fee label exactly as in "Fee charged: <label>", e.g. "TUTION FEE"}
        {--from= : Amount the students were wrongly charged}
        {--to= : Correct amount}
        {--student= : Limit to one student id (pilot run)}
        {--apply : Actually write the changes (default is dry-run)}
        {--yes : Skip the confirmation prompt when --apply is used}';

    protected $description = 'Lower an already-charged fee (unpaid students only) to the corrected rule amount — wallet, ledger, journal and carried-forward snapshot. Dry-run unless --apply.';

    public function handle(): int
    {
        $instituteId = (int) $this->option('institute');
        $streamId    = (int) $this->option('stream');
        $sessionId   = (int) $this->option('session');
        $label       = trim((string) $this->option('label'));
        $from        = round((float) $this->option('from'), 2);
        $to          = round((float) $this->option('to'), 2);

        if (!$instituteId || !$streamId || !$sessionId || $label === '' || $from <= 0 || $to < 0 || $to >= $from) {
            $this->error('Required: --institute --stream --session --label --from --to (and --to must be lower than --from).');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $diff  = round($from - $to, 2);
        $chargeDes = 'Fee charged: ' . $label;
        $adjustDes = "Fee adjustment: {$label} (rule changed " . (float) $from . ' to ' . (float) $to . ')';

        $studentIds = StudentTransaction::where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('des', $chargeDes)
            ->pluck('student_id')
            ->unique();

        $query = Student::whereIn('id', $studentIds)
            ->where('institute_id', $instituteId)
            ->where('course_stream_id', $streamId);

        if ($this->option('student')) {
            $query->where('id', (int) $this->option('student'));
        }

        $students = $query->orderBy('id')->get();

        if ($students->isEmpty()) {
            $this->warn('No matching students.');
            return self::SUCCESS;
        }

        if ($apply && !$this->option('yes')
            && !$this->confirm("Apply {$label}: {$from} -> {$to} to up to {$students->count()} student(s)? Take a DB backup first.")) {
            return self::FAILURE;
        }

        $rows = [];
        $done = 0;
        $skipped = 0;

        foreach ($students as $student) {
            try {
                $result = DB::transaction(fn() => $this->processStudent(
                    $student, $sessionId, $chargeDes, $adjustDes, $from, $to, $diff, $apply
                ));
            } catch (\Throwable $e) {
                $result = ['status' => 'ERROR', 'note' => $e->getMessage()];
            }

            $result['status'] === 'OK' ? $done++ : $skipped++;
            $rows[] = [$student->id, $student->student_uid, $result['status'], $result['note']];
        }

        $this->table(['id', 'student_uid', 'result', 'note'], $rows);
        $this->info(($apply ? 'APPLIED' : 'DRY-RUN') . ": {$done} eligible/changed, {$skipped} skipped/failed, total reduction " . number_format($done * $diff, 2));

        return self::SUCCESS;
    }

    private function processStudent(
        Student $student, int $sessionId, string $chargeDes, string $adjustDes,
        float $from, float $to, float $diff, bool $apply
    ): array {
        $wallet = StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            return ['status' => 'SKIP', 'note' => 'no wallet for session'];
        }

        $txns = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->get();

        if ($txns->contains('des', $adjustDes)) {
            return ['status' => 'SKIP', 'note' => 'already adjusted'];
        }

        $charges = $txns->where('des', $chargeDes);
        if ($charges->count() !== 1 || round((float) $charges->first()->debit, 2) !== $from) {
            return ['status' => 'SKIP', 'note' => 'charge row is not a single ' . $from . ' debit'];
        }

        if ($txns->sum('credit') > 0) {
            return ['status' => 'SKIP', 'note' => 'has credit/payment transactions'];
        }

        if (FeeInvoice::where('student_id', $student->id)->where('academic_session_id', $sessionId)->exists()) {
            return ['status' => 'SKIP', 'note' => 'has fee invoice'];
        }

        if (round((float) $wallet->main_b, 2) !== round(-$from, 2)) {
            return ['status' => 'SKIP', 'note' => 'wallet is ' . $wallet->main_b . ', expected ' . -$from];
        }

        $logs = PromotionLog::where('student_id', $student->id)
            ->where('promotion_type', 'semester')
            ->where('from_session_id', $sessionId)
            ->where('to_session_id', $sessionId)
            ->where('dues_carried_forward', '>', 0)
            ->get();

        foreach ($logs as $log) {
            if (round((float) $log->dues_carried_forward, 2) !== $from) {
                return ['status' => 'SKIP', 'note' => "promotion log #{$log->id} carries {$log->dues_carried_forward}, expected {$from}"];
            }
        }

        if (!$apply) {
            return ['status' => 'OK', 'note' => "would reduce by {$diff}" . ($logs->isEmpty() ? '' : ', ' . $logs->count() . ' promotion log(s)')];
        }

        StudentTransaction::create([
            'student_id'          => $student->id,
            'institute_id'        => $student->institute_id,
            'academic_session_id' => $sessionId,
            'des'                 => $adjustDes,
            'credit'              => $diff,
            'debit'               => 0.00,
            'type'                => StudentTransaction::CREDIT,
            'date'                => now()->toDateString(),
            'op_bal'              => $wallet->main_b,
            'cl_bal'              => -$to,
            'by_user_id'          => null,
        ]);

        $wallet->main_b = -$to;
        $wallet->save();

        foreach ($logs as $log) {
            $log->update([
                'dues_carried_forward' => $to,
                'remarks'              => trim(($log->remarks ? $log->remarks . ' | ' : '') . "Carried due corrected {$from} -> {$to} (fee rule change)"),
            ]);
        }

        $entry = JournalService::postAdmissionFeeReduction(
            $student,
            $sessionId,
            $diff,
            sprintf('fee-adjust:rule-change:student:%d:session:%d:%s', $student->id, $sessionId, md5($chargeDes . $from . $to)),
            "Fee rule change adjustment ({$chargeDes}: {$from} to {$to}) for {$student->name}"
        );

        return ['status' => 'OK', 'note' => 'adjusted ' . $diff . ($entry ? ', journal #' . $entry->id : ', no admission journal entry')];
    }
}
