<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wallet_extension_requests')) {
            return;
        }
        Schema::create('wallet_extension_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['center', 'channel']);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->enum('request_type', ['expiry_extension', 'token_topup']);
            $table->text('reason');
            $table->unsignedInteger('requested_days')->nullable();
            $table->decimal('requested_amount', 14, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['institute_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_extension_requests');
    }
};
