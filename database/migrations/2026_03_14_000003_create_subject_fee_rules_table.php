<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Subject Fee Rules ────────────────────────────────────────────
        // English + BA + Year 1 + Sem 1 = ₹500 subject fee + ₹0 practical
        // Geography + BA + Year 1 + Sem 1 = ₹600 + ₹1000 practical
        // Geography + BA + Year 1 + Sem 2 = ₹550 + ₹900 practical
        Schema::create('subject_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');

            // Year/Part
            $table->tinyInteger('course_part')
                  ->comment('1=1st year, 2=2nd year, 3=3rd year');

            // Semester within that year
            $table->tinyInteger('semester')
                  ->comment('1=sem1, 2=sem2, 0=both');

            // Subject Fee
            $table->decimal('subject_fee', 10, 2)->default(0);

            // Practical Fee (only if subject has_practical = true)
            $table->decimal('practical_fee', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ek subject ka ek course+year+sem me sirf ek rule
            $table->unique([
                'institute_id', 'academic_session_id',
                'course_id', 'subject_id', 'course_part', 'semester'
            ], 'subject_fee_rules_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_fee_rules');
    }
};
