<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('document_batches', 'course_stream_id')) {
            Schema::table('document_batches', function (Blueprint $table) {
                $table->foreignId('course_stream_id')->nullable()->after('course_id')
                    ->constrained('course_streams')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_batches', 'course_stream_id')) {
            Schema::table('document_batches', function (Blueprint $table) {
                $table->dropConstrainedForeignId('course_stream_id');
            });
        }
    }
};
