<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('password')->nullable()->after('status');
            $table->boolean('portal_enabled')->default(true)->after('password');
            $table->boolean('first_login')->default(true)->after('portal_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['password', 'portal_enabled', 'first_login']);
        });
    }
};
