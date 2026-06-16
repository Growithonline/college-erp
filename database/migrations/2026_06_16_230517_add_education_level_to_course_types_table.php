<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_types', function (Blueprint $table) {
            // ug | pg | diploma | certificate | phd | other (null = show all rows)
            $table->string('education_level', 20)->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('course_types', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
