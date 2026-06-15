<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->decimal('wallet_low_balance_threshold', 12, 2)->default(0)->after('rounding_adjustment_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn('wallet_low_balance_threshold');
        });
    }
};
