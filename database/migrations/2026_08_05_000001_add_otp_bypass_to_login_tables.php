<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('otp_bypass')->default(false)->after('status');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('otp_bypass')->default(false)->after('status');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->boolean('otp_bypass')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('otp_bypass');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('otp_bypass');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropColumn('otp_bypass');
        });
    }
};
