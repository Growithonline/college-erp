<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments
        Schema::create('employee_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->string('name', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->index('institute_id');
        });

        // Designations
        Schema::create('employee_designations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_department_id')->nullable();
            $table->string('name', 100);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_department_id')->references('id')->on('employee_departments')->onDelete('set null');
            $table->index('institute_id');
        });

        // Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_department_id')->nullable();
            $table->unsignedBigInteger('employee_designation_id')->nullable();

            // Personal
            $table->string('employee_code', 30)->nullable();
            $table->string('name', 120);
            $table->string('father_name', 120)->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('alternate_phone', 15)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('photo')->nullable();

            // Employment
            $table->date('joining_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contractual', 'daily_wage'])->default('full_time');
            $table->enum('salary_type', ['monthly', 'daily_wage'])->default('monthly');
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'terminated', 'resigned'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_department_id')->references('id')->on('employee_departments')->onDelete('set null');
            $table->foreign('employee_designation_id')->references('id')->on('employee_designations')->onDelete('set null');
            $table->index('institute_id');
        });

        // Documents
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('document_type', 50); // aadhaar, pan, driving_license, voter_id, other
            $table->string('document_number', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['institute_id', 'expiry_date']);
        });

        // Salary components (allowances etc.) — history via effective dates
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('component_type', ['hra', 'conveyance', 'medical', 'special', 'other']);
            $table->string('label', 80)->nullable(); // custom label for 'other'
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index('employee_id');
        });

        // Monthly salary disbursements
        Schema::create('employee_salary_disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_id');
            $table->tinyInteger('month'); // 1-12
            $table->smallInteger('year');
            $table->decimal('basic_paid', 10, 2)->default(0);
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode', 30)->nullable(); // cash, bank, cheque
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->string('remarks', 300)->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['employee_id', 'month', 'year'], 'unique_emp_month_year');
            $table->index(['institute_id', 'year', 'month']);
        });

        // Bonuses (Diwali, Holi, etc.)
        Schema::create('employee_bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('bonus_type', 50); // diwali, holi, eid, annual, adhoc
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_mode', 30)->nullable();
            $table->string('remarks', 300)->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['institute_id', 'payment_date']);
        });

        // Salary advances
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 10, 2);
            $table->date('given_date');
            $table->decimal('recovery_per_month', 10, 2)->default(0);
            $table->decimal('recovered_amount', 10, 2)->default(0);
            $table->enum('status', ['active', 'recovered'])->default('active');
            $table->string('remarks', 300)->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index('institute_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_bonuses');
        Schema::dropIfExists('employee_salary_disbursements');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('employee_designations');
        Schema::dropIfExists('employee_departments');
    }
};
