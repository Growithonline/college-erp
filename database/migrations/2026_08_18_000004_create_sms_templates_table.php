<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // otp, fee_due_reminder, fee_transaction_alert, admission_alert, notice, exam_info, admit_card, promotion
            $table->string('category')->default('transactional'); // transactional | promotional — picks sender_id vs promo_sender_id
            $table->string('dlt_template_id')->nullable(); // Fast2SMS "Message ID" / MSG91 template id
            $table->text('content'); // display text with named placeholders, e.g. {name}, {amount} — also the literal message for non-Fast2SMS providers
            $table->text('variable_names')->nullable(); // JSON ordered array, e.g. ["name","amount","due_date"] — defines variables_values order for Fast2SMS
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
