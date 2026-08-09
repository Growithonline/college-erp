<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('login_blocked')->default(false)->after('otp_bypass');
            $table->date('suspended_until')->nullable()->after('login_blocked');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('login_blocked')->default(false)->after('portal_enabled');
            $table->date('suspended_until')->nullable()->after('login_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn(['login_blocked', 'suspended_until']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['login_blocked', 'suspended_until']);
        });
    }
};
