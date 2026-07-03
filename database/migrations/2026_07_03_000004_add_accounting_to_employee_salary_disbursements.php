<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_account_id')->nullable()->after('remarks');
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('expense_account_id');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_account_id');
            $table->unsignedBigInteger('journal_entry_id')->nullable()->after('bank_account_id');
            $table->boolean('wallet_debited')->default(false)->after('journal_entry_id');
            $table->json('components_snapshot')->nullable()->after('wallet_debited');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->dropColumn([
                'expense_account_id', 'payment_account_id', 'bank_account_id',
                'journal_entry_id', 'wallet_debited', 'components_snapshot',
            ]);
        });
    }
};
