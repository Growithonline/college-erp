<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM ko string mein badlo taaki dynamic student types (private, distance, etc.) store ho sakein
        DB::statement("ALTER TABLE course_fee_rules MODIFY COLUMN student_type VARCHAR(50) NOT NULL DEFAULT 'all'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE course_fee_rules MODIFY COLUMN student_type ENUM('regular','ex_student','lateral','all') NOT NULL DEFAULT 'all'");
    }
};
