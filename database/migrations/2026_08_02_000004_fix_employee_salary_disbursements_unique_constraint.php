<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors 2026_06_03_000001_fix_salary_records_unique_constraint.php — that fix
 * (global unique -> institute-scoped unique) was never applied to this sibling table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add plain index on employee_id so the FK has a supporting index
        // after the old unique (employee_id, month, year) is dropped.
        if (!$this->indexExists('employee_salary_disbursements', 'esd_employee_id_index')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->index('employee_id', 'esd_employee_id_index');
            });
        }

        // Step 2: Drop the old unique constraint (now safe — FK has Step 1 index).
        if ($this->indexExists('employee_salary_disbursements', 'unique_emp_month_year')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->dropUnique('unique_emp_month_year');
            });
        }

        // Step 3: Add correct unique constraint scoped to institute.
        if (!$this->indexExists('employee_salary_disbursements', 'esd_institute_employee_month_year_unique')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->unique(
                    ['institute_id', 'employee_id', 'month', 'year'],
                    'esd_institute_employee_month_year_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('employee_salary_disbursements', 'esd_institute_employee_month_year_unique')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->dropUnique('esd_institute_employee_month_year_unique');
            });
        }

        if (!$this->indexExists('employee_salary_disbursements', 'unique_emp_month_year')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->unique(['employee_id', 'month', 'year'], 'unique_emp_month_year');
            });
        }

        if ($this->indexExists('employee_salary_disbursements', 'esd_employee_id_index')) {
            Schema::table('employee_salary_disbursements', function (Blueprint $table) {
                $table->dropIndex('esd_employee_id_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return !empty($result);
    }
};
