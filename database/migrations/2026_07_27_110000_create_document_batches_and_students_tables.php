<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('document_batches')) {
            Schema::create('document_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('document_type', 20); // marksheet, degree
                $table->string('batch_label', 100)->nullable();
                $table->date('dispatch_date')->nullable();
                $table->string('dispatch_remarks', 300)->nullable();
                $table->date('received_date')->nullable();
                $table->unsignedInteger('received_count')->nullable();
                $table->string('remarks', 300)->nullable();
                $table->timestamps();

                $table->index(['institute_id', 'academic_session_id', 'course_id', 'document_type']);
            });
        }

        if (!Schema::hasTable('document_batch_students')) {
            Schema::create('document_batch_students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_batch_id')->constrained('document_batches')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->timestamp('found_at')->nullable();
                $table->timestamp('distributed_at')->nullable();
                $table->string('received_by_name', 150)->nullable();
                $table->decimal('fee_amount', 10, 2)->nullable();
                $table->foreignId('income_category_id')->nullable()->constrained('institute_income_categories')->nullOnDelete();
                $table->foreignId('manual_income_id')->nullable()->constrained('institute_manual_incomes')->nullOnDelete();
                $table->string('remarks', 300)->nullable();
                $table->timestamps();

                $table->unique(['document_batch_id', 'student_id']);
                $table->index(['document_batch_id', 'found_at']);
                $table->index(['document_batch_id', 'distributed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_batch_students');
        Schema::dropIfExists('document_batches');
    }
};
