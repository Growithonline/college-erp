<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Pehle student_id pe alag index banao taaki FK constraint survive kare
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->index('student_id', 'idx_student_id_temp');
        });

        // Step 2: Ab purana unique constraint safe drop ho sakta hai
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropUnique('uniq_student_session');
        });

        // Step 3: Naya unique constraint — student + session + source + semester
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'academic_session_id', 'source', 'semester_at_time'],
                'uniq_student_session_source_sem'
            );
        });
    }

    public function down(): void
    {
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropUnique('uniq_student_session_source_sem');
        });
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_session_id'], 'uniq_student_session');
        });
        // temp index remove karo agar exist kare
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropIndex('idx_student_id_temp');
        });
    }
};