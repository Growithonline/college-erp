<?php

namespace App\Console\Commands;

use App\Models\FeeInvoiceItem;
use App\Models\PromotionLog;
use App\Models\StudentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

// Bulk companion of fees:correct-carried-due. A semester promotion charged "Promotion fee: <label>"
// from a fee rule that was edited/removed afterwards (see promotion-fees:audit). When the student
// was then session-promoted, that fee was frozen into the new session's "Previous Due (...)" debit.
// For each such student this lowers that debit by the stale fee — never more than what the session
// promotion's own due breakdown says was still unpaid for it — by calling fees:correct-carried-due
// (ledger credit + wallet + promotion log; originals are never edited). Dry-run unless --apply.
// Students not session-promoted yet are left to fees:sync-rule-change.
class CorrectStalePromotionFeeCarry extends Command
{
    protected $signature = 'fees:correct-stale-promotion-fee
        {--label= : fee name to correct, e.g. MISC_FEE_CARD (required)}
        {--from= : first promotion date, Y-m-d}
        {--to= : last promotion date, Y-m-d}
        {--institute=}
        {--student=}
        {--include-sem1 : also remove the same fee from the "Previous Due (Before Semester N)" part carried from the earlier semester}
        {--apply : Actually write the changes (default is dry-run)}
        {--yes : Skip the confirmation prompt when --apply is used}';

    protected $description = 'Bulk-lower session-promotion "Previous Due" debits that carry a promotion fee current rules no longer produce. Dry-run unless --apply.';

    public function handle(): int
    {
        $label = trim((string) $this->option('label'));
        if ($label === '') {
            $this->error('Required: --label=<fee name>.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $stale = AuditPromotionFees::findStale(
            $this->option('institute') ? (int) $this->option('institute') : null,
            $this->option('student') ? (int) $this->option('student') : null,
            $this->option('from') ?: null,
            $this->option('to') ?: null,
            $label,
            fn($msg) => $this->warn($msg)
        );

        $plan = [];
        $skipped = [];

        foreach ($stale['flagged'] as $row) {
            if ($row['type'] !== 'semester') {
                $skipped[] = [$row['student_id'], $row['log'], 'not a semester promotion charge'];
                continue;
            }

            $semLog = PromotionLog::find($row['log']);
            $sessionLog = $semLog ? PromotionLog::where('student_id', $semLog->student_id)
                ->where('promotion_type', 'session')
                ->where('is_reversed', false)
                ->where('from_session_id', $semLog->from_session_id)
                ->where('id', '>', $semLog->id)
                ->orderBy('id')
                ->first() : null;

            if (!$sessionLog) {
                $skipped[] = [$row['student_id'], $row['log'], 'not session-promoted (use fees:sync-rule-change)'];
                continue;
            }

            $debit = StudentTransaction::where('promotion_log_id', $sessionLog->id)
                ->where('type', StudentTransaction::DEBIT)
                ->where('des', 'like', 'Previous Due (%')
                ->first();
            if (!$debit) {
                $skipped[] = [$row['student_id'], $row['log'], 'no Previous Due debit on the session promotion'];
                continue;
            }

            $breakdown = collect($sessionLog->carry_forward_context['due_breakdown'] ?? []);
            $stillDue = round((float) $breakdown->where('label', $label)->sum('amount'), 2);
            $remove = round(min($row['charged'] - $row['today'], $stillDue), 2);

            if ($this->option('include-sem1')) {
                $semDue = round((float) collect($semLog->carry_forward_context['due_breakdown'] ?? [])->where('label', $label)->sum('amount'), 2);
                $prevDue = round((float) $breakdown->filter(fn($b) => str_starts_with((string) $b['label'], 'Previous Due (Before Semester'))->sum('amount'), 2);
                $remove = round($remove + min($semDue, $prevDue), 2);
            }

            if ($remove <= 0) {
                $skipped[] = [$row['student_id'], $row['log'], "{$label} no longer pending in the carried due (already paid)"];
                continue;
            }

            $from = round((float) $debit->debit, 2);
            $target = round($from - $remove, 2);
            if ($target < 0) {
                $skipped[] = [$row['student_id'], $row['log'], "correction {$remove} exceeds carried {$from}"];
                continue;
            }

            $paid = FeeInvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $debit->student_id)
                    ->where('academic_session_id', $debit->academic_session_id)->where('is_cancelled', false))
                ->where('fee_name', 'like', 'Previous Due (%')
                ->selectRaw('COALESCE(SUM(amount), 0) as a, COALESCE(SUM(COALESCE(discount, 0)), 0) as d')
                ->first();
            $paidTotal = round((float) ($paid?->a ?? 0) + (float) ($paid?->d ?? 0), 2);
            if ($paidTotal > $target + 0.005) {
                $skipped[] = [$row['student_id'], $row['log'], "already paid {$paidTotal} against Previous Due; corrected {$target} would be excess — review by hand"];
                continue;
            }

            $plan[] = [
                'student' => $debit->student_id, 'uid' => $row['uid'], 'txn' => $debit->id,
                'from' => $from, 'remove' => $remove, 'to' => $target,
            ];
        }

        $this->info('Stale charges found: ' . count($stale['flagged']) . ' | to correct: ' . count($plan) . ' | skipped: ' . count($skipped));

        if ($plan) {
            $this->table(['Student', 'UID', 'Prev Due txn', 'Carried', 'Remove', 'New carried'], array_map('array_values', $plan));
            $this->line('Total to remove: Rs ' . number_format(collect($plan)->sum('remove'), 2));
        }
        if ($skipped) {
            $this->warn('Skipped:');
            $this->table(['Student', 'Log', 'Reason'], $skipped);
        }

        if (!$apply) {
            $this->info('DRY-RUN — nothing written. Re-run with --apply to write.');
            return self::SUCCESS;
        }

        if (!$plan) {
            return self::SUCCESS;
        }

        if (!$this->option('yes')
            && !$this->confirm('Correct ' . count($plan) . ' students now? Take a DB backup first.')) {
            return self::FAILURE;
        }

        $ok = 0;
        foreach ($plan as $p) {
            $code = Artisan::call('fees:correct-carried-due', [
                '--txn' => $p['txn'], '--to' => $p['to'], '--apply' => true, '--yes' => true,
            ]);
            $out = trim(Artisan::output());
            if ($code === self::SUCCESS) {
                $ok++;
            } else {
                $this->error("Student {$p['student']}: {$out}");
            }
        }

        $this->info("APPLIED: {$ok} of " . count($plan) . ' corrected.');

        return self::SUCCESS;
    }
}
