<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by_staff_id')->nullable()->after('admitted_by_staff_id');
            $table->string('approved_by_name')->nullable()->after('approved_by_staff_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_name');
            $table->text('approval_notes')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'approved_by_staff_id',
                'approved_by_name',
                'approved_at',
                'approval_notes',
            ]);
        });
    }
};
