<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institute_transport_settings', function (Blueprint $table) {
            $table->dropColumn('prorated_billing');
        });
    }

    public function down(): void
    {
        Schema::table('institute_transport_settings', function (Blueprint $table) {
            $table->enum('prorated_billing', ['disabled', 'after_midmonth', 'daily_basis'])
                  ->default('disabled')
                  ->after('on_route_transfer');
        });
    }
};
