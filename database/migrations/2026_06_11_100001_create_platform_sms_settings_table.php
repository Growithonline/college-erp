<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_sms_settings')) {
            return;
        }
        Schema::create('platform_sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('msg91'); // msg91, fast2sms
            $table->text('api_key'); // encrypted
            $table->string('sender_id', 20);
            $table->unsignedTinyInteger('otp_expiry_minutes')->default(5);
            $table->unsignedTinyInteger('otp_max_attempts')->default(3);
            $table->unsignedTinyInteger('otp_resend_cooldown_seconds')->default(30);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_sms_settings');
    }
};
