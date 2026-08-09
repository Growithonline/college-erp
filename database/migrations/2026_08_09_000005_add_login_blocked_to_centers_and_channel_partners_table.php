<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('login_blocked')->default(false)->after('otp_bypass');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->boolean('login_blocked')->default(false)->after('otp_bypass');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('login_blocked');
        });

        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropColumn('login_blocked');
        });
    }
};
