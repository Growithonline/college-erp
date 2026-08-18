<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            // null = no course/semester restriction (applies institute-wide, existing behavior)
            $table->json('target_course_ids')->nullable()->after('visible_to');
            $table->json('target_semesters')->nullable()->after('target_course_ids');

            $table->foreignId('sms_template_id')->nullable()->after('sms_to')
                ->constrained('sms_templates')->nullOnDelete();
            $table->json('sms_template_values')->nullable()->after('sms_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sms_template_id');
            $table->dropColumn(['target_course_ids', 'target_semesters', 'sms_template_values']);
        });
    }
};
