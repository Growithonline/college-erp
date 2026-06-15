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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->string('code');

            $table->integer('duration');
            $table->enum('duration_type', ['year', 'month']);
            $table->enum('structure_type', ['semester', 'yearly', 'modular']);

            $table->boolean('lateral_entry_allowed')->default(false);
            $table->integer('lateral_entry_start_part')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['institute_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
