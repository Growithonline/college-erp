<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'ipa_institute_doc_version_unique';

    // The original create migration used an auto-generated index name over MySQL's
    // 64-char limit, so the table could exist without its unique index.
    public function up(): void
    {
        if (!Schema::hasTable('institute_policy_acceptances') || Schema::hasIndex('institute_policy_acceptances', self::INDEX)) {
            return;
        }

        Schema::table('institute_policy_acceptances', function (Blueprint $table) {
            $table->unique(['institute_id', 'document_type', 'version'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('institute_policy_acceptances') && Schema::hasIndex('institute_policy_acceptances', self::INDEX)) {
            Schema::table('institute_policy_acceptances', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }
};
