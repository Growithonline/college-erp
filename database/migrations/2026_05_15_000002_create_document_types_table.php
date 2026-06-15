<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->foreignId('document_category_id')->constrained('document_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('max_size_kb')->default(2048); // 2MB default
            $table->string('allowed_formats')->default('pdf,jpg,jpeg,png'); // comma-separated
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
