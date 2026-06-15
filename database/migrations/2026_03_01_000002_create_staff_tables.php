<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff Roles — pre-defined + custom
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('name');                      // Manager, Accountant, Receptionist
            $table->boolean('is_system')->default(false); // system roles delete nahi honge
            $table->json('permissions');                 // {"admission_add": true, "fee_collect": false, ...}
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'name']);
        });

        // Staff Members
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_role_id')->constrained('staff_roles')->onDelete('cascade');
            $table->string('name');
            $table->string('mobile', 15);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('photo')->nullable();
            $table->string('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Centers me login fields add karo
        Schema::table('centers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->boolean('can_add_admission')->default(true)->after('can_collect_fee');
            $table->boolean('can_view_students')->default(true)->after('can_add_admission');
            $table->rememberToken()->after('can_view_students');
        });
    }

    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['password', 'can_add_admission', 'can_view_students', 'remember_token']);
        });
        Schema::dropIfExists('staff_members');
        Schema::dropIfExists('staff_roles');
    }
};
