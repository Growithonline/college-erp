<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_due_reminder_settings')) {
            return;
        }
        Schema::create('sms_due_reminder_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            // Which days to send (days before/after due date as comma-separated: "0,3,7" means on due date, 3 days after, 7 days after)
            $table->string('trigger_days')->default('0,3,7');
            $table->text('message_template')->nullable();
            $table->time('send_time')->default('09:00:00');
            $table->timestamps();

            $table->unique('institute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_due_reminder_settings');
    }
};
