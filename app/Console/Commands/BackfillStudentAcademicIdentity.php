<?php

namespace App\Console\Commands;

use App\Models\PromotionLog;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Support\StudentSnapshotBuilder;
use Illuminate\Console\Command;

class BackfillStudentAcademicIdentity extends Command
{
    protected $signature   = 'students:backfill-identity';
    protected $description = 'Missing student_academic_identity rows backfill karo (current session + promotion_logs history) taaki purane session ki roster hamesha dikhe';

    public function handle(): void
    {
        $currentCreated    = 0;
        $historicalCreated = 0;

        Student::whereNotNull('academic_session_id')->chunkById(200, function ($students) use (&$currentCreated) {
            foreach ($students as $student) {
                $exists = StudentAcademicIdentity::where('student_id', $student->id)
                    ->where('academic_session_id', $student->academic_session_id)
                    ->realOnly()
                    ->exists();

                if ($exists) {
                    continue;
                }

                StudentAcademicIdentity::firstOrCreate(
                    [
                        'student_id'          => $student->id,
                        'academic_session_id' => $student->academic_session_id,
                        'source'              => StudentAcademicIdentity::SOURCE_ADMISSION,
                        'semester_at_time'    => $student->current_semester,
                    ],
                    $this->payload($student, (int) $student->academic_session_id)
                );

                $currentCreated++;
            }
        });

        PromotionLog::whereNotNull('from_session_id')->chunkById(200, function ($logs) use (&$historicalCreated) {
            foreach ($logs as $log) {
                $student = Student::find($log->student_id);
                if (!$student) {
                    continue;
                }

                $exists = StudentAcademicIdentity::where('student_id', $student->id)
                    ->where('academic_session_id', $log->from_session_id)
                    ->realOnly()
                    ->exists();

                if ($exists) {
                    continue;
                }

                StudentAcademicIdentity::firstOrCreate(
                    [
                        'student_id'          => $student->id,
                        'academic_session_id' => $log->from_session_id,
                        'source'              => StudentAcademicIdentity::SOURCE_PROMOTION,
                        'semester_at_time'    => $log->from_semester,
                    ],
                    array_merge(
                        $this->payload($student, (int) $log->from_session_id),
                        [
                            'course_part_id' => $log->from_course_part_id,
                            'remarks'        => 'Backfilled from promotion_logs #' . $log->id,
                        ]
                    )
                );

                $historicalCreated++;
            }
        });

        $this->info("Done. Current-session rows created: {$currentCreated}. Historical rows backfilled from promotion_logs: {$historicalCreated}.");
    }

    private function payload(Student $student, int $sessionId): array
    {
        return [
            'institute_id'                 => $student->institute_id,
            'course_id'                    => $student->stream?->course_id,
            'course_stream_id'             => $student->course_stream_id,
            'course_part_id'               => $student->course_part_id,
            'sr_no_snapshot'               => $student->sr_no,
            'enrollment_no_snapshot'       => $student->enrollment_no,
            'roll_no_snapshot'             => $student->roll_no,
            'admission_source_snapshot'    => $student->admission_source,
            'student_uid_snapshot'         => $student->student_uid,
            'institute_form_no_snapshot'   => $student->institute_form_no,
            'exam_form_no_snapshot'        => $student->exam_form_no,
            'uin_no_snapshot'              => $student->uin_no,
            'reference_no_snapshot'        => $student->reference_no,
            'admission_source_id_snapshot' => $student->admission_source_id,
            'submitted_date_snapshot'      => $student->submitted_date,
            'admission_date_snapshot'      => $student->admission_date,
            'student_status_snapshot'      => $student->status,
            'admission_type'               => $student->admission_type ?? 'new',
            'form_no'                      => $student->sr_no,
            'roll_no'                      => $student->roll_no,
            'profile_snapshot'             => StudentSnapshotBuilder::build($student),
        ];
    }
}
