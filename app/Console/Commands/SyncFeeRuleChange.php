<?php

namespace App\Console\Commands;

use App\Models\CourseFeeRule;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use App\Services\FeeCalculatorService;
use App\Services\JournalService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Brings the wallet/ledger of students in the LIVE session in line with the course-fee rules as
// they are today. A fee rule edit never touches wallets that were already charged, so a rule
// lowered from 5,000 to 4,000 left every earlier student on 5,000 (see fees:correct-charged-amount
// for the one-off fix of that case). For each active, non-bulk-import student of the session this
// compares what the ledger says was charged for each COURSE-fee item of the student's current
// semester with what FeeCalculatorService says it should be now, and posts the difference as a
// "Fee adjustment: <fee> (rule changed X to Y)" transaction (history is never edited), moves the
// wallet, lowers/raises the semester-promotion carried-forward snapshot by the pending part, and
// posts a journal adjusting entry for fees that were journaled at admission. Closed semesters are
// untouched (only the current semester's items and yearly items are looked at), subject/practical/
// transport fees are out of scope. Dry-run by default; --apply writes. Re-running is safe — the
// ledger is re-read every time, so an already-synced student shows no difference.
class SyncFeeRuleChange extends Command
{
    protected $signature = 'fees:sync-rule-change
        {--institute= : Institute id}
        {--session= : Live academic session id}
        {--course= : Limit to one course id}
        {--fee-type= : Limit to one fee_type_id}
        {--student= : Limit to one student id (pilot run)}
        {--from= : Only adjust rows whose ledger-charged amount equals this (the old rule amount)}
        {--include-new : Also charge items that were never charged to the student (rule added later)}
        {--include-removed : Also reverse items that no longer have a rule}
        {--apply : Actually write the changes (default is dry-run)}
        {--yes : Skip the confirmation prompt when --apply is used}';

    protected $description = 'Sync live-session students to the current course fee rules (ledger, wallet, journal, carried-forward snapshot). Dry-run unless --apply.';

