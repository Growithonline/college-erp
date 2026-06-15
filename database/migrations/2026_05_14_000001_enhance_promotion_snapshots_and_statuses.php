<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `status` ENUM('pending', 'active', 'inactive', 'detained', 'passed_out', 'backlog', 'failed', 'dropped', 'transferred', 'cancelled')
            NOT NULL DEFAULT 'active'
        ");

        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->string('student_uid_snapshot')->nullable()->after('admission_source_snapshot');
            $table->string('institute_form_no_snapshot')->nullable()->after('student_uid_snapshot');
            $table->string('exam_form_no_snapshot')->nullable()->after('institute_form_no_snapshot');
            $table->string('uin_no_snapshot')->nullable()->after('exam_form_no_snapshot');
            $table->string('reference_no_snapshot')->nullable()->after('uin_no_snapshot');
            $table->unsignedBigInteger('admission_source_id_snapshot')->nullable()->after('reference_no_snapshot');
            $table->date('submitted_date_snapshot')->nullable()->after('admission_source_id_snapshot');
            $table->date('admission_date_snapshot')->nullable()->after('submitted_date_snapshot');
            $table->string('student_status_snapshot')->nullable()->after('admission_date_snapshot');
        });

        Schema::table('promotion_logs', function (Blueprint $table) {
            $table->string('terminal_status', 30)->nullable()->after('status');
            $table->json('carry_forward_context')->nullable()->after('dues_carried_forward');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_logs', function (Blueprint $table) {
            $table->dropColumn(['terminal_status', 'carry_forward_context']);
        });

        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropColumn([
                'student_uid_snapshot',
                'institute_form_no_snapshot',
                'exam_form_no_snapshot',
                'uin_no_snapshot',
                'reference_no_snapshot',
                'admission_source_id_snapshot',
                'submitted_date_snapshot',
                'admission_date_snapshot',
                'student_status_snapshot',
            ]);
        });

        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `status` ENUM('pending', 'active', 'inactive', 'detained', 'passed_out', 'transferred', 'cancelled')
            NOT NULL DEFAULT 'active'
        ");
    }
};
