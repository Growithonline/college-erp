<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('has_scholarship')->default(false)->after('special_category');
            $table->string('scholarship_name', 100)->nullable()->after('has_scholarship');
            $table->enum('scholarship_type', [
                'govt_central', 'govt_state', 'university', 'institute', 'private', 'other'
            ])->nullable()->after('scholarship_name');
            $table->string('scholarship_authority', 150)->nullable()->after('scholarship_type');
            $table->date('scholarship_applied_date')->nullable()->after('scholarship_authority');
            $table->decimal('scholarship_amount', 10, 2)->nullable()->after('scholarship_applied_date');
            $table->string('scholarship_ref_no', 100)->nullable()->after('scholarship_amount');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'has_scholarship', 'scholarship_name', 'scholarship_type',
                'scholarship_authority', 'scholarship_applied_date',
                'scholarship_amount', 'scholarship_ref_no',
            ]);
        });
    }
};