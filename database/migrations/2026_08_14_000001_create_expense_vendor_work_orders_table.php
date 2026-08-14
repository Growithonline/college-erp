<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_vendor_work_orders')) {
            return;
        }
        Schema::create('expense_vendor_work_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('expense_vendor_id');
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('total_budget', 14, 2)->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('expense_vendor_id')->references('id')->on('expense_vendors')->onDelete('restrict');
            $table->index(['institute_id', 'expense_vendor_id']);
            $table->index(['institute_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_vendor_work_orders');
    }
};
