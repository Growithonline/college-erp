<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('journal_entry_id');
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('is_reversed')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('reversal_journal_entry_id');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
        });

        Schema::table('salary_records', function (Blueprint $table) {
            $table->foreignId('reversal_journal_entry_id')->nullable()->after('journal_entry_id')->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable()->after('status');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
            $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
        });
    }

    public function down(): void
    {
        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_journal_entry_id');
            $table->dropColumn(['reversed_at', 'reversed_by', 'reversal_reason']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversal_journal_entry_id');
            $table->dropColumn(['is_reversed', 'reversed_at', 'reversed_by', 'reversal_reason']);
        });
    }
};
