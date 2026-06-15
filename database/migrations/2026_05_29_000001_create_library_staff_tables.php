<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('library_staff')) {
            return;
        }
        Schema::create('library_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('employee_id', 30)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->unique();
            $table->string('photo')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->enum('designation', ['librarian', 'assistant_librarian', 'attendant', 'data_entry']);
            $table->date('joining_date')->nullable();
            $table->enum('shift', ['morning', 'evening', 'both'])->default('morning');
            $table->string('assigned_section')->nullable();
            $table->string('qualification')->nullable();
            $table->boolean('status')->default(true);
            // dual-role link: nullable FK to existing staff member
            $table->foreignId('staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            // security fields
            $table->unsignedTinyInteger('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('library_staff_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_staff_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('preset', 30)->nullable();
            $table->json('permissions');
            $table->timestamps();
        });

        Schema::create('library_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_staff_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->enum('status', ['success', 'failed_otp', 'locked']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_login_logs');
        Schema::dropIfExists('library_staff_permissions');
        Schema::dropIfExists('library_staff');
    }
};
