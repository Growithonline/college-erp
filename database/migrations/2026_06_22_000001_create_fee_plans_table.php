<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            // null = applies to all courses; set = course-specific plan
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('name');                        // "2 Installments (50-50)", "Full Payment"
            $table->tinyInteger('installment_count');       // 1, 2, 3 ...
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_plan_id')->constrained()->onDelete('cascade');

            $table->tinyInteger('installment_number');     // 1, 2, 3
            $table->string('label');                       // "At Admission", "2nd Installment"
            $table->decimal('percentage', 5, 2);           // 50.00, 33.33 etc

            // When is this installment due?
            $table->enum('due_trigger', [
                'at_admission',   // immediately at admission/1st payment
                'semester_start', // at start of specific semester
                'months_after',   // N months after admission
            ])->default('at_admission');

            $table->tinyInteger('due_semester')->nullable();   // for semester_start trigger
            $table->tinyInteger('due_months_after')->nullable(); // for months_after trigger

            $table->timestamps();

            $table->unique(['fee_plan_id', 'installment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_plan_installments');
        Schema::dropIfExists('fee_plans');
    }
};
