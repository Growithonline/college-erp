<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('email');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('city')->nullable();
            $table->enum('status', ['new', 'contacted', 'interested', 'not_interested', 'junk'])->default('new');
            $table->string('source')->default('website');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'status']);
            $table->index(['institute_id', 'mobile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
