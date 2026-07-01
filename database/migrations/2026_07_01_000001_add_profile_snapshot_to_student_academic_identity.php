<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_academic_identity', function (Blueprint $table) {
            // Personal + family + address + scholarship ka full snapshot (JSON)
            $table->json('profile_snapshot')->nullable()->after('student_status_snapshot')
                ->comment('Full profile snapshot at time of record: personal, family, address, scholarship, education');
        });
    }

    public function down(): void
    {
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropColumn('profile_snapshot');
        });
    }
};
