<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lets an institute require a DIFFERENT set of documents (e.g. Diploma Marksheet,
// Migration Certificate) specifically for Lateral Entry admissions, on top of the
// existing per-course + user_type rules. Existing rows default to 'all', so every
// current document rule keeps applying to every admission exactly as before.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->string('admission_type', 20)->default('all')->after('user_type');
        });

        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->dropUnique('doc_rule_unique');
        });

        Schema::table('document_upload_rules', function (Blueprint $table) {
            $table->unique(
                ['course_id', 'document_type_id', 'user_type', 'admission_type'],
                'doc_rule_unique'
            );
        });
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
