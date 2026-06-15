<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notice_reads')) {
            return;
        }
        Schema::create('notice_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notice_id');
            $table->string('reader_type', 20); // institute, staff, center, partner
            $table->unsignedBigInteger('reader_id');
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['notice_id', 'reader_type', 'reader_id'], 'notice_reads_unique');
            $table->index(['reader_type', 'reader_id'], 'notice_reads_reader_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_reads');
    }
};
