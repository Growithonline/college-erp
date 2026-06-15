<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('certificate_type_id');
            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->string('certificate_number', 50)->unique();
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('issued');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('issued_by');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('certificate_type_id')->references('id')->on('certificate_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
