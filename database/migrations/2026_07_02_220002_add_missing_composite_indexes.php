<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // channel_partners — status filter queries
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'cp_institute_status_idx');
        });

        // staff_members — status filter queries (e.g. active staff list)
        Schema::table('staff_members', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'sm_institute_status_idx');
        });

        // library_staff — status filter queries
        Schema::table('library_staff', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'ls_institute_status_idx');
        });

        // transport_drivers — status filter queries
        Schema::table('transport_drivers', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'td_institute_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transport_drivers', function (Blueprint $table) {
            $table->dropIndex('td_institute_status_idx');
        });

        Schema::table('library_staff', function (Blueprint $table) {
            $table->dropIndex('ls_institute_status_idx');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropIndex('sm_institute_status_idx');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropIndex('cp_institute_status_idx');
        });
    }
};
