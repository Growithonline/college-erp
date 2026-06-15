<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Widen column so JSON strings fit (varchar 20 is too small)
        DB::statement("ALTER TABLE notices MODIFY COLUMN visible_to TEXT NULL");

        // Step 2: Convert 'all' and empty to full JSON array
        DB::statement("UPDATE notices SET visible_to = '[\"staff\",\"center\",\"channel\",\"students\"]'
                        WHERE visible_to IS NULL OR visible_to = '' OR visible_to = 'all'");

        // Step 3: Wrap remaining single values (staff / center / channel / students)
        DB::statement("UPDATE notices SET visible_to = CONCAT('[\"', visible_to, '\"]')
                        WHERE visible_to NOT LIKE '[%'");

        // Step 4: Switch to proper JSON type
        DB::statement("ALTER TABLE notices MODIFY COLUMN visible_to JSON NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notices MODIFY COLUMN visible_to VARCHAR(20) NULL");
        DB::statement("UPDATE notices SET visible_to = 'all'");
        DB::statement("ALTER TABLE notices MODIFY COLUMN visible_to VARCHAR(20) NOT NULL DEFAULT 'all'");
    }
};
