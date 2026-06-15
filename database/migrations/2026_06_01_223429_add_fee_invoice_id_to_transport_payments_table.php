<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transport_payments', function (Blueprint $table) {
            $table->foreignId('fee_invoice_id')->nullable()->after('student_transaction_id')
                ->constrained('fee_invoices')->nullOnDelete();
            $table->boolean('is_reversed')->default(false)->after('fee_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('transport_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_invoice_id');
            $table->dropColumn('is_reversed');
        });
    }
};
