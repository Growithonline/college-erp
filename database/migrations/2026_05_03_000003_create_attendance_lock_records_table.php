<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_lock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('lock_year');
            $table->unsignedTinyInteger('lock_month');
            
            // Reason for locking
            $table->enum('lock_reason', ['month_closed', 'salary_generated', 'manual'])->default('manual');
            
            // Lock metadata
            $table->foreignId('locked_by')->nullable()->constrained('staff_members', 'id')->nullOnDelete();
            $table->text('lock_remarks')->nullable();
            $table->timestamps();
            
            // Unique lock per month per institute
            $table->unique(['institute_id', 'lock_year', 'lock_month'], 'attendance_lock_unique');
            $table->index(['institute_id', 'lock_year', 'lock_month'], 'lock_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_lock_records');
    }
};
