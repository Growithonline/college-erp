<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * institute_transactions got left out of 2026_07_02_230001_add_performance_indexes_for_large_datasets.php
 * even though its sibling table (student_transactions) received the equivalent index there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institute_transactions', function (Blueprint $table) {
            // Ledger/report queries filter by institute+session on every call
            $table->index(['institute_id', 'academic_session_id'], 'it_institute_session_idx');
            // Date-range report queries filter by institute+date
            $table->index(['institute_id', 'date'], 'it_institute_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('institute_transactions', function (Blueprint $table) {
            $table->dropIndex('it_institute_date_idx');
            $table->dropIndex('it_institute_session_idx');
        });
    }
};
