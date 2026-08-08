<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Login is now by staff_uid/partner_uid/employee_id (not email), so email no
     * longer needs to be unique platform-wide — only within one institute. Safe:
     * the current global-unique constraint already guarantees no duplicates exist
     * today, so narrowing to a composite constraint can never conflict.
     */
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['institute_id', 'email']);
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['institute_id', 'email']);
        });

        Schema::table('library_staff', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['institute_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'email']);
            $table->unique('email');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'email']);
            $table->unique('email');
        });

        Schema::table('library_staff', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'email']);
            $table->unique('email');
        });
    }
};
