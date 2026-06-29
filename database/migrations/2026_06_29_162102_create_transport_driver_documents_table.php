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
        Schema::create('transport_driver_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('transport_driver_id');
            $table->string('document_type', 50);
            $table->string('document_name', 150)->nullable();
            $table->string('file_path', 300);
            $table->string('original_name', 200)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('notes', 300)->nullable();
            $table->timestamps();

            $table->foreign('transport_driver_id')
                  ->references('id')->on('transport_drivers')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_driver_documents');
    }
};
