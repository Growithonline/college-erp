<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simple accounting-period lock: any date on/before this cutoff can no longer
 * have its expenses/salary payments reversed. Null = no lock (default, current behavior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->date('locked_before_date')->nullable()->after('wallet_low_balance_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn('locked_before_date');
        });
    }
};
