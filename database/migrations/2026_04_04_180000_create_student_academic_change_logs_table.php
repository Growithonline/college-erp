<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academic_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained('academic_sessions')->nullOnDelete();
            $table->json('old_snapshot')->nullable();
            $table->json('new_snapshot')->nullable();
            $table->decimal('old_academic_fee', 12, 2)->default(0);
            $table->decimal('new_academic_fee', 12, 2)->default(0);
            $table->decimal('fee_delta', 12, 2)->default(0);
            $table->decimal('wallet_balance_after', 12, 2)->default(0);
            $table->string('actor_type', 30)->nullable();
            $table->string('actor_name', 150)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'academic_session_id'], 'stud_acad_change_student_session_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academic_change_logs');
    }
};
