<?php

namespace App\Services;

use App\Models\CourseFeeRule;
use App\Models\FeeInvoiceItem;
use App\Models\FeeType;
use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentTransaction;
use App\Models\StudentWallet;
use Illuminate\Support\Facades\DB;

// Brings the wallet/ledger of students in the LIVE session in line with the course-fee rules as
// they are today. A fee rule edit never touches wallets that were already charged, so a rule
// lowered from 5,000 to 4,000 left every earlier student on 5,000 (fees:correct-charged-amount
// was the one-off fix of that case). For each active, non-bulk-import student of the session this
// compares what the ledger says was charged for each COURSE-fee item of the student's current
// semester with what FeeCalculatorService says it should be now, and posts the difference as a
// "Fee adjustment: <fee> (rule changed X to Y)" transaction (history is never edited), moves the
// wallet, lowers/raises the semester-promotion carried-forward snapshot by the pending part, and
// posts a journal adjusting entry for fees that were journaled at admission. Closed semesters are
// untouched (only the current semester's items and yearly items are looked at), subject/practical/
// transport fees are out of scope. Re-running is safe — the ledger is re-read every time, so an
// already-synced student shows no difference. Shared by `fees:sync-rule-change` and the admin
// preview/apply page.
class FeeRuleSyncService
{
    /**
     * @param  array{course?: ?int, fee_type?: ?int, student?: ?int, from?: ?float, include_new?: bool, include_removed?: bool}  $opts
     * @return array{checked: int, rows: array<int, array{student_id: int, uid: ?string, name: ?string, fee: string, result: string, diff: float, note: string}>, changed: int, increase: float, decrease: float, skipped: int}
     */
    public static function run(int $instituteId, int $sessionId, array $opts, bool $apply): array
    {
        $query = Student::with('stream')
            ->where('institute_id', $instituteId)
            ->where('academic_session_id', $sessionId)
            ->where('status', 'active')
            ->where(fn($q) => $q->where('is_bulk_import', false)->orWhereNull('is_bulk_import'));

        if (!empty($opts['course'])) {
            $courseId = (int) $opts['course'];
            $query->whereHas('stream', fn($q) => $q->where('course_id', $courseId));
        }
        if (!empty($opts['student'])) {
            $query->where('id', (int) $opts['student']);
        }

        $report = ['checked' => 0, 'rows' => [], 'changed' => 0, 'increase' => 0.0, 'decrease' => 0.0, 'skipped' => 0];

        $query->orderBy('id')->chunkById(100, function ($students) use (&$report, $sessionId, $opts, $apply) {
            foreach ($students as $student) {
                $report['checked']++;

                try {
                    $results = DB::transaction(fn() => self::syncStudent($student, $sessionId, $opts, $apply));
                } catch (\Throwable $e) {
                    $results = [self::row($student, '-', 'ERROR', 0.0, $e->getMessage())];
                }

                foreach ($results as $r) {
                    $report['rows'][] = $r;
                    if ($r['result'] === 'OK') {
                        $report['changed']++;
                        $r['diff'] > 0 ? $report['increase'] += $r['diff'] : $report['decrease'] += abs($r['diff']);
                    } else {
                        $report['skipped']++;
                    }
                }
            }
        });

        return $report;
    }

    private static function row(Student $student, string $fee, string $result, float $diff, string $note): array
    {
        return [
            'student_id' => (int) $student->id,
            'uid'        => $student->student_uid,
            'name'       => $student->name,
            'fee'        => $fee,
            'result'     => $result,
            'diff'       => $diff,
            'note'       => $note,
        ];
    }

    private static function syncStudent(Student $student, int $sessionId, array $opts, bool $apply): array
    {
        $out = [];

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
            return [self::row($student, '-', 'SKIP', 0.0, 'fee calculation failed: ' . $e->getMessage())];
        }

        $expected = collect($feeData['items'] ?? [])
            ->where('type', 'course')
            ->keyBy('label');

        $onlyFeeType = !empty($opts['fee_type']) ? (int) $opts['fee_type'] : null;
        if ($onlyFeeType) {
            $expected = $expected->filter(fn($i) => (int) ($i['fee_type_id'] ?? 0) === $onlyFeeType);
        }

        $txns = StudentTransaction::where('student_id', $student->id)
            ->where('academic_session_id', $sessionId)
            ->get();

        $labels = $expected->keys()->all();

        if (!empty($opts['include_removed'])) {
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
        $fromOnly = isset($opts['from']) && $opts['from'] !== null && $opts['from'] !== '' ? round((float) $opts['from'], 2) : null;

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
                    $out[] = self::row($student, $label, 'SKIP', 0.0, 'charged more than once, cannot tell which row is the current one');
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

            if ($isNew && empty($opts['include_new'])) {
                $out[] = self::row($student, $label, 'SKIP', $diff, "never charged to this student (rule {$newAmount}); needs 'include new'");
                continue;
            }
            if (!isset($expected[$label]) && empty($opts['include_removed'])) {
                $out[] = self::row($student, $label, 'SKIP', $diff, "no rule applies any more; needs 'include removed'");
                continue;
            }

            $paid = FeeInvoiceItem::whereHas('invoice', fn($q) => $q->where('student_id', $student->id)
                    ->where('academic_session_id', $sessionId)->where('is_cancelled', false))
                ->where('fee_name', $label)
                ->selectRaw('COALESCE(SUM(amount), 0) as a, COALESCE(SUM(COALESCE(discount, 0)), 0) as d')
                ->first();
            $paidTotal = round((float) ($paid?->a ?? 0) + (float) ($paid?->d ?? 0), 2);
            $note = "{$chargedNet} -> {$newAmount}" . ($paidTotal > $newAmount ? " (already paid {$paidTotal}: excess)" : '');

            $snapshotNote = self::syncSnapshot($student, $sessionId, $semester, $target, $label, $chargedNet, $newAmount, $apply);
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

            $out[] = self::row($student, $label, 'OK', $diff, $note);
        }

        return $out;
    }

    /**
     * When the fee was charged BEFORE this semester's promotion, the promotion's carried-forward
     * due already contains its unpaid part, so that snapshot moves by the change in what is
     * still pending (payments made before the promotion counted). Returns a note ('' = no change).
     */
    private static function syncSnapshot(
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
