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
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('name');                          // "Registration Fee", "Exam Fee"
            $table->enum('category', [
                'registration', 'course', 'subject_theory',
                'subject_practical', 'exam', 'practical_exam',
                'transport', 'library', 'maintenance', 'computer',
                'fine', 'discount', 'certification', 'other'
            ]);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);   // system-defined types, delete nahi honge
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
