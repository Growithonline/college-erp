<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_active');
            $table->string('attachment', 255)->nullable()->after('is_pinned');
            $table->timestamp('scheduled_at')->nullable()->after('attachment');
            // null = email nahi bhejna, value = comma-separated roles (staff,center,channel)
            $table->string('email_to', 100)->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'attachment', 'scheduled_at', 'email_to']);
        });
    }
};
