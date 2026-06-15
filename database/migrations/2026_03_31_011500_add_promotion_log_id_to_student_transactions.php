<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_transactions', function (Blueprint $table) {
            $table->foreignId('promotion_log_id')
                ->nullable()
                ->after('fee_invoice_id')
                ->constrained('promotion_logs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_log_id');
        });
    }
};
