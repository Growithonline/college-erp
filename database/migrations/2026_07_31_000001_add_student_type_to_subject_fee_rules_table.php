<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Lets an institute set a DIFFERENT (or same) subject/practical fee for a specific
// student_type (e.g. "Lateral Entry") on top of the existing course/subject/year/
// semester scoping. Existing rows default to 'all', so every current fee rule keeps
// applying to every student exactly as before — this is purely additive.
//
// Every step is guarded (hasColumn / index-existence check) because MySQL DDL is not
// transactional — each Schema::table() call commits independently, so a mid-migration
// failure (e.g. a deploy timeout) can leave the column added but the index step not
// yet run. A bare re-run would then fail on "column already exists" before ever
// reaching the index change. Guarding each step makes a retry from any partial state
// converge to the same end result instead of failing again.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subject_fee_rules', 'student_type')) {
            Schema::table('subject_fee_rules', function (Blueprint $table) {
                $table->string('student_type', 50)->default('all')->after('semester');
            });
        }

        $indexes = collect(DB::select("SHOW INDEX FROM subject_fee_rules WHERE Key_name = 'subject_fee_rules_unique'"))
            ->pluck('Column_name')
            ->all();

        if (!in_array('student_type', $indexes, true)) {
            if (!empty($indexes)) {
                Schema::table('subject_fee_rules', function (Blueprint $table) {
                    $table->dropUnique('subject_fee_rules_unique');
                });
            }

            Schema::table('subject_fee_rules', function (Blueprint $table) {
                $table->unique([
                    'institute_id', 'academic_session_id',
                    'course_id', 'subject_id', 'course_part', 'semester', 'student_type',
                ], 'subject_fee_rules_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->dropUnique('subject_fee_rules_unique');
        });

        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->unique([
                'institute_id', 'academic_session_id',
                'course_id', 'subject_id', 'course_part', 'semester',
            ], 'subject_fee_rules_unique');
        });

        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->dropColumn('student_type');
        });
    }
};
