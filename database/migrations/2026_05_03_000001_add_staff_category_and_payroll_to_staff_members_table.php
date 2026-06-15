<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            // Add staff category for payroll/attendance grouping
            $table->enum('staff_category', ['Teaching', 'Office', 'Non-Teaching', 'Guest'])->nullable()->after('status');
            
            // Add payroll type: monthly or daily wage
            $table->enum('payroll_type', ['monthly', 'daily'])->default('monthly')->after('staff_category');
            
            // Daily wage field (used when payroll_type = daily)
            $table->decimal('daily_wage', 12, 2)->nullable()->after('payroll_type');
            
            // Monthly salary (used when payroll_type = monthly)
            $table->decimal('monthly_salary', 12, 2)->nullable()->after('daily_wage');
            
            // Expense head for salary posting
            $table->foreignId('salary_expense_head_id')->nullable()->constrained('accounts')->nullOnDelete()->after('monthly_salary');
            
            // Leave policy group
            $table->string('leave_policy_group', 100)->nullable()->after('salary_expense_head_id');
            
            // Bank details
            $table->string('bank_account_number', 50)->nullable()->after('leave_policy_group');
            $table->string('bank_account_holder', 100)->nullable()->after('bank_account_number');
            $table->string('bank_name', 100)->nullable()->after('bank_account_holder');
            $table->string('bank_ifsc', 20)->nullable()->after('bank_name');
            
            // Index for better querying
            $table->index(['staff_category', 'status'], 'staff_category_status_idx');
            $table->index(['payroll_type', 'status'], 'payroll_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropIndex('staff_category_status_idx');
            $table->dropIndex('payroll_type_status_idx');
            $table->dropForeign(['salary_expense_head_id']);
            $table->dropColumn([
                'staff_category',
                'payroll_type',
                'daily_wage',
                'monthly_salary',
                'salary_expense_head_id',
                'leave_policy_group',
                'bank_account_number',
                'bank_account_holder',
                'bank_name',
                'bank_ifsc',
            ]);
        });
    }
};
