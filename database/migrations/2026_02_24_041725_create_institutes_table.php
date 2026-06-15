<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutes', function (Blueprint $table) {
            $table->id();

            $table->string('institute_uid')->unique();

            $table->string('name');
            $table->string('mobile', 20);
            $table->string('email')->unique();

            $table->string('image')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();

            // Owner
            $table->string('owner_name');
            $table->string('owner_mobile', 20);
            $table->string('owner_email');
            $table->string('owner_whatsapp')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('owner_identity_proof')->nullable();

            // SaaS
            $table->integer('student_limit')->default(0);
            $table->date('subscription_start')->nullable();
            $table->date('subscription_end')->nullable();
            $table->enum('status', ['active','paused','expired','revoked'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutes');
    }
};