<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_year_subject_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_stream_id')
                  ->constrained('course_streams')
                  ->onDelete('cascade');
            $table->integer('year_number');          // 1, 2, 3
            $table->integer('minor_optional_min');   // kam se kam kitne minor choose kare
            $table->integer('minor_optional_max');   // zyada se zyada kitne minor choose kare
            // UG Year 1: min=2 max=2 | UG Year 3: min=1 max=1 | PG Year 2: min=0 max=0
            $table->timestamps();

            $table->unique(['course_stream_id', 'year_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_year_subject_rules');
    }
};
