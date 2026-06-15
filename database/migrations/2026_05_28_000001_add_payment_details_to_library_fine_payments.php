<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_fine_payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('payment_mode')
                  ->constrained('institute_bank_accounts')->nullOnDelete();
            $table->string('transaction_ref', 100)->nullable()->after('bank_account_id');
            $table->string('bank_name', 100)->nullable()->after('transaction_ref');
            $table->datetime('payment_datetime')->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('library_fine_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_account_id');
            $table->dropColumn(['transaction_ref', 'bank_name', 'payment_datetime']);
        });
    }
};
