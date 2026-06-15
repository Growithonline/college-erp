<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Course Fee Rules ─────────────────────────────────────────────
        // BA 1st Year + Regular + Direct = ₹2000
        // BA 1st Year + Ex-student + Direct = ₹1500
        // BA 1st Year + Regular + Center = ₹1800
        Schema::create('course_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained()->onDelete('cascade');

            // Year/Part — 1, 2, 3 (0 = all years)
            $table->tinyInteger('course_part')->default(0)
                  ->comment('0=all years, 1=1st year, 2=2nd year...');

            // Semester — 1 or 2 within year (0 = both semesters)
            $table->tinyInteger('semester')->default(0)
                  ->comment('0=both semesters, 1=sem1, 2=sem2');

            // Student Type — dynamic slug from student_types table, or 'all'
            $table->string('student_type', 50)->default('all');

            // Admission Source — null = applies to all
            $table->enum('admission_source', ['direct', 'center', 'channel_partner', 'all'])
                  ->default('all');

            // Category — null = applies to all
            $table->enum('category', ['general', 'obc', 'sc', 'st', 'all'])
                  ->default('all');

            // Gender — null = applies to all
            $table->enum('gender', ['male', 'female', 'other', 'all'])
                  ->default('all');

            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Unique rule — same combination nahi honi chahiye
            $table->unique([
                'institute_id', 'academic_session_id', 'course_id',
                'fee_type_id', 'course_part', 'semester',
                'student_type', 'admission_source', 'category', 'gender'
            ], 'course_fee_rules_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_fee_rules');
    }
};
