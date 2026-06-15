<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('allowed_admission_forms', 10)->default('both')->after('restrict_fee_collection_types');
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->boolean('restrict_fee_collection_types')->default(false)->after('can_waive_fee');
        });

        Schema::create('center_fee_discount_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['center_id', 'fee_type_id']);
        });

        Schema::create('center_fee_collection_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['center_id', 'fee_type_id'], 'center_fee_collect_perm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_fee_collection_permissions');
        Schema::dropIfExists('center_fee_discount_permissions');

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn('restrict_fee_collection_types');
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('allowed_admission_forms');
        });
    }
};
