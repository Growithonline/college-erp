<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('student_visibility_scope', 20)
                ->default('role_based')
                ->after('restrict_fee_collection_types');
            $table->boolean('restrict_session_access')
                ->default(false)
                ->after('student_visibility_scope');
            $table->json('allowed_session_ids')
                ->nullable()
                ->after('restrict_session_access');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn([
                'student_visibility_scope',
                'restrict_session_access',
                'allowed_session_ids',
            ]);
        });
    }
};
