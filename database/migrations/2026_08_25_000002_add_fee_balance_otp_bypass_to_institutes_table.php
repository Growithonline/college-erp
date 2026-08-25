<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            // Defaults false (OTP required) — same naming/polarity as students.sms_otp_bypass.
            // Lets an institute without an SMS provider configured yet still use the public
            // fee-balance page: identity match alone (course + identifier + DOB + mobile)
            // reveals the balance, skipping the OTP step.
            $table->boolean('fee_balance_otp_bypass')->default(false)->after('fee_balance_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn('fee_balance_otp_bypass');
        });
    }
};
