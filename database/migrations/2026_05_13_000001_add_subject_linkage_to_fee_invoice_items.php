<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->foreignId('subject_id')
                ->nullable()
                ->after('fee_type_id')
                ->constrained('subjects')
                ->nullOnDelete();
            $table->string('item_type', 50)->nullable()->after('subject_id');

            $table->index(['item_type', 'subject_id'], 'fee_invoice_items_type_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->dropIndex('fee_invoice_items_type_subject_idx');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropColumn('item_type');
        });
    }
};
