<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 150);
            $table->enum('type', ['asset', 'liability', 'income', 'expense', 'equity']);
            $table->enum('normal_side', ['debit', 'credit']);
            $table->string('linked_type', 50)->nullable();
            $table->unsignedBigInteger('linked_id')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_posting')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'code'], 'accounts_institute_code_unique');
            $table->index(['institute_id', 'type'], 'accounts_institute_type_idx');
            $table->index(['institute_id', 'linked_type', 'linked_id'], 'accounts_linked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
