<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'reference_by')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('reference_by', 100)->nullable()->after('reference_no');
            });
        }

        if (!Schema::hasColumn('student_academic_identity', 'reference_by_snapshot')) {
            Schema::table('student_academic_identity', function (Blueprint $table) {
                $table->string('reference_by_snapshot')->nullable()->after('reference_no_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_academic_identity', 'reference_by_snapshot')) {
            Schema::table('student_academic_identity', function (Blueprint $table) {
                $table->dropColumn('reference_by_snapshot');
            });
        }

        if (Schema::hasColumn('students', 'reference_by')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('reference_by');
            });
        }
    }
};
