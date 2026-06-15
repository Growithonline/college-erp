<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_vendors')) {
            return;
        }
        Schema::create('expense_vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('l2_id');
            $table->string('name');
            $table->string('gst_no')->nullable();
            $table->string('pan_no')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('l2_id')->references('id')->on('expense_categories_l2')->onDelete('cascade');
            $table->index(['institute_id', 'l2_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_vendors');
    }
};
