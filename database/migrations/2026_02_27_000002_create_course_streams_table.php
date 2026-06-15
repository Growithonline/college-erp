<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('name');     // "English", "Hindi", "Geography"
            $table->string('code');     // "BA-ENG", "BA-HIN"
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_streams');
    }
};
