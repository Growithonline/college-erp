<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_change_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->cascadeOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('year_number')->default(1);
            $table->unsignedSmallInteger('semester')->default(1);

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            // Subject info snapshot (immutable at time of change)
            $table->string('subject_name', 100);
            $table->string('subject_code', 20)->nullable();

            // What happened
            $table->enum('action', ['added', 'removed']);

            // Role before and after
            $table->string('previous_role', 20)->nullable(); // major, minor, compulsory, null
            $table->string('new_role', 20)->nullable();

            // Fee impact breakdown
            $table->decimal('subject_fee', 10, 2)->default(0.00);   // pure subject fee
            $table->decimal('practical_fee', 10, 2)->default(0.00); // practical fee if has_practical
            $table->decimal('total_fee_impact', 10, 2)->default(0.00);
            // positive = extra charge (added), negative = credit (removed)

            // Paid / unpaid split (only meaningful on 'removed')
            $table->decimal('paid_portion', 10, 2)->default(0.00);   // already paid → credit note
            $table->decimal('unpaid_portion', 10, 2)->default(0.00); // not yet paid → direct cancel

            // adjustment_type:
            //   'debit'         → subject added, fee charged
            //   'credit_cancel' → subject removed, fee was UNPAID  (direct cancel)
            //   'credit_note'   → subject removed, fee was PAID     (advance credit in wallet)
            $table->enum('adjustment_type', ['debit', 'credit_cancel', 'credit_note'])->nullable();

            // Link to the wallet transaction created for this change
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('student_transactions')
                ->nullOnDelete();

            // Actor who made the change
            $table->unsignedBigInteger('by_user_id')->nullable();
            $table->string('actor_type', 30)->nullable();  // web, staff, center, partner
            $table->string('actor_name', 100)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['student_id', 'academic_session_id'], 'scl_student_session');
            $table->index(['student_id', 'subject_id'], 'scl_student_subject');
            $table->index(['institute_id', 'academic_session_id'], 'scl_institute_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_change_logs');
    }
};
