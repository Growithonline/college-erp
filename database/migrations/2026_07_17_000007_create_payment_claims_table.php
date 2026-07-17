<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_claimed', 10, 2);
            $table->enum('payment_mode', ['upi_neft', 'pay_at_institute']);
            $table->string('transaction_ref')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('institute_bank_accounts')->nullOnDelete();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('fee_invoice_id')->nullable()->constrained('fee_invoices')->nullOnDelete();
            $table->foreignId('recorded_by_staff_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamps();

            $table->index(['institute_id', 'transaction_ref']);
            $table->index(['student_id', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_claims');
    }
};