    public function handle(): int
    {
        $instituteId = (int) $this->option('institute');
        $sessionId   = (int) $this->option('session');

        if ($instituteId <= 0 || $sessionId <= 0) {
            $this->error('Required: --institute and --session.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $query = Student::with('stream')
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('status', 'active')
            ->where(fn($q) => $q->where('is_bulk_import', false)->orWhereNull('is_bulk_import'));

        if ($this->option('course')) {
            $courseId = (int) $this->option('course');
            $query->whereHas('stream', fn($q) => $q->where('course_id', $courseId));
        }
        if ($this->option('student')) {
            $query->where('id', (int) $this->option('student'));
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('No matching students.');
            return self::SUCCESS;
        }

        if ($apply && !$this->option('yes')
            && !$this->confirm("Apply fee-rule sync to up to {$total} student(s)? Take a DB backup first.")) {
            return self::FAILURE;
        }

        $rows = [];
        $changed = 0;
        $increase = 0.0;
        $decrease = 0.0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(100, function ($students) use (&$rows, &$changed, &$increase, &$decrease, &$skipped, $sessionId, $apply) {
            foreach ($students as $student) {
                try {
                    $result = DB::transaction(fn() => $this->syncStudent($student, $sessionId, $apply));
                } catch (\Throwable $e) {
                    $result = [['student' => $student, 'fee' => '-', 'result' => 'ERROR', 'diff' => 0.0, 'note' => $e->getMessage()]];
                }

                foreach ($result as $r) {
                    $rows[] = [
                        $r['student']->id,
                        $r['student']->student_uid,
                        $r['fee'],
                        $r['result'],
                        $r['diff'] != 0.0 ? number_format($r['diff'], 2, '.', '') : '',
                        $r['note'],
                    ];
                    if ($r['result'] === 'OK') {
                        $changed++;
                        $r['diff'] > 0 ? $increase += $r['diff'] : $decrease += abs($r['diff']);
                    } else {
                        $skipped++;
                    }
                }
            }
        });

        if (empty($rows)) {
            $this->info(($apply ? 'APPLIED' : 'DRY-RUN') . ": {$total} student(s) checked, nothing to change.");
            return self::SUCCESS;
        }

        $this->table(['id', 'student_uid', 'fee', 'result', 'diff', 'note'], $rows);
        $this->info(($apply ? 'APPLIED' : 'DRY-RUN') . ": {$total} student(s) checked, {$changed} adjustment(s) (+" . number_format($increase, 2) . ' / -' . number_format($decrease, 2) . "), {$skipped} skipped/failed.");

        return self::SUCCESS;
    }

    /** @return array<int, array{student: Student, fee: string, result: string, diff: float, note: string}> */
    private function syncStudent(Student $student, int $sessionId, bool $apply): array
    {
        $out = [];
        $row = fn(string $fee, string $result, float $diff, string $note) => [
            'student' => $student, 'fee' => $fee, 'result' => $result, 'diff' => $diff, 'note' => $note,
        ];

        $wallet = StudentWallet::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->lockForUpdate()
            ->first();

        $context = WalletService::resolveAcademicContext($student, $sessionId);
        if (!$wallet || $context['course_id'] <= 0) {
            return [];
        }

        try {
            $feeData = FeeCalculatorService::calculate(
                instituteId:       $student->institute_id,
                sessionId:         $sessionId,
                courseId:          $context['course_id'],
                coursePart:        $context['course_part_year'],
                semester:          $context['semester'],
                studentType:       $student->student_type ?? 'regular',
                admissionSource:   $student->admission_source ?? 'direct',
                category:          $student->category ?? 'general',
                gender:            $student->gender ?? 'other',
                subjectIds:        $context['subject_ids'],
                courseStreamId:    $student->course_stream_id,
                coursePartId:      $context['course_part']->id ?? $student->course_part_id,
                includeYearlyFees: true
            );
        } catch (\Throwable $e) {
            return [$row('-', 'SKIP', 0.0, 'fee calculation failed: ' . $e->getMessage())];
        }

        $expected = collect($feeData['items'] ?? [])
            ->where('type', 'course')
            ->keyBy('label');

        $onlyFeeType = $this->option('fee-type') ? (int) $this->option('fee-type') : null;
        if ($onlyFeeType) {
            $expected = $expected->filter(fn($i) => (int) ($i['fee_type_id'] ?? 0) === $onlyFeeType);
        }

        $txns = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->get();

        $labels = $expected->keys()->all();

        if ($this->option('include-removed')) {
            $courseFeeLabels = FeeType::whereIn('id', CourseFeeRule::where('institute_id', $student->institute_id)
                    ->where('course_id', $context['course_id'])->pluck('fee_type_id'))
                ->pluck('name')->all();

            foreach ($txns as $t) {
                if (preg_match('/^Fee charged: (.+)$/', (string) $t->des, $m)
                    && in_array($m[1], $courseFeeLabels, true) && !in_array($m[1], $labels, true)) {
                    $labels[] = $m[1];
                }
            }
        }

        $semester = (int) $context['semester'];
        $fromOnly = $this->option('from') !== null ? round((float) $this->option('from'), 2) : null;

        foreach (array_unique($labels) as $label) {
            $newAmount = round((float) ($expected[$label]['amount'] ?? 0), 2);

            $charges = $txns->filter(fn($t) => (int) $t->type === StudentTransaction::DEBIT
                && ($t->des === 'Fee charged: ' . $label
                    || preg_match('/^Promotion fee: ' . preg_quote($label, '/') . ' \(Sem \d+\)$/', (string) $t->des)));

            $isNew = false;
            $target = null;
            if ($charges->isEmpty()) {
                $isNew = true;
            } elseif ($charges->count() === 1) {
                $target = $charges->first();
            } else {
                $current = $charges->where('des', 'Promotion fee: ' . $label . ' (Sem ' . $semester . ')');
                if ($current->count() === 1) {
                    $target = $current->first();
                } else {
                    $out[] = $row($label, 'SKIP', 0.0, 'charged more than once, cannot tell which row is the current one');
                    continue;
                }
            }

            $adjustments = $txns->filter(fn($t) => str_starts_with((string) $t->des, 'Fee adjustment: ' . $label . ' ('));
            $adjNet      = round((float) $adjustments->sum('debit') - (float) $adjustments->sum('credit'), 2);
            $chargedNet  = round(($target ? (float) $target->debit : 0.0) + $adjNet, 2);
            $diff        = round($newAmount - $chargedNet, 2);

            if (abs($diff) < 0.01) {
                continue;
            }
            if ($fromOnly !== null && $chargedNet !== $fromOnly) {
                continue;
            }

            if ($isNew && !$this->option('include-new')) {
                $out[] = $row($label, 'SKIP', $diff, "never charged to this student (rule {$newAmount}); use --include-new");
                continue;
            }
            if (!isset($expected[$label]) && !$this->option('include-removed')) {
                $out[] = $row($label, 'SKIP', $diff, 'no rule applies any more; use --include-removed');
                continue;
            }

            $paid = FeeInvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)->where('is_cancelled', false))
                ->where('fee_name', $label)
                ->selectRaw('COALESCE(SUM(amount), 0) as a, COALESCE(SUM(COALESCE(discount, 0)), 0) as d')
                ->first();
            $paidTotal = round((float) ($paid?->a ?? 0) + (float) ($paid?->d ?? 0), 2);
            $note = "{$chargedNet} -> {$newAmount}" . ($paidTotal > $newAmount ? " (already paid {$paidTotal}: excess)" : '');

            $snapshotNote = $this->syncSnapshot($student, $sessionId, $semester, $target, $label, $chargedNet, $newAmount, $apply);
            if ($snapshotNote !== '') {
                $note .= ', ' . $snapshotNote;
            }

            if ($apply) {
                $opBal = (float) $wallet->main_b;
                $clBal = round($opBal - $diff, 2);

                $txn = StudentTransaction::create([
                    'student_id'          => $student->id,
                    'institute_id'        => $student->institute_id,
                    'academic_session_id' => $sessionId,
                    'des'                 => 'Fee adjustment: ' . $label . ' (rule changed ' . (float) $chargedNet . ' to ' . (float) $newAmount . ')',
                    'credit'              => $diff < 0 ? abs($diff) : 0.00,
                    'debit'               => $diff > 0 ? $diff : 0.00,
                    'type'                => $diff > 0 ? StudentTransaction::DEBIT : StudentTransaction::CREDIT,
                    'date'                => now()->toDateString(),
                    'op_bal'              => $opBal,
                    'cl_bal'              => $clBal,
                    'by_user_id'          => null,
                ]);

                $wallet->main_b = $clBal;
                $wallet->save();

                // Only fees journaled at admission ("Fee charged: ...") have a receivable to adjust;
                // promotion fees never got a journal entry.
                if ($target && str_starts_with((string) $target->des, 'Fee charged:')) {
                    $entry = JournalService::postFeeRuleAdjustment(
                        $student,
                        $sessionId,
                        ['fee_type_id' => $expected[$label]['fee_type_id'] ?? null, 'label' => $label, 'type' => 'course'],
                        $diff,
                        'fee-adjust:rule:txn:' . $txn->id,
                        "Fee rule change adjustment ({$label}: {$chargedNet} to {$newAmount}) for {$student->name}"
                    );
                    $note .= $entry ? ", journal #{$entry->id}" : ', no admission journal entry';
                } else {
                    $note .= ', no journal (promotion fee)';
                }
            }

            $out[] = $row($label, 'OK', $diff, $note);
        }

        return $out;
    }

