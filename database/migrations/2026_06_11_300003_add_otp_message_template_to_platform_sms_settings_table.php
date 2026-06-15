<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_sms_settings', function (Blueprint $table) {
            $table->text('otp_message_template')->nullable()->after('otp_resend_cooldown_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('platform_sms_settings', function (Blueprint $table) {
            $table->dropColumn('otp_message_template');
        });
    }
};
