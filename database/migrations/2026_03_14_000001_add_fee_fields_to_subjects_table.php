<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // subject_type already exists, sirf has_practical add karo
            if (!Schema::hasColumn('subjects', 'has_practical')) {
                $table->boolean('has_practical')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['subject_type', 'has_practical']);
        });
    }
};
