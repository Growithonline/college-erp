<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE fee_invoices MODIFY payment_mode ENUM('cash', 'online', 'cheque', 'dd', 'upi', 'neft', 'rtgs') NOT NULL DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fee_invoices MODIFY payment_mode ENUM('cash', 'online', 'cheque', 'dd', 'upi') NOT NULL DEFAULT 'cash'");
    }
};
