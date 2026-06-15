<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('student_id');

            // Promotion type
            $table->enum('promotion_type', ['semester', 'session']);

            // From state
            $table->unsignedBigInteger('from_session_id')->nullable();
            $table->unsignedBigInteger('from_course_part_id')->nullable();
            $table->unsignedInteger('from_semester')->nullable();

            // To state
            $table->unsignedBigInteger('to_session_id')->nullable();
            $table->unsignedBigInteger('to_course_part_id')->nullable();
            $table->unsignedInteger('to_semester')->nullable();

            // Dues carry forward (session promotion mein)
            $table->decimal('dues_carried_forward', 10, 2)->default(0);

            // Status
            $table->enum('status', ['promoted', 'failed', 'backlog', 'dropped'])->default('promoted');
            $table->text('remarks')->nullable();

            // Who promoted
            $table->string('promoted_by')->nullable();
            $table->string('promoted_by_role')->nullable(); // institute/staff/center

            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('from_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
            $table->foreign('to_session_id')->references('id')->on('academic_sessions')->onDelete('set null');

            $table->index(['institute_id', 'student_id']);
            $table->index(['institute_id', 'promotion_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_logs');
    }
};