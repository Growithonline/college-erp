<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->string('title', 255);
            $table->text('body');
            $table->string('notice_type', 30)->default('general');
            $table->string('visible_to', 20)->default('all');
            $table->date('notice_date');
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('posted_by_staff_id')->nullable();
            $table->unsignedBigInteger('posted_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['institute_id', 'is_active', 'notice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
