<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-session token for library staff
        if (!Schema::hasColumn('library_staff', 'session_token')) {
            Schema::table('library_staff', function (Blueprint $table) {
                $table->string('session_token', 64)->nullable()->after('last_login_ip');
            });
        }

        // Widen status to varchar so we can add new values (ip_change, etc.)
        if (Schema::hasTable('library_login_logs')) {
            DB::statement("ALTER TABLE library_login_logs MODIFY COLUMN status VARCHAR(30) NOT NULL");
        }

        // Activity log — tracks library operations per staff member
        if (Schema::hasTable('library_staff_activity_logs')) {
            return;
        }
        Schema::create('library_staff_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_staff_id')->constrained()->cascadeOnDelete();
            $table->string('action', 50);        // login, logout, profile_update, ip_change, etc.
            $table->string('subject')->nullable(); // e.g. "Book: ISBN 978-xxx"
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['library_staff_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_staff_activity_logs');

        if (Schema::hasTable('library_login_logs')) {
            DB::statement("ALTER TABLE library_login_logs MODIFY COLUMN status ENUM('success','failed_otp','locked') NOT NULL");
        }

        Schema::table('library_staff', function (Blueprint $table) {
            $table->dropColumn('session_token');
        });
    }
};
