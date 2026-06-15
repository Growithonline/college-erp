<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change enum to varchar so all form values are accepted
        // Old enum: scholarship_quota, sports_quota, others, none
        // Form values: pwd, ex_serviceman, sports, ncc, others, none
        DB::statement("ALTER TABLE students MODIFY COLUMN special_category VARCHAR(50) NOT NULL DEFAULT 'none'");
    }

    public function down(): void
    {
        // Revert to original enum (data may be lost if new values are present)
        DB::statement("ALTER TABLE students MODIFY COLUMN special_category ENUM('scholarship_quota','sports_quota','others','none') NOT NULL DEFAULT 'none'");
    }
};
