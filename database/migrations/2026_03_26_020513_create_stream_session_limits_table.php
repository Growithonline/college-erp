<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Table already exist karti hai toh skip karo
        if (!Schema::hasTable('stream_session_limits')) {
            Schema::create('stream_session_limits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_stream_id')->constrained('course_streams')->onDelete('cascade');
                $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');
                $table->unsignedSmallInteger('student_limit');
                $table->timestamps();
                $table->unique(['course_stream_id', 'academic_session_id']);
            });
        }

        // course_streams se static limit remove karo
        Schema::table('course_streams', function (Blueprint $table) {
            if (Schema::hasColumn('course_streams', 'student_limit')) {
                $table->dropColumn('student_limit');
            }
        });
    }
    public function down(): void {
        Schema::dropIfExists('stream_session_limits');
        Schema::table('course_streams', function (Blueprint $table) {
            $table->unsignedSmallInteger('student_limit')->nullable()->after('code');
        });
    }
};