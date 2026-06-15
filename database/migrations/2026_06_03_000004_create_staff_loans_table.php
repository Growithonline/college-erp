<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_loans')) {
            return;
        }
        Schema::create('staff_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->enum('loan_type', ['advance', 'loan'])->default('advance');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('outstanding_amount', 12, 2);
            $table->decimal('monthly_deduction', 10, 2);
            $table->unsignedTinyInteger('start_month');
            $table->unsignedSmallInteger('start_year');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('purpose', 255)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'staff_member_id', 'status'], 'staff_loans_staff_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_loans');
    }
};
