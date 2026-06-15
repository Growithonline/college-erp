<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practical_fee_token_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('course_part_id')->nullable()->constrained('course_parts')->nullOnDelete();
            $table->unsignedInteger('year_number')->default(1);
            $table->unsignedInteger('semester')->default(1);
            $table->decimal('token_amount', 12, 2)->default(0);
            $table->string('payment_mode', 30)->default('cash');
            $table->date('collection_date')->nullable();
            $table->string('title')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('created_by_type', 30)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'academic_session_id', 'course_id'], 'pftb_context_idx');
        });

        Schema::create('practical_fee_token_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('practical_fee_token_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_invoice_id')->nullable()->constrained('fee_invoices')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('posted');
            $table->string('entered_by_type', 30)->nullable();
            $table->unsignedBigInteger('entered_by_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'student_id'], 'pft_entries_batch_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practical_fee_token_entries');
        Schema::dropIfExists('practical_fee_token_batches');
    }
};
