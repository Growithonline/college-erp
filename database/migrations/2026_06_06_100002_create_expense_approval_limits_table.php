<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_approval_limits')) {
            return;
        }
        Schema::create('expense_approval_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->unsignedBigInteger('staff_role_id');
            // max amount a staff of this role can create without needing approval
            // 0 = cannot auto-approve anything (all go to admin for approval)
            $table->decimal('max_auto_approve_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('staff_role_id')->references('id')->on('staff_roles')->onDelete('cascade');
            $table->unique(['institute_id', 'staff_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_approval_limits');
    }
};
