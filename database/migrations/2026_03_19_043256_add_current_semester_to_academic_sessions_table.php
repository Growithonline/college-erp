<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Bug #1 Fix: current_semester column add 
            $table->unsignedTinyInteger('current_semester')
                  ->nullable()
                  ->default(1)
                  ->after('is_active')
                  ->comment('Current running semester: 1 or 2');
        });
    }

    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropColumn('current_semester');
        });
    }
};