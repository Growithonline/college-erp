<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');

            // === UNIQUE IDs ===
            $table->string('student_uid')->unique();   // BBA/STU/2026/0001
            $table->string('enrollment_no')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('sr_no')->nullable();

            // === BASIC DETAILS ===
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 15);
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('photo')->nullable();

            $table->enum('nationality', [
                'indian', 'nepali', 'bhutanese', 'sri_lankan', 'others'
            ])->default('indian');

            $table->enum('religion', [
                'hindu', 'muslim', 'sikh', 'christian',
                'jain', 'parsi', 'buddhist', 'others'
            ])->nullable();

            $table->enum('category', [
                'gen', 'obc', 'sc', 'st', 'ews', 'others'
            ])->nullable();

            $table->enum('special_category', [
                'scholarship_quota', 'sports_quota', 'others', 'none'
            ])->default('none');

            $table->string('student_type', 50)->default('regular');

            $table->string('aadhar_no', 20)->nullable();
            $table->string('apaar_no', 20)->nullable();

            $table->enum('admission_type', [
                'new', 'lateral', 'transfer', 're_admission'
            ])->default('new');

            $table->boolean('gap_year')->default(false);
            $table->boolean('is_previous_student')->default(false);
            $table->string('previous_roll_no')->nullable();
            $table->string('previous_percentage')->nullable();

            $table->enum('marital_status', [
                'single', 'married', 'divorced', 'widowed'
            ])->default('single');
            $table->string('spouse_name')->nullable();

            // === PARENTS/GUARDIAN ===
            $table->string('father_name')->nullable();
            $table->string('father_mobile', 15)->nullable();
            $table->string('father_occupation')->nullable();

            $table->string('mother_name')->nullable();
            $table->string('mother_mobile', 15)->nullable();
            $table->string('mother_occupation')->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('guardian_mobile', 15)->nullable();
            $table->enum('guardian_relation', [
                'father', 'mother', 'uncle', 'aunt',
                'brother', 'sister', 'grandfather', 'grandmother', 'others'
            ])->nullable();

            // === PERMANENT ADDRESS ===
            $table->string('perm_state')->nullable();
            $table->string('perm_district')->nullable();
            $table->string('perm_post')->nullable();
            $table->string('perm_thana')->nullable();
            $table->string('perm_pincode', 10)->nullable();
            $table->string('perm_city')->nullable();
            $table->string('perm_address')->nullable();

            // === COMMUNICATION ADDRESS ===
            $table->boolean('comm_same_as_perm')->default(true);
            $table->string('comm_state')->nullable();
            $table->string('comm_district')->nullable();
            $table->string('comm_post')->nullable();
            $table->string('comm_thana')->nullable();
            $table->string('comm_pincode', 10)->nullable();
            $table->string('comm_city')->nullable();
            $table->string('comm_address')->nullable();

            // === COURSE DETAILS ===
            $table->foreignId('course_stream_id')->nullable()->constrained('course_streams')->onDelete('set null');
            $table->foreignId('course_part_id')->nullable()->constrained('course_parts')->onDelete('set null');

            // === STATUS ===
            $table->enum('status', [
                'active', 'inactive', 'detained',
                'passed_out', 'transferred', 'cancelled'
            ])->default('active');

            $table->date('admission_date')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['institute_id', 'academic_session_id']);
            $table->index(['institute_id', 'mobile']);
        });

        // Education details (separate table — multiple rows per student)
        Schema::create('student_education_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('exam_name');           // 10TH, 12TH, GRADUATION
            $table->string('institute_name')->nullable();
            $table->string('board_university')->nullable();
            $table->string('roll_number')->nullable();
            $table->year('passing_year')->nullable();
            $table->string('district')->nullable();
            $table->enum('division', ['I', 'II', 'III', 'pass', 'fail'])->nullable();
            $table->integer('obtained_marks')->nullable();
            $table->integer('max_marks')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_education_details');
        Schema::dropIfExists('students');
    }
};
