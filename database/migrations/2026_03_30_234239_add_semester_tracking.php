<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. students table mein current_semester add karo
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_semester')->default(1)->after('course_part_id');
        });

        // Existing students ka current_semester their course_part year_number se set karo
        DB::statement("
            UPDATE students s
            LEFT JOIN course_parts cp ON cp.id = s.course_part_id
            SET s.current_semester = COALESCE(cp.year_number, 1)
        ");

        // 2. student_academic_identity mein history fields add karo
        Schema::table('student_academic_identity', function (Blueprint $table) {
            // Course/Stream snapshot
            $table->unsignedBigInteger('course_stream_id')->nullable()->after('course_id');
            $table->unsignedBigInteger('course_part_id')->nullable()->after('course_stream_id');
            $table->unsignedTinyInteger('semester_at_time')->nullable()->after('course_part_id');

            // Subjects snapshot (JSON)
            $table->json('subjects_json')->nullable()->after('semester_at_time')
                ->comment('Enrolled subject IDs at time of this record');

            // Office details snapshot
            $table->string('sr_no_snapshot')->nullable()->after('subjects_json');
            $table->string('enrollment_no_snapshot')->nullable()->after('sr_no_snapshot');
            $table->string('roll_no_snapshot')->nullable()->after('enrollment_no_snapshot');
            $table->string('admission_source_snapshot')->nullable()->after('roll_no_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('current_semester');
        });
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropColumn([
                'course_stream_id', 'course_part_id', 'semester_at_time',
                'subjects_json', 'sr_no_snapshot', 'enrollment_no_snapshot',
                'roll_no_snapshot', 'admission_source_snapshot',
            ]);
        });
    }
};