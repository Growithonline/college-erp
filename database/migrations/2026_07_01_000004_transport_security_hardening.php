<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // H2: Soft deletes on transport_allocations — preserves financial audit trail
        // when a student's allocation is cancelled (children: charges + payments stay intact)
        Schema::table('transport_allocations', function (Blueprint $table) {
            $table->softDeletes();

            // H3: compound index for the generate() query: institute_id + is_active
            // (institute_id already has a FK index, but compound with is_active speeds up billing queries)
            if (!Schema::hasIndex('transport_allocations', 'ta_inst_active_idx')) {
                $table->index(['institute_id', 'is_active'], 'ta_inst_active_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transport_allocations', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('ta_inst_active_idx');
        });
    }
};
