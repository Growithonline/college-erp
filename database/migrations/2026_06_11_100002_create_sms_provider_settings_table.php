<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_provider_settings')) {
            return;
        }
        Schema::create('sms_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('provider')->default('msg91'); // msg91, fast2sms, twilio
            $table->text('api_key'); // encrypted
            $table->string('sender_id', 20);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sms_disabled')->default(false); // super admin can disable
            $table->timestamps();

            $table->unique('institute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_provider_settings');
    }
};
