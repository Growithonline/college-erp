<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institute_transactions', function (Blueprint $table) {
            // fee_invoice, library_fine, manual_income
            $table->string('source_type')->nullable()->after('fee_invoice_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['institute_id', 'source_type']);
        });

        // Backfill existing fee_invoice transactions
        \DB::statement("
            UPDATE institute_transactions
            SET source_type = 'fee_invoice', source_id = fee_invoice_id
            WHERE fee_invoice_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('institute_transactions', function (Blueprint $table) {
            $table->dropIndex(['institute_id', 'source_type']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
