<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the fee-collection student search on large institutes.
 *
 * The search runs inside one institute + one session and returns the first 50 matches ordered by
 * name, student_uid. This composite index lets MySQL walk that range already in sorted order and
 * stop at the 51st match instead of scanning and sorting every student of the session.
 * The two identity-snapshot indexes let the roll / form / enrollment prefix lookup use index merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('students', 'idx_stu_search_range')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['institute_id', 'academic_session_id', 'name', 'student_uid'], 'idx_stu_search_range');
            });
        }

        Schema::table('student_academic_identity', function (Blueprint $table) {
            if (!Schema::hasIndex('student_academic_identity', 'idx_sai_roll_snapshot')) {
                $table->index('roll_no_snapshot', 'idx_sai_roll_snapshot');
            }
            if (!Schema::hasIndex('student_academic_identity', 'idx_sai_enroll_snapshot')) {
                $table->index('enrollment_no_snapshot', 'idx_sai_enroll_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_stu_search_range');
        });

        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropIndex('idx_sai_roll_snapshot');
            $table->dropIndex('idx_sai_enroll_snapshot');
        });
    }
};
