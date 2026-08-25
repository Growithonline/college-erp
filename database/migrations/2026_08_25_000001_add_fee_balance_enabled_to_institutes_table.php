<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            // Defaults false: an institute must opt in after confirming their SMS
            // OTP provider is configured, since the public fee-balance page relies on it.
            $table->boolean('fee_balance_enabled')->default(false)->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn('fee_balance_enabled');
        });
    }
};
