<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stream_year_subject_rules', function (Blueprint $table) {
            $table->integer('major_min')->default(1)->after('minor_optional_max');
            $table->integer('major_max')->default(3)->after('major_min');
        });
    }

    public function down(): void
    {
        Schema::table('stream_year_subject_rules', function (Blueprint $table) {
            $table->dropColumn(['major_min', 'major_max']);
        });
    }
};