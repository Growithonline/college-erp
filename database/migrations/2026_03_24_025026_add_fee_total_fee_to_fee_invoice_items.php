<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fee_invoice_items mein total_fee add karo
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->decimal('total_fee', 10, 2)->default(0)->nullable()->after('discount');
        });

        // fee_invoices mein cancel fields add karo (Phase 8)
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_invoices', 'bank_account_id')) {
                $table->unsignedBigInteger('bank_account_id')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('fee_invoices', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('remarks');
                $table->string('cancel_reason', 255)->nullable()->after('is_cancelled');
                $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->dropColumn('total_fee');
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['bank_account_id','is_cancelled','cancel_reason','cancelled_at','cancelled_by']);
        });
    }
};