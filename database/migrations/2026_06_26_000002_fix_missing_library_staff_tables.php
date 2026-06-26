<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── library_staff_permissions ──────────────────────────────────
        if (!Schema::hasTable('library_staff_permissions')) {
            Schema::create('library_staff_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('library_staff_id')->unique()->constrained('library_staff')->cascadeOnDelete();
                $table->string('preset', 30)->nullable();
                $table->json('permissions');
                $table->timestamps();
            });
        }

        // ── library_login_logs ─────────────────────────────────────────
        if (!Schema::hasTable('library_login_logs')) {
            Schema::create('library_login_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('library_staff_id')->constrained('library_staff')->cascadeOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 300)->nullable();
                $table->string('status', 30)->default('failed_otp');
                $table->timestamp('created_at')->useCurrent();
            });
        } else {
            // Ensure status column is VARCHAR(30) not ENUM (in case it was created as ENUM)
            DB::statement("ALTER TABLE library_login_logs MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'failed_otp'");
        }

        // ── library_staff_activity_logs ────────────────────────────────
        if (!Schema::hasTable('library_staff_activity_logs')) {
            Schema::create('library_staff_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('library_staff_id')->constrained('library_staff')->cascadeOnDelete();
                $table->string('action', 50);
                $table->string('subject')->nullable();
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['library_staff_id', 'created_at']);
            });
        }

        // ── session_token column ───────────────────────────────────────
        if (!Schema::hasColumn('library_staff', 'session_token')) {
            Schema::table('library_staff', function (Blueprint $table) {
                $table->string('session_token', 64)->nullable()->after('last_login_ip');
            });
        }

        // ── password column ────────────────────────────────────────────
        if (!Schema::hasColumn('library_staff', 'password')) {
            Schema::table('library_staff', function (Blueprint $table) {
                $table->string('password')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('library_staff_activity_logs');
        Schema::dropIfExists('library_login_logs');
        Schema::dropIfExists('library_staff_permissions');

        if (Schema::hasColumn('library_staff', 'session_token')) {
            Schema::table('library_staff', function (Blueprint $table) {
                $table->dropColumn('session_token');
            });
        }

        if (Schema::hasColumn('library_staff', 'password')) {
            Schema::table('library_staff', function (Blueprint $table) {
                $table->dropColumn('password');
            });
        }
    }
};
