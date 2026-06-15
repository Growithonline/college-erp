<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('admitted_by_staff_id')->nullable()->after('is_quick_admission');
            $table->foreign('admitted_by_staff_id')
                  ->references('id')->on('staff_members')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['admitted_by_staff_id']);
            $table->dropColumn('admitted_by_staff_id');
        });
    }
};
