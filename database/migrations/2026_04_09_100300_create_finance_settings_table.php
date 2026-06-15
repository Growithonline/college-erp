<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fees_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('student_advance_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('discount_allowed_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('fine_income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('rounding_adjustment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();

            $table->unique('institute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
    }
};
