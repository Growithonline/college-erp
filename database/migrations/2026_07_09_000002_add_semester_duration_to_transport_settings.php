<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reference length of one billing period for semester-frequency transport routes,
     * used to prorate mid-semester joins/route-transfers/cancellations. academic_sessions
     * cannot be used for this — a session row spans an entire academic year (all
     * semesters of that year), not a single semester — so this is a deliberate,
     * institute-configured value instead.
     */
    public function up(): void
    {
        Schema::table('institute_transport_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester_duration_months')->default(6)->after('yearly_fee_cross_session');
        });
    }

    public function down(): void
    {
        Schema::table('institute_transport_settings', function (Blueprint $table) {
            $table->dropColumn('semester_duration_months');
        });
    }
};
