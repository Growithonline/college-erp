<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('center_wallets')) {
            return;
        }
        Schema::create('center_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->onDelete('cascade');
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->decimal('total_tokens', 14, 2)->default(0);
            $table->decimal('used_tokens', 14, 2)->default(0);
            $table->decimal('remaining_tokens', 14, 2)->default(0);
            $table->date('expires_at')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('center_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_wallets');
    }
};
