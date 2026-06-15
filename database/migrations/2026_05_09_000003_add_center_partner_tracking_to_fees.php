<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 1: Center + Partner FK columns on fee_invoices
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->foreignId('collected_by_center_id')
                ->nullable()->after('collected_by_staff_id')
                ->constrained('centers')->nullOnDelete();

            $table->foreignId('collected_by_partner_id')
                ->nullable()->after('collected_by_center_id')
                ->constrained('channel_partners')->nullOnDelete();
        });

        // Phase 2: Commission ledger per invoice
        Schema::create('partner_commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('channel_partners')->cascadeOnDelete();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->timestamps();

            $table->unique('fee_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_commission_entries');

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by_partner_id');
            $table->dropConstrainedForeignId('collected_by_center_id');
        });
    }
};
