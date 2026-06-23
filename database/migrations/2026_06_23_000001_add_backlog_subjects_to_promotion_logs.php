<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_logs', function (Blueprint $table) {
            // Stores [{id, name, code}, ...] for subjects the student is in backlog for.
            // Populated during session promotion when outcome = 'backlog'.
            $table->json('backlog_subjects')->nullable()->after('terminal_status');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_logs', function (Blueprint $table) {
            $table->dropColumn('backlog_subjects');
        });
    }
};
