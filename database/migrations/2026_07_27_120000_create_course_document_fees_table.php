<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_document_fees')) {
            Schema::create('course_document_fees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->decimal('marksheet_fee', 10, 2)->nullable();
                $table->decimal('degree_fee', 10, 2)->nullable();
                $table->timestamps();

                $table->unique(['institute_id', 'course_id'], 'course_document_fees_institute_course_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_document_fees');
    }
};
