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
        Schema::create('daily_report_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->date('report_date');

            $table->string('book_no', 50)->nullable();
            $table->string('rec_range_from', 50)->nullable();
            $table->string('rec_range_to', 50)->nullable();
            $table->string('online_range_from', 50)->nullable();
            $table->string('online_range_to', 50)->nullable();
            $table->string('sr_no', 50)->nullable();
            $table->text('activities')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['institute_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_headers');
    }
};
