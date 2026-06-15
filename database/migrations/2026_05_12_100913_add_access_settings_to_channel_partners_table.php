<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            // Admission controls
            $table->string('admission_form_type')->default('quick')->after('can_add_admission'); // partners default to quick
            $table->json('allowed_courses')->nullable()->after('admission_form_type');            // null = all courses
            $table->json('allowed_sessions')->nullable()->after('allowed_courses');               // null = all sessions

            // Student visibility scope
            $table->string('student_scope')->default('own')->after('can_view_students');          // own | all

            // Fee controls
            $table->string('fee_scope')->default('own')->after('can_collect_fee');                // own | all
            $table->json('allowed_pay_modes')->nullable()->after('fee_scope');                    // null = all modes
            $table->boolean('can_give_discount')->default(false)->after('allowed_pay_modes');
            $table->decimal('max_discount_pct', 5, 2)->default(0)->after('can_give_discount');
            $table->boolean('can_waive_fee')->default(false)->after('max_discount_pct');

            // Reports
            $table->boolean('can_download_reports')->default(false)->after('can_waive_fee');
        });
    }

    public function down(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropColumn([
                'admission_form_type', 'allowed_courses', 'allowed_sessions',
                'student_scope',
                'fee_scope', 'allowed_pay_modes', 'can_give_discount',
                'max_discount_pct', 'can_waive_fee',
                'can_download_reports',
            ]);
        });
    }
};
