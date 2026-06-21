<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transport_routes MODIFY COLUMN billing_frequency ENUM('one_time','monthly','quarterly','semester','yearly') NOT NULL DEFAULT 'one_time'");
    }

    public function down(): void
    {
        // Move any yearly routes to one_time before reverting enum
        DB::statement("UPDATE transport_routes SET billing_frequency = 'one_time' WHERE billing_frequency = 'yearly'");
        DB::statement("ALTER TABLE transport_routes MODIFY COLUMN billing_frequency ENUM('one_time','monthly','quarterly','semester') NOT NULL DEFAULT 'one_time'");
    }
};
