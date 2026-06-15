<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academic_identity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('academic_session_id'); // academic_sessions.id

            // Filled by office staff later
            $table->string('form_no')->nullable();
            $table->string('roll_no')->nullable();

            // Extra info
            $table->string('source')->default('admission'); // admission | promotion
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('cascade');

            // Ek student ek session mein ek hi row hoga
            $table->unique(['student_id', 'academic_session_id'], 'uniq_student_session');
            $table->index(['institute_id', 'academic_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_identity');
    }
};