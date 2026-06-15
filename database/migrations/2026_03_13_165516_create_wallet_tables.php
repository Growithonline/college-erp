<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Student Wallets (session-wise) ──────────────────────────────
        Schema::create('student_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->decimal('main_b', 12, 2)->default(0.00); // negative=due, positive=advance
            $table->timestamps();

            // Ek student ka ek session mein sirf ek wallet
            $table->unique(['student_id', 'academic_session_id']);
        });

        // ── Student Transactions (session-wise) ─────────────────────────
        Schema::create('student_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->string('des')->nullable();
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('debit',  12, 2)->default(0.00);
            $table->tinyInteger('type');            // 1=Debit, 2=Credit
            $table->date('date');
            $table->decimal('op_bal', 12, 2)->default(0.00);
            $table->decimal('cl_bal', 12, 2)->default(0.00);
            $table->foreignId('fee_invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('by_user_id')->nullable();
            $table->timestamps();
        });

        // ── Institute Wallets (session-wise) ────────────────────────────
        Schema::create('institute_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->decimal('main_b', 12, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['institute_id', 'academic_session_id']);
        });

        // ── Institute Transactions (session-wise) ───────────────────────
        Schema::create('institute_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_session_id')->constrained()->onDelete('cascade');
            $table->string('des')->nullable();
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->decimal('debit',  12, 2)->default(0.00);
            $table->tinyInteger('type');
            $table->date('date');
            $table->decimal('op_bal', 12, 2)->default(0.00);
            $table->decimal('cl_bal', 12, 2)->default(0.00);
            $table->foreignId('fee_invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_transactions');
        Schema::dropIfExists('institute_wallets');
        Schema::dropIfExists('student_transactions');
        Schema::dropIfExists('student_wallets');
    }
};