<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'exam_form_no')) {
                $table->string('exam_form_no', 50)->nullable()->after('roll_no');
            }
            if (!Schema::hasColumn('students', 'uin_no')) {
                $table->string('uin_no', 50)->nullable()->after('exam_form_no');
            }
            if (!Schema::hasColumn('students', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('uin_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['exam_form_no', 'uin_no', 'reference_no']);
        });
    }
};
