<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
            $table->date('attendance_date');
            
            // Category snapshot at time of marking (for reporting)
            $table->enum('staff_category_snapshot', ['Teaching', 'Office', 'Non-Teaching', 'Guest'])->nullable();
            
            // Attendance status
            $table->enum('status', [
                'Present',
                'Absent',
                'Half Day',
                'Paid Leave',
                'Unpaid Leave',
                'Holiday',
                'Week Off'
            ])->default('Present');
            
            // Time tracking
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            
            // Remarks and tracking
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('staff_members', 'id')->nullOnDelete();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for fast querying
            $table->unique(['institute_id', 'staff_member_id', 'attendance_date'], 'attendance_staff_date_unique');
            $table->index(['institute_id', 'attendance_date'], 'attendance_date_idx');
            $table->index(['institute_id', 'staff_member_id'], 'attendance_staff_idx');
            $table->index(['status'], 'attendance_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance');
    }
};
