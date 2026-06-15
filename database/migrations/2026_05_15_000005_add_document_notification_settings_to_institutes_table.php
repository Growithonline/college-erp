<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->boolean('doc_rejection_notify')->default(false)->after('status');
            $table->set('doc_rejection_channels', ['email', 'sms'])->nullable()->after('doc_rejection_notify');
            // 'per_document' = notify on each reject, 'final_only' = notify only when admission rejected
            $table->enum('doc_rejection_trigger', ['per_document', 'final_only'])->default('per_document')->after('doc_rejection_channels');
        });
    }

    public function down(): void
    {
        Schema::table('institutes', function (Blueprint $table) {
            $table->dropColumn(['doc_rejection_notify', 'doc_rejection_channels', 'doc_rejection_trigger']);
        });
    }
};
