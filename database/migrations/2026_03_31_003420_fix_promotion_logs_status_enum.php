<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM mein naye values add karo
        // 'completed' — course finish ho gaya
        // 'reversed'  — promotion wapas li gayi
        DB::statement("
            ALTER TABLE promotion_logs
            MODIFY COLUMN status ENUM('promoted','failed','backlog','dropped','completed','reversed')
            NOT NULL DEFAULT 'promoted'
        ");
    }

    public function down(): void
    {
        // Pehle 'completed'/'reversed' wale rows ko 'promoted' kar do, phir ENUM shrink karo
        DB::statement("
            UPDATE promotion_logs
            SET status = 'promoted'
            WHERE status IN ('completed', 'reversed')
        ");

        DB::statement("
            ALTER TABLE promotion_logs
            MODIFY COLUMN status ENUM('promoted','failed','backlog','dropped')
            NOT NULL DEFAULT 'promoted'
        ");
    }
};