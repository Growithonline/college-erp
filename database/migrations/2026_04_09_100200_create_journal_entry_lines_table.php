<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->unsignedInteger('line_no')->default(1);
            $table->enum('entry_type', ['debit', 'credit']);
            $table->decimal('amount', 14, 2);
            $table->string('narration', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'line_no'], 'journal_entry_lines_entry_line_idx');
            $table->index(['account_id', 'entry_type'], 'journal_entry_lines_account_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
