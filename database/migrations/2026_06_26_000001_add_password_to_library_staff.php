<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('library_staff', 'password')) {
            return;
        }
        Schema::table('library_staff', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('library_staff', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
