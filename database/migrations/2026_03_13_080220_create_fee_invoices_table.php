<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->onDelete('cascade');
 
            $table->string('invoice_no')->unique(); // BBA/FEE/2026/00001
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2);
            $table->enum('payment_mode', ['cash', 'online', 'cheque', 'dd', 'upi'])->default('cash');
            $table->string('transaction_ref')->nullable(); // Cheque/DD/UTR number
            $table->string('bank_name')->nullable();
            $table->date('payment_date');
            $table->string('remarks')->nullable();
            $table->string('collected_by')->nullable(); // staff name
            $table->timestamps();
        });
 
        // Fee invoice ke items (kaunsa fee type, kitna amount)
        Schema::create('fee_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained()->onDelete('cascade');
            $table->string('fee_name'); // snapshot of fee type name
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('fee_invoice_items');
        Schema::dropIfExists('fee_invoices');
    }
};