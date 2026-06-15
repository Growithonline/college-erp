<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('transport_monthly_charges')) {
            return;
        }

        Schema::create('transport_monthly_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_allocation_id')->constrained('transport_allocations')->cascadeOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('charge_month', 7);   // YYYY-MM
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->unique(['transport_allocation_id', 'charge_month'], 'tmc_allocation_month_unique');
            $table->index(['institute_id', 'charge_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_monthly_charges');
    }
};
