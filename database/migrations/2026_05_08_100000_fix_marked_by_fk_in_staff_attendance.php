<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->dropForeign('staff_attendance_marked_by_foreign');
            $table->foreign('marked_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('attendance_lock_records', function (Blueprint $table) {
            $table->dropForeign('attendance_lock_records_locked_by_foreign');
            $table->foreign('locked_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->dropForeign(['marked_by']);
            $table->foreign('marked_by')->references('id')->on('staff_members')->nullOnDelete();
        });

        Schema::table('attendance_lock_records', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->foreign('locked_by')->references('id')->on('staff_members')->nullOnDelete();
        });
    }
};
