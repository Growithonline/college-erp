<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Values verified against production data (DB::table(...)->distinct()->pluck(...))
        // plus every App\Models\Expense::STATUS_* / SalaryRecord::STATUS_* constant, so no
        // existing row and no code-driven transition falls outside the enum.
        DB::statement("ALTER TABLE expenses MODIFY COLUMN approval_status ENUM('auto_approved','pending','approved','rejected') NOT NULL DEFAULT 'auto_approved'");

        DB::statement("ALTER TABLE salary_records MODIFY COLUMN status ENUM('draft','pending','approved','paid','reversed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE expenses MODIFY COLUMN approval_status VARCHAR(255) NOT NULL DEFAULT 'auto_approved'");

        DB::statement("ALTER TABLE salary_records MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }
};
