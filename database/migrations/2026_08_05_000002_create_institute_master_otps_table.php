<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institute_master_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->unique()->constrained()->onDelete('cascade');
            $table->text('otp_encrypted');
            $table->string('valid_month', 7);
            $table->timestamp('generated_at');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_master_otps');
    }
};
