<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('hra_percent')->default(0)->after('monthly_salary');
            $table->unsignedTinyInteger('da_percent')->default(0)->after('hra_percent');
            $table->decimal('ta_amount', 10, 2)->default(0)->after('da_percent');
            $table->decimal('medical_amount', 10, 2)->default(0)->after('ta_amount');
            $table->boolean('pf_applicable')->default(false)->after('medical_amount');
            $table->decimal('tds_monthly', 10, 2)->default(0)->after('pf_applicable');
            $table->decimal('professional_tax_monthly', 10, 2)->default(0)->after('tds_monthly');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn([
                'hra_percent', 'da_percent', 'ta_amount', 'medical_amount',
                'pf_applicable', 'tds_monthly', 'professional_tax_monthly',
            ]);
        });
    }
};
