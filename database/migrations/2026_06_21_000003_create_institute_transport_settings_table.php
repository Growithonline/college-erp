<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_transport_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id')->unique();
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');

            // What to charge when student transfers from one route to another
            $table->enum('on_route_transfer', ['full_charge', 'no_charge', 'prorated_charge'])
                  ->default('full_charge');

            // Prorate monthly fee for students who join mid-month
            $table->enum('prorated_billing', ['disabled', 'after_midmonth', 'daily_basis'])
                  ->default('disabled');

            // For yearly routes: if student already paid in Sem 1, don't charge again in Sem 2
            $table->boolean('yearly_fee_cross_session')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_transport_settings');
    }
};
