<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_provider_settings', function (Blueprint $table) {
            $table->text('otp_message_template')->nullable()->after('custom_credentials_json');
            $table->string('otp_id')->nullable()->after('otp_message_template');
        });
    }

    public function down(): void
    {
        Schema::table('sms_provider_settings', function (Blueprint $table) {
            $table->dropColumn(['otp_message_template', 'otp_id']);
        });
    }
};
