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
        Schema::create('course_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');

            $table->integer('part_number');                  // 1,2,3,4,5,6
            $table->string('part_name');                     // "Semester 1", "Year 1", "Month 1"
            $table->integer('year_number');                  // 1,1,2,2,3,3 (sem 1&2 = year 1)

            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'part_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_parts');
    }
};
