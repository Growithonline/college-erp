<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_logs')) {
            return;
        }
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // otp, notice, due_reminder
            $table->string('mobile', 15);
            $table->text('message');
            $table->string('provider'); // msg91, fast2sms, platform
            $table->string('sender_id', 20)->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('provider_response')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
