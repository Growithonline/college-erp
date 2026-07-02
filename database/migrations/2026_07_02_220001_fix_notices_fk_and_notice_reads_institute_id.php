<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. notices table: institute_id FK missing ────────────────────
        // Column hai lekin FK constraint nahi tha — add karo
        Schema::table('notices', function (Blueprint $table) {
            $table->foreign('institute_id')
                  ->references('id')->on('institutes')
                  ->cascadeOnDelete();
        });

        // ── 2. notice_reads: institute_id column add karo ────────────────
        // notice_reads sirf notice_id FK hai; direct institute_id nahi tha
        if (!Schema::hasColumn('notice_reads', 'institute_id')) {
            Schema::table('notice_reads', function (Blueprint $table) {
                $table->unsignedBigInteger('institute_id')->nullable()->after('id');
            });

            // Backfill: notices JOIN se institute_id lo
            DB::statement('
                UPDATE notice_reads nr
                JOIN notices n ON n.id = nr.notice_id
                SET nr.institute_id = n.institute_id
            ');

            // Orphaned notice_reads (notice delete ho chuki) — safe delete
            DB::table('notice_reads')->whereNull('institute_id')->delete();

            Schema::table('notice_reads', function (Blueprint $table) {
                $table->unsignedBigInteger('institute_id')->nullable(false)->change();
                $table->foreign('institute_id')
                      ->references('id')->on('institutes')
                      ->cascadeOnDelete();
                $table->index(['institute_id', 'reader_type', 'reader_id'], 'nr_institute_reader_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notice_reads', 'institute_id')) {
            Schema::table('notice_reads', function (Blueprint $table) {
                $table->dropIndex('nr_institute_reader_idx');
                $table->dropForeign(['institute_id']);
                $table->dropColumn('institute_id');
            });
        }

        Schema::table('notices', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
        });
    }
};
