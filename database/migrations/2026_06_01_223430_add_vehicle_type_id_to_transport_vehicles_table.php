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
        if (Schema::hasColumn('transport_vehicles', 'transport_vehicle_type_id')) {
            return;
        }

        Schema::table('transport_vehicles', function (Blueprint $table) {
            $table->foreignId('transport_vehicle_type_id')->nullable()->after('institute_id')
                ->constrained('transport_vehicle_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transport_vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transport_vehicle_type_id');
        });
    }
};
