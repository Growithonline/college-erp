<?php

namespace App\Console\Commands;

use App\Http\Controllers\Institute\Master\CourseController;
use App\Models\Course;
use Illuminate\Console\Command;

class FixCourseParts extends Command
{
    protected $signature   = 'erp:fix-course-parts';
    protected $description = 'Existing courses ke missing CourseParts generate karo';

    public function handle(): void
    {
        $courses = Course::all();
        $fixed   = 0;

        foreach ($courses as $course) {
            $existingCount = $course->parts()->count();

            if ($existingCount === 0) {
                CourseController::generateCourseParts($course, onlyMissing: false);
                $this->info("Fixed: {$course->name} ({$course->structure_type}, {$course->duration} yr)");
                $fixed++;
            } else {
                // Missing parts add karo (duration badha hua ho toh)
                CourseController::generateCourseParts($course, onlyMissing: true);
                $this->line("Checked: {$course->name} — {$existingCount} parts already exist");
            }
        }

        $this->info("\n✅ Done! {$fixed} courses fixed.");
    }
}