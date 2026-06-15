<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `status` ENUM('pending', 'active', 'inactive', 'detained', 'passed_out', 'transferred', 'cancelled')
            NOT NULL DEFAULT 'active'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `status` ENUM('active', 'inactive', 'detained', 'passed_out', 'transferred', 'cancelled')
            NOT NULL DEFAULT 'active'
        ");
    }
};
