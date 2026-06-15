<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('name');                         // "2025-26"
            $table->date('start_date');                     // 2025-07-01
            $table->date('end_date');                       // 2026-06-30
            $table->boolean('is_active')->default(false);   // sirf ek active hogi
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};
