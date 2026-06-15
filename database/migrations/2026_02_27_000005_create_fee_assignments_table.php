<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')
                  ->constrained('academic_sessions')
                  ->onDelete('cascade');

            // Fee kis cheez pe apply hoti hai — course ya subject
            $table->enum('applies_to', ['course', 'subject']);

            // Course-wise fee ke liye
            $table->foreignId('course_stream_id')
                  ->nullable()
                  ->constrained('course_streams')
                  ->onDelete('cascade');
            $table->foreignId('course_part_id')
                  ->nullable()
                  ->constrained('course_parts')
                  ->onDelete('cascade');

            // Subject-wise fee ke liye (theory ya practical alag alag)
            $table->foreignId('subject_component_id')
                  ->nullable()
                  ->constrained('subject_components')
                  ->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_assignments');
    }
};
