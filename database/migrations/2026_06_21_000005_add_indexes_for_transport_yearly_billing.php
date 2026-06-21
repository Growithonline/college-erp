<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MED-1: Index academic_year — queried in WalletService yearly-fee cross-session check
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->index('academic_year', 'academic_sessions_academic_year_idx');
        });

        // transport_monthly_charges already has unique(['transport_allocation_id','charge_month'])
        // which covers the lookup. Adding a covering index on institute_id for the semesterBilledIds query.
        Schema::table('transport_monthly_charges', function (Blueprint $table) {
            if (!Schema::hasIndex('transport_monthly_charges', 'tmc_inst_alloc_idx')) {
                $table->index(['institute_id', 'transport_allocation_id'], 'tmc_inst_alloc_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex('academic_sessions_academic_year_idx');
        });

        Schema::table('transport_monthly_charges', function (Blueprint $table) {
            $table->dropIndex('tmc_inst_alloc_idx');
        });
    }
};
