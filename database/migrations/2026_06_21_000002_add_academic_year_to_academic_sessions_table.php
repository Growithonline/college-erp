<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // e.g. "2025-26" — groups semester sessions belonging to same academic year
            // Used by transport yearly billing to avoid double-charging across semesters
            $table->string('academic_year', 10)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
    }
};
