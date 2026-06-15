<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transport_maintenance_logs')) return;

        Schema::create('transport_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_vehicle_id')->constrained('transport_vehicles')->cascadeOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->date('next_service_due')->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->string('service_type', 80)->nullable();
            $table->string('garage_name', 120)->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('status', 30)->default('completed');
            $table->text('issues_found')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('by_user_id')->nullable();
            $table->timestamps();

            $table->index(['transport_vehicle_id', 'service_date'], 'tml_vehicle_service_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_maintenance_logs');
    }
};
