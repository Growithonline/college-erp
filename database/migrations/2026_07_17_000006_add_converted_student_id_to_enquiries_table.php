<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreignId('converted_student_id')->nullable()->after('assigned_staff_id')
                ->constrained('students')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_student_id');
        });
    }
};
