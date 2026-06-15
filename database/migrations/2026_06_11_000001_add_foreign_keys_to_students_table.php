<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullify orphan values before adding FK constraints
        DB::statement("UPDATE students SET course_type_id = NULL
            WHERE course_type_id IS NOT NULL
              AND course_type_id NOT IN (SELECT id FROM course_types)");

        DB::statement("UPDATE students SET approved_by_staff_id = NULL
            WHERE approved_by_staff_id IS NOT NULL
              AND approved_by_staff_id NOT IN (SELECT id FROM staff_members)");

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('course_type_id')
                  ->references('id')->on('course_types')
                  ->nullOnDelete();

            $table->foreign('approved_by_staff_id')
                  ->references('id')->on('staff_members')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['course_type_id']);
            $table->dropForeign(['approved_by_staff_id']);
        });
    }
};
