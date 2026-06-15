<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            // Allowance breakdown
            $table->decimal('hra', 10, 2)->default(0)->after('allowances');
            $table->decimal('da', 10, 2)->default(0)->after('hra');
            $table->decimal('ta', 10, 2)->default(0)->after('da');
            $table->decimal('medical', 10, 2)->default(0)->after('ta');
            $table->decimal('overtime_amount', 10, 2)->default(0)->after('medical');

            // Statutory deduction breakdown
            $table->decimal('pf_employee', 10, 2)->default(0)->after('deductions');
            $table->decimal('pf_employer', 10, 2)->default(0)->after('pf_employee');
            $table->decimal('esi_employee', 10, 2)->default(0)->after('pf_employer');
            $table->decimal('esi_employer', 10, 2)->default(0)->after('esi_employee');
            $table->decimal('tds', 10, 2)->default(0)->after('esi_employer');
            $table->decimal('professional_tax', 10, 2)->default(0)->after('tds');
            $table->decimal('loan_deduction', 10, 2)->default(0)->after('professional_tax');
            $table->decimal('absence_deduction', 10, 2)->default(0)->after('loan_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropColumn([
                'hra', 'da', 'ta', 'medical', 'overtime_amount',
                'pf_employee', 'pf_employer', 'esi_employee', 'esi_employer',
                'tds', 'professional_tax', 'loan_deduction', 'absence_deduction',
            ]);
        });
    }
};
