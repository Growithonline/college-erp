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
        Schema::create('transport_route_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('transport_route_id');
            $table->unsignedBigInteger('transport_vehicle_id')->nullable();
            $table->unsignedBigInteger('transport_driver_id')->nullable();
            $table->unsignedBigInteger('academic_session_id')->nullable();
            $table->boolean('status')->default(true);
            $table->string('notes', 300)->nullable();
            $table->timestamps();

            $table->foreign('transport_route_id')->references('id')->on('transport_routes')->onDelete('cascade');
            $table->foreign('transport_vehicle_id')->references('id')->on('transport_vehicles')->onDelete('set null');
            $table->foreign('transport_driver_id')->references('id')->on('transport_drivers')->onDelete('set null');

            // ek route ek session mein sirf ek assignment
            $table->unique(['institute_id', 'transport_route_id', 'academic_session_id'], 'unique_route_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_route_assignments');
    }
};
