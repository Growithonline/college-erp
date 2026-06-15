<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->enum('component_type', ['theory', 'practical']);
            $table->integer('max_marks')->default(100);
            $table->integer('pass_marks')->default(33);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subject_id', 'component_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_components');
    }
};
