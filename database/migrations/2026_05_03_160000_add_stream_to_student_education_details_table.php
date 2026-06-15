<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_education_details', function (Blueprint $table) {
            if (!Schema::hasColumn('student_education_details', 'education_stream')) {
                $table->string('education_stream', 50)->nullable()->after('exam_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_education_details', function (Blueprint $table) {
            if (Schema::hasColumn('student_education_details', 'education_stream')) {
                $table->dropColumn('education_stream');
            }
        });
    }
};
