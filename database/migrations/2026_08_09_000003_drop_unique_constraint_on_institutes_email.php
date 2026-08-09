<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * institutes.email is a general contact field, not used for login or scoped by
     * anything (Institute is the top-level tenant) — dropped entirely rather than
     * scoped, matching centers.email/students.email which already have no
     * uniqueness constraint anywhere in this codebase.
     */
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
