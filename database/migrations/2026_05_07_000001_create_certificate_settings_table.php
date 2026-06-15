<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id')->unique();
            $table->string('header_line1')->nullable();
            $table->string('header_line2')->nullable();
            $table->string('header_line3')->nullable();
            $table->string('logo')->nullable();
            $table->string('seal_image')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('principal_designation')->default('Principal');
            $table->string('principal_signature')->nullable();
            $table->string('registrar_name')->nullable();
            $table->string('registrar_designation')->default('Registrar');
            $table->string('registrar_signature')->nullable();
            $table->enum('theme', ['classic', 'colored', 'minimal'])->default('classic');
            $table->string('primary_color', 7)->default('#1e3a5f');
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_settings');
    }
};
