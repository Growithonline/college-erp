<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `promotion_logs`
            MODIFY COLUMN `promotion_type`
            ENUM('semester', 'session', 'readmission') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `promotion_logs`
            MODIFY COLUMN `promotion_type`
            ENUM('semester', 'session') NOT NULL
        ");
    }
};
