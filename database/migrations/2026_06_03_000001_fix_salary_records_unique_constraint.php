<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add plain index on staff_member_id so FK has a supporting index
        // after the old unique (staff_member_id, salary_month, salary_year) is dropped.
        if (!$this->indexExists('salary_records', 'salary_records_staff_member_id_index')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->index('staff_member_id', 'salary_records_staff_member_id_index');
            });
        }

        // Step 2: Drop the old unique constraint (now safe — FK has Step 1 index).
        if ($this->indexExists('salary_records', 'salary_records_staff_month_year_unique')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->dropUnique('salary_records_staff_month_year_unique');
            });
        }

        // Step 3: Add correct unique constraint scoped to institute.
        if (!$this->indexExists('salary_records', 'salary_records_institute_staff_month_year_unique')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->unique(
                    ['institute_id', 'staff_member_id', 'salary_month', 'salary_year'],
                    'salary_records_institute_staff_month_year_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('salary_records', 'salary_records_institute_staff_month_year_unique')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->dropUnique('salary_records_institute_staff_month_year_unique');
            });
        }

        if (!$this->indexExists('salary_records', 'salary_records_staff_month_year_unique')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->unique(
                    ['staff_member_id', 'salary_month', 'salary_year'],
                    'salary_records_staff_month_year_unique'
                );
            });
        }

        if ($this->indexExists('salary_records', 'salary_records_staff_member_id_index')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->dropIndex('salary_records_staff_member_id_index');
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
