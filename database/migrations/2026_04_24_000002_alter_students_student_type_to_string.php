<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM ko string mein badlo — dynamic student types support karne ke liye
        DB::statement("ALTER TABLE students MODIFY COLUMN student_type VARCHAR(50) NOT NULL DEFAULT 'regular'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE students MODIFY COLUMN student_type ENUM('regular','private','distance') NOT NULL DEFAULT 'regular'");
    }
};
