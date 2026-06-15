<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('center_wallet_transactions')) {
            return;
        }
        Schema::create('center_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->foreignId('fee_invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_wallet_transactions');
    }
};
