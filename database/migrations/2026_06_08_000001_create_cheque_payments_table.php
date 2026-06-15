<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cheque_payments')) {
            return;
        }
        Schema::create('cheque_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->foreignId('fee_invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->string('cheque_no', 50);
            $table->string('drawee_bank', 120)->nullable();
            $table->date('cheque_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'cleared', 'bounced'])->default('pending');
            $table->date('clearance_date')->nullable();
            $table->text('bounce_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'status']);
            $table->index('fee_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheque_payments');
    }
};
