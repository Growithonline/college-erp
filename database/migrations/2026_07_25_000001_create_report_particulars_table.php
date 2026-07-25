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
        Schema::create('report_particulars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');

            $table->enum('section', ['income', 'expense']);
            $table->enum('source_type', ['fee_invoice', 'manual_income', 'library_fine', 'expense', 'salary']);

            // fee_invoice + flat FeeType row (e.g. "TC Fee") — no course split
            $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->nullOnDelete();

            // fee_invoice + course-wise row (e.g. "B.A. 1st Year") — sub-columns resolved
            // at query time via course_parts.course_id + year_number
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->unsignedTinyInteger('year_number')->nullable();

            // manual_income row
            $table->foreignId('income_category_id')->nullable()->constrained('institute_income_categories')->nullOnDelete();

            // expense row
            $table->foreignId('expense_category_l1_id')->nullable()->constrained('expense_categories_l1')->nullOnDelete();

            // salary row — which of SalaryRecord (teaching) / EmployeeSalaryDisbursement (non-teaching) to include
            $table->enum('salary_scope', ['both', 'teaching', 'non_teaching'])->nullable();

            $table->string('name', 150);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['institute_id', 'section', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_particulars');
    }
};
