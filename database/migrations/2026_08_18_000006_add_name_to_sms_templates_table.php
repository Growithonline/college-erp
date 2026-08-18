<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent — MySQL DDL auto-commits per statement, so a prior failed run of this
        // same migration can leave partial changes in place even though it isn't recorded as
        // run. Guard every step so re-running after a failure is safe.
        if (! Schema::hasColumn('sms_templates', 'name')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->string('name')->nullable()->after('type');
            });
        }

        $indexNames = collect(Schema::getIndexes('sms_templates'))->pluck('name');

        // The new composite index MUST be added before the old one is dropped: institute_id
        // is the leftmost column of both, and the old (institute_id, type) unique index is
        // also serving as the required supporting index for the institute_id foreign key —
        // MySQL refuses ("error 1553") to drop it while no replacement index covers that FK.
        if (! $indexNames->contains('sms_templates_institute_id_type_name_unique')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->unique(['institute_id', 'type', 'name'], 'sms_templates_institute_id_type_name_unique');
            });
        }

        if ($indexNames->contains('sms_templates_institute_id_type_unique')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropUnique('sms_templates_institute_id_type_unique');
            });
        }
    }

    public function down(): void
    {
        $indexNames = collect(Schema::getIndexes('sms_templates'))->pluck('name');

        if (! $indexNames->contains('sms_templates_institute_id_type_unique')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->unique(['institute_id', 'type'], 'sms_templates_institute_id_type_unique');
            });
        }

        if ($indexNames->contains('sms_templates_institute_id_type_name_unique')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropUnique('sms_templates_institute_id_type_name_unique');
            });
        }

        if (Schema::hasColumn('sms_templates', 'name')) {
            Schema::table('sms_templates', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
