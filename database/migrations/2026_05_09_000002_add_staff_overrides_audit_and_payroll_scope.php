<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->json('payroll_scope_categories')->nullable()->after('restrict_fee_collection_types');
        });

        Schema::create('staff_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->onDelete('cascade');
            $table->string('permission_key', 100);
            $table->enum('effect', ['allow', 'deny']);
            $table->date('expires_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['staff_member_id', 'permission_key']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id')->nullable()->index();
            $table->string('actor_type', 30)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('module', 60)->index();
            $table->string('action', 120)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('staff_permission_overrides');

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('payroll_scope_categories');
        });
    }
};
