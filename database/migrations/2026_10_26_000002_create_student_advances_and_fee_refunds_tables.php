<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cash a student paid beyond what their fees came to, carried from the session it was
        // paid in into the next one by a session promotion (WalletService::applyAdvancePool()
        // sets it against the new session's fees; AdvanceService::settle() writes it).
        Schema::create('student_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('to_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('promotion_log_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('status', 12)->default('carried'); // carried | reversed
            $table->timestamps();

            $table->index(['student_id', 'to_session_id', 'status'], 'student_advances_student_session_idx');
        });

        // A student leaving (final year / dropped) with cash left over is owed it back. Staff
        // never create these by hand: they are raised by the final session promotion and then
        // approved and paid by the institute admin (FeeRefundController).
        Schema::create('fee_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete(); // session the cash was paid in
            $table->unsignedBigInteger('promotion_log_id')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('status', 12)->default('pending'); // pending | approved | paid | rejected | cancelled
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('payment_mode', 20)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->string('voucher_no', 40)->nullable()->unique();
            $table->date('paid_on')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'status'], 'fee_refunds_institute_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_refunds');
        Schema::dropIfExists('student_advances');
    }
};
