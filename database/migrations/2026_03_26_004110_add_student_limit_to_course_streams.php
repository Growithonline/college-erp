<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('course_streams', function (Blueprint $table) {
            // Per-year limit — null = unlimited
            $table->unsignedSmallInteger('student_limit')->nullable()->after('code')
                  ->comment('Max students per year/session. NULL = unlimited.');
        });
    }
    public function down(): void {
        Schema::table('course_streams', function (Blueprint $table) {
            $table->dropColumn('student_limit');
        });
    }
};