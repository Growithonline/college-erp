<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentSubject;

class FixStudentSubjectRoles extends Command
{
    protected $signature   = 'fix:student-subject-roles {--dry-run : Preview without saving}';
    protected $description = 'Fix student_subjects.subject_role = "both" rows corrupted by the old promotion bug — restore each student\'s own prior major/minor choice';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        // subject_role = 'both' is only ever a valid value on course_stream_subjects
        // (the master plan, meaning "student may pick either"). The normal admission
        // edit flow always writes an exact 'major'/'minor'/'compulsory' onto a
        // student's own row — so any student_subjects row sitting at 'both' can only
        // have come from the promotion bug that copied the plan's flexible marker
        // straight onto the student.
        $corrupted = StudentSubject::where('subject_role', 'both')
            ->with(['student:id,name,student_uid', 'subject:id,name'])
            ->get();

        $this->info("Found {$corrupted->count()} corrupted row(s)" . ($dryRun ? ' (dry-run)' : ''));

        $fixed = 0;
        $needsReview = 0;

        foreach ($corrupted as $row) {
            $prior = StudentSubject::where('student_id', $row->student_id)
                ->where('subject_id', $row->subject_id)
                ->where('id', '!=', $row->id)
                ->where('subject_role', '!=', 'both')
                ->orderByDesc('academic_session_id')
                ->orderByDesc('year_number')
                ->first();

            $studentLabel = $row->student ? "{$row->student->name} ({$row->student->student_uid})" : "student #{$row->student_id}";
            $subjectLabel = $row->subject->name ?? "subject #{$row->subject_id}";

            if (!$prior) {
                $needsReview++;
                $this->warn("  NEEDS REVIEW — {$studentLabel} / {$subjectLabel}: no prior non-'both' role found, left untouched");
                continue;
            }

            $this->line("  {$studentLabel} / {$subjectLabel}: both -> {$prior->subject_role}");

            if (!$dryRun) {
                $row->update(['subject_role' => $prior->subject_role]);
            }

            $fixed++;
        }

        $this->info($dryRun
            ? "Dry-run complete — {$fixed} row(s) would be fixed, {$needsReview} need manual review."
            : "Done — {$fixed} row(s) fixed, {$needsReview} need manual review.");
    }
}
