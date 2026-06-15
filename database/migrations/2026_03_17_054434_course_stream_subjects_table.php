<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Course Stream ke subjects map karo.
     *
     * Ek stream (BA English) ke andar kaun kaun se subjects hain
     * aur har subject ka role kya hai (major/minor/compulsory/optional)
     * aur kis year mein hai — ye sab store hoga.
     *
     * Example:
     *   BA English + Year 1 + English = major
     *   BA English + Year 1 + Hindi   = minor (chooseable)
     *   BA English + Year 1 + EVS     = compulsory
     */
    public function up(): void
    {
        Schema::create('course_stream_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_stream_id')
                  ->constrained('course_streams')
                  ->onDelete('cascade')
                  ->comment('Kis stream ka subject hai');

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->onDelete('cascade')
                  ->comment('Kaun sa subject');

            // Year number — 1, 2, 3
            $table->unsignedTinyInteger('year_number')
                  ->comment('Kis year mein — 1=1st year, 2=2nd year...');

            // Subject role in this stream+year
            $table->enum('subject_role', ['major', 'minor', 'compulsory', 'optional'])
                  ->comment('major=student choose kare, minor=optional choose kare, compulsory=har student ko lena hai');

            // Is subject ko student choose kar sakta hai ya compulsory hai?
            // major/minor = chooseable, compulsory = auto-included
            $table->boolean('is_chooseable')->default(true)
                  ->comment('true=student choose karega, false=auto include hoga (compulsory)');

            // Sort order for display
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Ek stream+year mein ek subject sirf ek baar
            $table->unique(
                ['course_stream_id', 'subject_id', 'year_number'],
                'css_stream_subject_year_unique'
            );

            // Indexes for performance
            $table->index(['course_stream_id', 'year_number'], 'css_stream_year_idx');
            $table->index(['course_stream_id', 'subject_role'], 'css_stream_role_idx');
        });

        // ── Student Subject Selections ────────────────────────────────────
        // Jab student admit hota hai, usne kaun se subjects choose kiye
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->onDelete('cascade');

            $table->foreignId('academic_session_id')
                  ->constrained('academic_sessions')
                  ->onDelete('cascade');

            // Year number (session ke andar kaunsa year)
            $table->unsignedTinyInteger('year_number')->default(1);

            // Subject role at the time of selection
            $table->enum('subject_role', ['major', 'minor', 'compulsory', 'optional']);

            // Was it auto-included (compulsory) or manually chosen?
            $table->boolean('is_auto_included')->default(false)
                  ->comment('true=compulsory tha, auto add hua | false=student ne choose kiya');

            $table->timestamps();

            // Ek student ek session mein ek subject sirf ek baar
            $table->unique(
                ['student_id', 'subject_id', 'academic_session_id', 'year_number'],
                'ss_student_subject_session_unique'
            );

            // Performance indexes
            $table->index(['student_id', 'academic_session_id'], 'ss_student_session_idx');
            $table->index(['student_id', 'year_number'], 'ss_student_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
        Schema::dropIfExists('course_stream_subjects');
    }
};