    /**
     * When the fee was charged BEFORE this semester's promotion, the promotion's carried-forward
     * due already contains its unpaid part, so that snapshot moves by the change in what is
     * still pending (payments made before the promotion counted). Returns a note ('' = no change).
     */
    private function syncSnapshot(
        Student $student, int $sessionId, int $semester, ?StudentTransaction $target,
        string $label, float $chargedNet, float $newAmount, bool $apply
    ): string {
        if (!$target) {
            return '';
        }

        $log = PromotionLog::where('student_id', $student->id)
            ->where('promotion_type', 'semester')
            ->where('from_session_id', $sessionId)
            ->where('to_session_id', $sessionId)
            ->where('to_semester', $semester)
            ->where('is_reversed', false)
            ->where('status', 'promoted')
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (!$log || $target->created_at >= $log->created_at) {
            return '';
        }

        $paid = FeeInvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)->where('is_cancelled', false)
                ->where('created_at', '<', $log->created_at))
            ->where('fee_name', $label)
            ->selectRaw('COALESCE(SUM(amount), 0) as a, COALESCE(SUM(COALESCE(discount, 0)), 0) as d')
            ->first();
        $paidBefore = (float) ($paid?->a ?? 0) + (float) ($paid?->d ?? 0);

        $delta = max(0.0, $newAmount - $paidBefore) - max(0.0, $chargedNet - $paidBefore);
        $old   = round((float) $log->dues_carried_forward, 2);
        $new   = max(0.0, round($old + $delta, 2));

        if ($new === $old) {
            return '';
        }

        if ($apply) {
            $log->update([
                'dues_carried_forward' => $new,
                'remarks'              => trim(($log->remarks ? $log->remarks . ' | ' : '') . "Carried due {$old} -> {$new} ({$label} rule change)"),
            ]);
        }

        return "carried due {$old} -> {$new}";
    }
}
