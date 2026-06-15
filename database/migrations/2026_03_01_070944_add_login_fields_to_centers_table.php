<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            if (!Schema::hasColumn('centers', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('centers', 'can_add_admission')) {
                $table->boolean('can_add_admission')->default(true)->after('can_collect_fee');
            }
            if (!Schema::hasColumn('centers', 'can_view_students')) {
                $table->boolean('can_view_students')->default(true)->after('can_add_admission');
            }
            if (!Schema::hasColumn('centers', 'remember_token')) {
                $table->rememberToken()->after('can_view_students');
            }
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['password', 'can_add_admission', 'can_view_students', 'remember_token']);
        });
    }
};