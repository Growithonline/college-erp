<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Course-wise particulars used to represent one specific year of a course
     * (e.g. "BA - 1st Year"). They now represent the whole course — the report
     * expands each into one "Year" column per year automatically — so any
     * previously-set year_number is no longer meaningful and is cleared.
     */
    public function up(): void
    {
        DB::table('report_particulars')
            ->whereNotNull('course_id')
            ->update(['year_number' => null]);
    }

    /**
     * Reverse the migrations.
     *
     * The original per-particular year values aren't recoverable — nothing to undo.
     */
    public function down(): void
    {
        //
    }
};
