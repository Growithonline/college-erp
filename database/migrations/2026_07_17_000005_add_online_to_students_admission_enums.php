<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `admission_source` ENUM('direct', 'center', 'channel_partner', 'online')
            NULL DEFAULT 'direct'
        ");

        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `admitted_by_type` ENUM('admin', 'staff', 'center', 'channel_partner', 'online')
            NOT NULL DEFAULT 'admin'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `admission_source` ENUM('direct', 'center', 'channel_partner')
            NULL DEFAULT 'direct'
        ");

        DB::statement("
            ALTER TABLE `students`
            MODIFY COLUMN `admitted_by_type` ENUM('admin', 'staff', 'center', 'channel_partner')
            NOT NULL DEFAULT 'admin'
        ");
    }
};
