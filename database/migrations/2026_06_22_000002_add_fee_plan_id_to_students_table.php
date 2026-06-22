<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('fee_plan_id')
                  ->nullable()
                  ->after('course_part_id')
                  ->constrained('fee_plans')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['fee_plan_id']);
            $table->dropColumn('fee_plan_id');
        });
    }
};
