<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->date('date');
            $table->string('entry_key', 120)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('posted');
            $table->text('narration')->nullable();
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->foreignId('reversal_of_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_role', 30)->nullable();
            $table->unsignedBigInteger('reversed_by_user_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['institute_id', 'entry_key'], 'journal_entries_institute_entry_key_unique');
            $table->index(['institute_id', 'date'], 'journal_entries_institute_date_idx');
            $table->index(['institute_id', 'reference_type', 'reference_id'], 'journal_entries_reference_idx');
            $table->index(['institute_id', 'status'], 'journal_entries_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
