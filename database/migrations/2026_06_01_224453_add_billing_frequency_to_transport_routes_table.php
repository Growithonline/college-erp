<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transport_routes', function (Blueprint $table) {
            $table->enum('billing_frequency', ['one_time', 'monthly', 'quarterly', 'semester'])
                ->default('one_time')->after('fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transport_routes', function (Blueprint $table) {
            $table->dropColumn('billing_frequency');
        });
    }
};
