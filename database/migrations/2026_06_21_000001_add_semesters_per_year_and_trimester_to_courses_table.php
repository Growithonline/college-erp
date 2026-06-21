<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add semesters_per_year column
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('semesters_per_year')->default(2)->after('structure_type');
        });

        // 2. Extend structure_type enum to include 'trimester'
        DB::statement("ALTER TABLE courses MODIFY COLUMN structure_type ENUM('semester','yearly','modular','trimester') NOT NULL DEFAULT 'semester'");

        // 3. Set correct semesters_per_year for all existing courses (zero data loss)
        DB::statement("UPDATE courses SET semesters_per_year = 1 WHERE structure_type = 'yearly'");
        DB::statement("UPDATE courses SET semesters_per_year = 0 WHERE structure_type = 'modular'");
        // semester courses already have default=2, trimester would be new so none exist yet
    }

    public function down(): void
    {
        // Remove trimester from enum first (revert to original)
        DB::statement("UPDATE courses SET structure_type = 'semester' WHERE structure_type = 'trimester'");
        DB::statement("ALTER TABLE courses MODIFY COLUMN structure_type ENUM('semester','yearly','modular') NOT NULL DEFAULT 'semester'");

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('semesters_per_year');
        });
    }
};
