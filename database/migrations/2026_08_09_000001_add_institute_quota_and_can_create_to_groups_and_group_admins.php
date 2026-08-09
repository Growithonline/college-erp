<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // null = unlimited institutes for this group
            $table->unsignedInteger('institute_quota')->nullable()->after('status');
            // null = Group-Admin institute creation stays blocked until Super-Admin sets this
            $table->unsignedInteger('per_institute_student_limit')->nullable()->after('institute_quota');
        });

        Schema::table('group_admins', function (Blueprint $table) {
            $table->boolean('can_create_institutes')->default(false)->after('can_reset_institute_password');
        });
    }

    public function down(): void
    {
        Schema::table('group_admins', function (Blueprint $table) {
            $table->dropColumn('can_create_institutes');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['institute_quota', 'per_institute_student_limit']);
        });
    }
};
