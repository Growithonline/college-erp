<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_attendance')) {
            return;
        }
        Schema::create('student_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['Present', 'Absent', 'Half Day', 'Holiday', 'Week Off'])->default('Present');
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['institute_id', 'student_id', 'attendance_date'],
                'student_attendance_unique'
            );
            $table->index(['institute_id', 'attendance_date'], 'student_att_date_idx');
            $table->index(['institute_id', 'student_id'], 'student_att_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendance');
    }
};
