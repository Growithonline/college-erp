<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Centers
        Schema::table('centers', function (Blueprint $table) {
            $table->enum('doc_full_form_upload',  ['skip', 'optional', 'required'])->default('skip')->after('admission_form_type');
            $table->enum('doc_quick_form_upload', ['skip', 'optional', 'required'])->default('skip')->after('doc_full_form_upload');
        });

        // Channel Partners
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->enum('doc_full_form_upload',  ['skip', 'optional', 'required'])->default('skip')->after('admission_form_type');
            $table->enum('doc_quick_form_upload', ['skip', 'optional', 'required'])->default('skip')->after('doc_full_form_upload');
        });

        // Staff Members
        Schema::table('staff_members', function (Blueprint $table) {
            $table->enum('doc_full_form_upload',  ['skip', 'optional', 'required'])->default('skip')->after('allowed_admission_forms');
            $table->enum('doc_quick_form_upload', ['skip', 'optional', 'required'])->default('skip')->after('doc_full_form_upload');
        });
    }

    public function down(): void
    {
        foreach (['centers', 'channel_partners', 'staff_members'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['doc_full_form_upload', 'doc_quick_form_upload']);
            });
        }
    }
};
