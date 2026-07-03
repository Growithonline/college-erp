<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend status enum to include 'reversed'
        DB::statement("ALTER TABLE employee_salary_disbursements MODIFY COLUMN status ENUM('pending','paid','reversed') NOT NULL DEFAULT 'pending'");

        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('components_snapshot');
            $table->string('reversal_reason', 300)->nullable()->after('reversed_at');
            $table->unsignedBigInteger('reversal_journal_entry_id')->nullable()->after('reversal_reason');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary_disbursements', function (Blueprint $table) {
            $table->dropColumn(['reversed_at', 'reversal_reason', 'reversal_journal_entry_id']);
        });

        DB::statement("ALTER TABLE employee_salary_disbursements MODIFY COLUMN status ENUM('pending','paid') NOT NULL DEFAULT 'pending'");
    }
};
