<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_vendor_work_order_id')->nullable()->after('expense_vendor_id');
            $table->foreign('expense_vendor_work_order_id')->references('id')->on('expense_vendor_work_orders')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_vendor_work_order_id']);
            $table->dropColumn('expense_vendor_work_order_id');
        });
    }
};
