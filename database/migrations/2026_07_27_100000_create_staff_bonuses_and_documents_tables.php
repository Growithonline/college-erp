<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_bonuses')) {
            Schema::create('staff_bonuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
                $table->string('bonus_type', 50); // diwali, holi, eid, annual, adhoc
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('payment_mode', 30)->nullable();
                $table->string('remarks', 300)->nullable();
                $table->timestamps();

                $table->index(['institute_id', 'payment_date']);
            });
        }

        if (!Schema::hasTable('staff_documents')) {
            Schema::create('staff_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
                $table->foreignId('staff_member_id')->constrained('staff_members')->cascadeOnDelete();
                $table->string('document_type', 50); // aadhaar, pan, driving_license, voter_id, passport, certificate, other
                $table->string('document_number', 100)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('file_path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('notes', 300)->nullable();
                $table->timestamps();

                $table->index(['institute_id', 'expiry_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff_bonuses');
    }
};
