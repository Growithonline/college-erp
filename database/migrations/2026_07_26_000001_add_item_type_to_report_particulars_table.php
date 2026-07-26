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
        Schema::table('report_particulars', function (Blueprint $table) {
            // Matches FeeInvoiceItem.item_type — needed because some invoice items
            // (transport, practical) are created without a fee_type_id at all.
            $table->string('item_type', 30)->nullable()->after('fee_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_particulars', function (Blueprint $table) {
            $table->dropColumn('item_type');
        });
    }
};
