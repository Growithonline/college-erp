<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Institute Bank Accounts ──────────────────────────────────
        Schema::create('institute_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->string('bank_name', 100);           // SBI, HDFC, PNB
            $table->string('account_name', 150);         // Account holder name
            $table->string('account_no', 50);            // Account number
            $table->string('ifsc_code', 20)->nullable(); // IFSC
            $table->string('branch', 100)->nullable();   // Branch name
            $table->string('upi_id', 100)->nullable();   // UPI ID linked to this account
            $table->string('display_label', 100)->nullable(); // Custom label e.g. "SBI Main"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── 2. Payment Mode Permissions per user ──────────────────────
        Schema::create('payment_mode_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')->constrained()->onDelete('cascade');
            $table->enum('user_type', ['staff', 'center', 'partner']);
            $table->unsignedBigInteger('user_id');       // StaffMember/Center/ChannelPartner id
            $table->json('allowed_modes');               // ["cash","upi","cheque","bank_transfer"]
            $table->json('allowed_bank_ids');            // [1, 3] → institute_bank_accounts ids
            $table->timestamps();

            $table->unique(['user_type', 'user_id']);
            $table->index(['institute_id', 'user_type', 'user_id']);
        });

        // ── 3. FeeInvoice mein bank_account_id add karo ───────────────
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->foreignId('bank_account_id')
                  ->nullable()
                  ->after('bank_name')
                  ->constrained('institute_bank_accounts')
                  ->nullOnDelete();
            $table->boolean('is_cancelled')->default(false)->after('remarks');
            $table->string('cancel_reason', 255)->nullable()->after('is_cancelled');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['bank_account_id','is_cancelled','cancel_reason','cancelled_at','cancelled_by']);
        });
        Schema::dropIfExists('payment_mode_permissions');
        Schema::dropIfExists('institute_bank_accounts');
    }
};