<?php

namespace App\Console\Commands;

use App\Models\PromotionLog;
use App\Models\StudentSubject;
use App\Models\StudentTransaction;
use App\Services\FeeCalculatorService;
use Illuminate\Console\Command;

// Read-only diagnostic — never writes anything. A promotion charges "Promotion fee: <label> (Sem N)"
// from the fee rules as they stood at that moment. Rules are editable afterwards, so this re-runs the
// same calculation today (same arguments PromotionController::applyPromotionFee() uses) and lists
// every charge that today's rules would NOT produce (rule deleted / re-pointed to another part or
// semester) or would produce for a smaller amount. Those are the students whose carried-forward
// "Previous Due" may include a fee the institute no longer intends to charge.
class AuditPromotionFees extends Command
{
    protected $signature = 'promotion-fees:audit {--institute=} {--student=} {--from=} {--to=} {--label=} {--detail}';

    protected $description = 'Read-only report of "Promotion fee" charges that current fee rules would no longer produce';

    public function handle(): int
    {
        $charges = StudentTransaction::whereNotNull('promotion_log_id')
            ->where('des', 'like', 'Promotion fee:%')
            ->where('type', StudentTransaction::DEBIT);

        if ($this->option('institute')) {
            $charges->where('institute_id', (int) $this->option('institute'));
        }
        if ($this->option('student')) {
            $charges->where('student_id', (int) $this->option('student'));
        }
        if ($this->option('from')) {
            $charges->whereDate('created_at', '>=', $this->option('from'));
        }
        if ($this->option('to')) {
            $charges->whereDate('created_at', '<=', $this->option('to'));
        }
        if ($this->option('label')) {
            $charges->where('des', 'like', 'Promotion fee: ' . $this->option('label') . ' (Sem %');
        }

        $byLog = $charges->orderBy('id')->get()->groupBy('promotion_log_id');
        $flagged = [];

        foreach ($byLog as $logId => $txns) {
            $log = PromotionLog::with(['student.stream.course', 'toCoursePart'])->find($logId);
            $student = $log?->student;
            if (!$student || !$log->toCoursePart) {
                continue;
            }

            $sessionId = (int) $log->to_session_id;
            $yearNumber = $log->toCoursePart->year_number;
            $subjectIds = StudentSubject::where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('year_number', $yearNumber)
                ->pluck('subject_id')->unique()->values()->all();

            try {
                $now = FeeCalculatorService::calculate(
                    instituteId:       (int) $student->institute_id,
                    sessionId:         $sessionId,
                    courseId:          (int) ($student->stream?->course_id ?? 0),
                    coursePart:        (int) ($yearNumber ?? 1),
                    semester:          (int) $log->to_semester,
                    studentType:       $student->student_type ?? 'regular',
                    admissionSource:   $student->admission_source ?? 'direct',
                    category:          $student->category ?? 'general',
                    gender:            $student->gender ?? 'other',
                    subjectIds:        $subjectIds,
                    courseStreamId:    $student->course_stream_id,
                    coursePartId:      $log->to_course_part_id,
                    includeYearlyFees: $log->promotion_type === 'session',
                    semestersPerYear:  $student->stream?->course?->effectiveSemestersPerYear() ?? 2
                );
            } catch (\Throwable $e) {
                $this->warn("Log #{$logId}: calculation failed — {$e->getMessage()}");
                continue;
            }

            $expected = collect($now['items'] ?? [])->groupBy('label')->map(fn($g) => round((float) $g->sum('amount'), 2));

            foreach ($txns->groupBy('des') as $des => $rows) {
                $label = preg_replace('/^Promotion fee: (.*) \(Sem \d+\)$/', '$1', (string) $des);
                $charged = round((float) $rows->sum('debit'), 2);
                $today = (float) ($expected[$label] ?? 0);

                if ($today + 0.005 >= $charged) {
                    continue;
                }

                $flagged[] = [
                    'student_id' => $student->id,
                    'uid'        => $student->student_uid,
                    'log'        => $logId,
                    'type'       => $log->promotion_type,
                    'fee'        => $label,
                    'charged'    => $charged,
                    'today'      => $today,
                    'on'         => $rows->first()->created_at?->format('Y-m-d H:i'),
                ];
            }
        }

        $this->info('Promotion logs checked: ' . $byLog->count());

        if (empty($flagged)) {
            $this->info('No charge differs from what current rules produce.');
            return self::SUCCESS;
        }

        $this->warn(count($flagged) . ' charge(s) not produced (or produced lower) by current rules, '
            . collect($flagged)->pluck('student_id')->unique()->count() . ' student(s).');

        $this->table(
            ['Student', 'UID', 'Log', 'Type', 'Fee', 'Charged', 'Today', 'On'],
            collect($flagged)->map(fn($r) => array_values($r))->all()
        );

        $this->line('Summary by fee: ' . collect($flagged)->groupBy('fee')
            ->map(fn($g, $fee) => "{$fee} x{$g->count()} (Rs " . number_format($g->sum('charged') - $g->sum('today'), 2) . ')')
            ->implode(', '));

        return self::SUCCESS;
    }
}
