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
