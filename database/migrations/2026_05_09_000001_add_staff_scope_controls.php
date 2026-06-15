<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->boolean('restrict_course_access')->default(false)->after('max_discount_percent');
            $table->boolean('restrict_fee_collection_types')->default(false)->after('restrict_course_access');
        });

        Schema::create('staff_course_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['staff_member_id', 'course_id']);
        });

        Schema::create('staff_fee_collection_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained('staff_members')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['staff_member_id', 'fee_type_id'], 'staff_fee_collect_perm_unique');
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->foreignId('collected_by_staff_id')
                ->nullable()
                ->after('collected_by')
                ->constrained('staff_members')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by_staff_id');
        });

        Schema::dropIfExists('staff_fee_collection_permissions');
        Schema::dropIfExists('staff_course_permissions');

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn(['restrict_course_access', 'restrict_fee_collection_types']);
        });
    }
};
