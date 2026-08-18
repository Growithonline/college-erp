<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            // Only meaningful for multi-template types (notice, promotion) — single-template
            // types (otp, fee_due_reminder, fee_transaction_alert, admission_alert) leave this
            // null and keep exactly one row per (institute_id, type), enforced by application
            // logic (updateOrCreate keyed on institute_id+type only, name never passed for those).
            $table->string('name')->nullable()->after('type');

            $table->dropUnique(['institute_id', 'type']);
            $table->unique(['institute_id', 'type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'type', 'name']);
            $table->unique(['institute_id', 'type']);
            $table->dropColumn('name');
        });
    }
};
