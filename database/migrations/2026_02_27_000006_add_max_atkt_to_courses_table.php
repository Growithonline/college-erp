<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->integer('max_atkt_allowed')->default(2)->after('structure_type');
            // 0 = ATKT allowed nahi (BEd type yearly courses)
            // 2 = default (semester based UG/PG)
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('max_atkt_allowed');
        });
    }
};
