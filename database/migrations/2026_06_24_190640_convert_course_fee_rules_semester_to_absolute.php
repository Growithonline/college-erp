<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Convert year-specific fee rules (course_part > 0) from relative to absolute semester.
    // Before this migration, the form always showed relative options (T1, T2, T3) regardless
    // of year, so a Year=2 rule with "T1" was stored as semester=1.
    // After this change the form shows absolute values for year-specific rules
    // (Year 2 trimester → T4, T5, T6), so stored values must be absolute:
    //   semester_new = (course_part - 1) * spy + semester_old
    public function up(): void
    {
        DB::statement("
            UPDATE course_fee_rules cfr
            INNER JOIN courses c ON c.id = cfr.course_id
            SET cfr.semester = (cfr.course_part - 1) * (
                CASE
                    WHEN c.semesters_per_year > 0  THEN c.semesters_per_year
                    WHEN c.structure_type = 'yearly'    THEN 1
                    WHEN c.structure_type = 'trimester' THEN 3
                    ELSE 2
                END
            ) + cfr.semester
            WHERE cfr.course_part > 0 AND cfr.semester > 0
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE course_fee_rules cfr
            INNER JOIN courses c ON c.id = cfr.course_id
            SET cfr.semester = cfr.semester - (cfr.course_part - 1) * (
                CASE
                    WHEN c.semesters_per_year > 0  THEN c.semesters_per_year
                    WHEN c.structure_type = 'yearly'    THEN 1
                    WHEN c.structure_type = 'trimester' THEN 3
                    ELSE 2
                END
            )
            WHERE cfr.course_part > 0 AND cfr.semester > 0
        ");
    }
};
