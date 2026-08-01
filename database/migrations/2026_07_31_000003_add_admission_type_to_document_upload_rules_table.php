<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Lets an institute require a DIFFERENT set of documents (e.g. Diploma Marksheet,
// Migration Certificate) specifically for Lateral Entry admissions, on top of the
// existing per-course + user_type rules. Existing rows default to 'all', so every
// current document rule keeps applying to every admission exactly as before.
//
// Every step is guarded — see the 2026_07_31_000001 migration's comment for why
// (MySQL DDL isn't transactional, so a retry after a partial failure must be safe).
//
// course_id has no dedicated single-column index of its own — it only has one because
// it's the leftmost column of doc_rule_unique. Dropping that unique index directly
// fails with error 1553 ("needed in a foreign key constraint") because InnoDB would be
// left with no index at all covering the course_id foreign key. Giving course_id its
// own dedicated index first avoids that (see 2026_07_31_000001 for the same fix).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_upload_rules', 'admission_type')) {
            Schema::table('document_upload_rules', function (Blueprint $table) {
                $table->string('admission_type', 20)->default('all')->after('user_type');
            });
        }

        $indexes = collect(DB::select("SHOW INDEX FROM document_upload_rules WHERE Key_name = 'doc_rule_unique'"))
            ->pluck('Column_name')
            ->all();

        if (!in_array('admission_type', $indexes, true)) {
            $hasCourseIndex = collect(DB::select(
                "SHOW INDEX FROM document_upload_rules WHERE Key_name = 'document_upload_rules_course_id_index'"
            ))->isNotEmpty();

            if (!$hasCourseIndex) {
                Schema::table('document_upload_rules', function (Blueprint $table) {
                    $table->index('course_id', 'document_upload_rules_course_id_index');
                });
            }

            if (!empty($indexes)) {
                Schema::table('document_upload_rules', function (Blueprint $table) {
                    $table->dropUnique('doc_rule_unique');
                });
            }

            Schema::table('document_upload_rules', function (Blueprint $table) {
                $table->unique(
                    ['course_id', 'document_type_id', 'user_type', 'admission_type'],
                    'doc_rule_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->dropUnique('doc_rule_unique');
        });

        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->unique(['course_id', 'document_type_id', 'user_type'], 'doc_rule_unique');
        });

        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->dropColumn('admission_type');
        });
    }
};
