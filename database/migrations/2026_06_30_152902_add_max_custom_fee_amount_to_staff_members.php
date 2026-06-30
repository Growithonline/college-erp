<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            // Null = no limit (default, preserves today's behaviour for existing staff).
            $table->decimal('max_custom_fee_amount', 10, 2)->nullable()->after('max_discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('max_custom_fee_amount');
        });
    }
};
