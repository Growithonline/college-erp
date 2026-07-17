<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained('enquiries')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->enum('type', ['call', 'whatsapp', 'email', 'note']);
            $table->text('note');
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();

            $table->index('enquiry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_follow_ups');
    }
};
