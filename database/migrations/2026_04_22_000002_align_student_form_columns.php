<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'submitted_date')) {
                $table->date('submitted_date')->nullable()->after('admission_date');
            }
        });

        DB::statement("ALTER TABLE students MODIFY COLUMN special_category VARCHAR(50) NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE students MODIFY COLUMN nationality VARCHAR(50) NOT NULL DEFAULT 'indian'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE students MODIFY COLUMN special_category ENUM('scholarship_quota','sports_quota','others','none') NOT NULL DEFAULT 'none'");
        DB::statement("ALTER TABLE students MODIFY COLUMN nationality ENUM('indian','nepali','bhutanese','sri_lankan','others') NOT NULL DEFAULT 'indian'");

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'submitted_date')) {
                $table->dropColumn('submitted_date');
            }
        });
    }
};
