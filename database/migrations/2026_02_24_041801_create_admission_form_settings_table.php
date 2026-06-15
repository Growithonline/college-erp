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
        Schema::create('admission_form_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->enum('form_type', ['admission', 'quick', 'online', 'receipt']);
            $table->json('field_config');      // field ON/OFF, required, order, label
            $table->json('form_config')->nullable(); // form-level settings (receipt size, prefix etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'form_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_form_settings');
    }
};
