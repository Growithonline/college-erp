<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE course_stream_subjects MODIFY COLUMN subject_role ENUM('major','minor','compulsory','optional','both') NOT NULL");
        DB::statement("ALTER TABLE student_subjects MODIFY COLUMN subject_role ENUM('major','minor','compulsory','optional','both') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE course_stream_subjects MODIFY COLUMN subject_role ENUM('major','minor','compulsory','optional') NOT NULL");
        DB::statement("ALTER TABLE student_subjects MODIFY COLUMN subject_role ENUM('major','minor','compulsory','optional') NOT NULL");
    }
};