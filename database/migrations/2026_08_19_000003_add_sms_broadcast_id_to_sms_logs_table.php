<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->foreignId('sms_broadcast_id')->nullable()->after('institute_id')
                ->constrained('sms_broadcasts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sms_broadcast_id');
        });
    }
};
