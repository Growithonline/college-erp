<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('admitted_by_type', ['admin', 'staff', 'center', 'channel_partner'])
                  ->default('admin')
                  ->after('admitted_by_staff_id');
        });

        // Backfill existing rows (default 'admin' already set by column default;
        // this corrects rows that were admitted via staff/center/partner).
        // DDL above causes implicit commit on MySQL, so this UPDATE runs separately.
        try {
            DB::statement("
                UPDATE students
                SET admitted_by_type = CASE
                    WHEN admitted_by_staff_id IS NOT NULL THEN 'staff'
                    WHEN admission_source = 'center' THEN 'center'
                    WHEN admission_source = 'channel_partner' THEN 'channel_partner'
                    ELSE 'admin'
                END
            ");
        } catch (\Throwable $e) {
            // Column exists with safe default 'admin'; log and continue rather than blocking deploy.
            \Illuminate\Support\Facades\Log::error('admitted_by_type backfill failed: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('admitted_by_type');
        });
    }
};
