<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->string('short_name', 20)->nullable()->after('name');
            // e.g. "BBA", "SGHPG", "DPGC"
            // Student ID: BBA/STU/2026/0001
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
