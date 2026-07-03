<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate any existing monthly/quarterly routes to 'semester' before shrinking the enum
        DB::statement("
            UPDATE transport_routes
            SET billing_frequency = 'semester'
            WHERE billing_frequency IN ('monthly', 'quarterly')
        ");

        DB::statement("
            ALTER TABLE transport_routes
            MODIFY COLUMN billing_frequency
            ENUM('one_time','semester','yearly') NOT NULL DEFAULT 'one_time'
        ");
    }

    public function down(): void
    {
        // Restore original enum (data that was monthly/quarterly will remain as semester)
        DB::statement("
            ALTER TABLE transport_routes
            MODIFY COLUMN billing_frequency
            ENUM('one_time','monthly','quarterly','semester','yearly') NOT NULL DEFAULT 'one_time'
        ");
    }
};
