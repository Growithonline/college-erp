<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('status');
            $table->unsignedSmallInteger('smtp_port')->default(587)->after('smtp_host');
            $table->string('smtp_encryption')->default('tls')->after('smtp_port'); // tls / ssl / none
            $table->string('smtp_username')->nullable()->after('smtp_encryption');
            $table->text('smtp_password')->nullable()->after('smtp_username');     // stored encrypted
            $table->string('smtp_from_name')->nullable()->after('smtp_password');
            $table->string('smtp_from_email')->nullable()->after('smtp_from_name');
            $table->boolean('smtp_verified')->default(false)->after('smtp_from_email');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host', 'smtp_port', 'smtp_encryption',
                'smtp_username', 'smtp_password',
                'smtp_from_name', 'smtp_from_email', 'smtp_verified',
            ]);
        });
    }
};
