<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('institute_bank_accounts')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->unsignedTinyInteger('salary_month');
            $table->unsignedSmallInteger('salary_year');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_payable', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode', 30)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['staff_member_id', 'salary_month', 'salary_year'], 'salary_records_staff_month_year_unique');
            $table->index(['institute_id', 'salary_year', 'salary_month'], 'salary_records_institute_period_idx');
            $table->index(['institute_id', 'status'], 'salary_records_institute_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};
