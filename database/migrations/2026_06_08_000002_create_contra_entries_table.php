<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contra_entries')) {
            return;
        }
        Schema::create('contra_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->date('entry_date');
            $table->decimal('amount', 12, 2);
            $table->foreignId('to_bank_account_id')->constrained('institute_bank_accounts')->cascadeOnDelete();
            $table->string('slip_no', 80)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contra_entries');
    }
};
