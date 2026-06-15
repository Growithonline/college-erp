<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->nullable()->after('amount');
        });

        // FeeTypes mein hierarchy_order column add karo
        Schema::table('fee_types', function (Blueprint $table) {
            $table->integer('hierarchy_order')->default(99)->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
        Schema::table('fee_types', function (Blueprint $table) {
            $table->dropColumn('hierarchy_order');
        });
    }
};