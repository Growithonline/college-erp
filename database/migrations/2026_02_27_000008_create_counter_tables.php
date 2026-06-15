<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student ID counter — BBA/STU/2026/0001
        Schema::create('admission_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');       // 2026 (academic session end year)
            $table->integer('last_seq')->default(0);  // last used sequence
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });

        // Fee Invoice counter — BBA/FEE/2026/00001
        Schema::create('fee_invoice_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('last_seq')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_invoice_counters');
        Schema::dropIfExists('admission_counters');
    }
};
