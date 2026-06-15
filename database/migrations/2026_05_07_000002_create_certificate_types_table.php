<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->string('name');
            $table->string('slug', 30);
            $table->text('body_template');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['institute_id', 'slug']);
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_types');
    }
};
