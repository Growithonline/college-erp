<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_fee_discount_permissions')) {
            return;
        }
        Schema::create('staff_fee_discount_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['staff_member_id', 'fee_type_id'], 'staff_fee_disc_perm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_fee_discount_permissions');
    }
};
