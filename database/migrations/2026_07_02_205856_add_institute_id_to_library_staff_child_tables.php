<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── library_staff_permissions ──────────────────────────────────
        Schema::table('library_staff_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable()->after('id');
        });

        DB::statement('
            UPDATE library_staff_permissions lsp
            JOIN library_staff ls ON ls.id = lsp.library_staff_id
            SET lsp.institute_id = ls.institute_id
        ');

        Schema::table('library_staff_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable(false)->change();
            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->index('institute_id');
        });

        // ── library_login_logs ─────────────────────────────────────────
        Schema::table('library_login_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable()->after('id');
        });

        DB::statement('
            UPDATE library_login_logs lll
            JOIN library_staff ls ON ls.id = lll.library_staff_id
            SET lll.institute_id = ls.institute_id
        ');

        Schema::table('library_login_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable(false)->change();
            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->index('institute_id');
        });

        // ── library_staff_activity_logs ────────────────────────────────
        Schema::table('library_staff_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable()->after('id');
        });

        DB::statement('
            UPDATE library_staff_activity_logs lsal
            JOIN library_staff ls ON ls.id = lsal.library_staff_id
            SET lsal.institute_id = ls.institute_id
        ');

        Schema::table('library_staff_activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable(false)->change();
            $table->foreign('institute_id')->references('id')->on('institutes')->cascadeOnDelete();
            $table->index(['institute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('library_staff_activity_logs', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
            $table->dropIndex(['institute_id', 'created_at']);
            $table->dropColumn('institute_id');
        });

        Schema::table('library_login_logs', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
            $table->dropIndex(['institute_id']);
            $table->dropColumn('institute_id');
        });

        Schema::table('library_staff_permissions', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
            $table->dropIndex(['institute_id']);
            $table->dropColumn('institute_id');
        });
    }
};
