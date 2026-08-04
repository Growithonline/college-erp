<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('institute_policy_acceptances')) {
            return;
        }

        Schema::create('institute_policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('version');
            $table->foreignId('accepted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['institute_id', 'document_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_policy_acceptances');
    }
};
