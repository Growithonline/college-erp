<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_vendor_work_order_transactions')) {
            return;
        }
        Schema::create('expense_vendor_work_order_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_vendor_work_order_id')->constrained('expense_vendor_work_orders')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_vendor_work_order_transactions');
    }
};
