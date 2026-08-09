<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Institute-owner login already resolves the tenant via institute_uid before
     * scoping the User lookup by institute_id+email, so no login-controller change
     * is needed here — only the DB constraint needs narrowing. Safe: the current
     * global-unique constraint already guarantees no duplicate emails exist today,
     * so narrowing to a composite constraint can never conflict with existing data.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['institute_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['institute_id', 'email']);
            $table->unique('email');
        });
    }
};
