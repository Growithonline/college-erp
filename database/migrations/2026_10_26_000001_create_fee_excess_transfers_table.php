








<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A student's cash on one fee item that exceeds what the item now costs ("excess", e.g.
        // after the fee rule was lowered) re-allocated onto another fee item of the SAME session
        // that is still pending. No money moves — wallet and journal are untouched — only which
        // fee the cash counts against. In the live session the effective amount follows the
        // source's remaining excess (see WalletService::applyExcessTransfers()), so raising the
        // source fee again undoes the transfer by itself.
        Schema::create('fee_excess_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->string('from_label');
            $table->string('to_label');
            $table->decimal('amount', 10, 2);
            $table->string('status', 12)->default('active'); // active | reverted
            $table->string('created_by_type', 20)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'academic_session_id', 'status'], 'fee_excess_transfers_student_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_excess_transfers');
    }
};
