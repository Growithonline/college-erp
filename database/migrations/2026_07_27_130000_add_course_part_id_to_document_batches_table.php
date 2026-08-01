<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_batches', 'course_part_id')) {
            Schema::table('document_batches', function (Blueprint $table) {
                $table->foreignId('course_part_id')->nullable()->after('course_id')
                    ->constrained('course_parts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_batches', 'course_part_id')) {
            Schema::table('document_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_part_id');
            });
        }
    }
};
