<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets an institute set a DIFFERENT (or same) subject/practical fee for a specific
// student_type (e.g. "Lateral Entry") on top of the existing course/subject/year/
// semester scoping. Existing rows default to 'all', so every current fee rule keeps
// applying to every student exactly as before — this is purely additive.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->string('student_type', 50)->default('all')->after('semester');
        });

        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->dropUnique('subject_fee_rules_unique');
        });

        Schema::table('subject_fee_rules', function (Blueprint $table) {
            $table->unique([
                'institute_id', 'academic_session_id',
                'course_id', 'subject_id', 'course_part', 'semester', 'student_type',
            ], 'subject_fee_rules_unique');
        });
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
