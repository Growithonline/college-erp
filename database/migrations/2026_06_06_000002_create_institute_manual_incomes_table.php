<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('institute_manual_incomes')) {
            return;
        }
        Schema::create('institute_manual_incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('academic_session_id');
            $table->unsignedBigInteger('income_category_id');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('receipt_no')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('cascade');
            $table->foreign('income_category_id')->references('id')->on('institute_income_categories')->onDelete('restrict');
            $table->index(['institute_id', 'academic_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_manual_incomes');
    }
};
