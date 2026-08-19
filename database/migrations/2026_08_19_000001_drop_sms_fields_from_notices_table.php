<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// SMS is moving off the in-app Notice form entirely — bulk SMS (DLT-template-only, since
// free text can't satisfy a registered DLT template) now lives in its own dedicated flow
// (sms_broadcasts), optionally linking back to a Notice rather than the other way round.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sms_template_id');
            $table->dropColumn(['sms_to', 'sms_template_values']);
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('sms_to')->nullable()->after('email_to');
            $table->foreignId('sms_template_id')->nullable()->after('sms_to')
                ->constrained('sms_templates')->nullOnDelete();
            $table->json('sms_template_values')->nullable()->after('sms_template_id');
        });
    }
};
