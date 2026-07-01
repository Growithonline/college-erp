<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_allocations', function (Blueprint $table) {
            // H2: Soft deletes — preserves financial audit trail on allocation cancellation
            if (!Schema::hasColumn('transport_allocations', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // H3: Compound index (institute_id, is_active) for billing generate() query
        $indexExists = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'transport_allocations'
               AND index_name = 'ta_inst_active_idx'"
        );
        if (($indexExists[0]->cnt ?? 0) === 0) {
            Schema::table('transport_allocations', function (Blueprint $table) {
                $table->index(['institute_id', 'is_active'], 'ta_inst_active_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transport_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('transport_allocations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $table->dropIndex('ta_inst_active_idx');
        });
    }
};
