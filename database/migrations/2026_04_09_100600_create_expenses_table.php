<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('institute_bank_accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_mode', 30)->default('cash');
            $table->string('vendor_name', 150)->nullable();
            $table->string('bill_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'expense_date'], 'expenses_institute_date_idx');
            $table->index(['institute_id', 'academic_session_id'], 'expenses_institute_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
