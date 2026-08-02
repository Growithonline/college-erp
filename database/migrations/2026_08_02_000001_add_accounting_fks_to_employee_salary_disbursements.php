<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Null out any dangling references first, so this migration is safe to run
        // regardless of current data state (these columns were unconstrained before).
        DB::table('employee_salary_disbursements')
            ->whereNotNull('expense_account_id')
            ->whereNotIn('expense_account_id', DB::table('accounts')->select('id'))
            ->update(['expense_account_id' => null]);

        DB::table('employee_salary_disbursements')
            ->whereNotNull('payment_account_id')
            ->whereNotIn('payment_account_id', DB::table('accounts')->select('id'))
            ->update(['payment_account_id' => null]);

        DB::table('employee_salary_disbursements')
            ->whereNotNull('bank_account_id')
            ->whereNotIn('bank_account_id', DB::table('institute_bank_accounts')->select('id'))
            ->update(['bank_account_id' => null]);

        DB::table('employee_salary_disbursements')
            ->whereNotNull('journal_entry_id')
            ->whereNotIn('journal_entry_id', DB::table('journal_entries')->select('id'))
            ->update(['journal_entry_id' => null]);

        DB::table('employee_salary_disbursements')
            ->whereNotNull('reversal_journal_entry_id')
            ->whereNotIn('reversal_journal_entry_id', DB::table('journal_entries')->select('id'))
            ->update(['reversal_journal_entry_id' => null]);

        // Adding each foreign key also creates its supporting index (InnoDB requires one).
        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('payment_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('institute_bank_accounts')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('reversal_journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropForeign(['payment_account_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['reversal_journal_entry_id']);
        });
    }
};
