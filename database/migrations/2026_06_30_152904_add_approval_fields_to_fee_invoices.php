<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            // 'approved' = today's normal flow (default, no behaviour change for existing rows).
            // 'pending'  = staff submitted a custom fee item above their limit; nothing has been
            //              credited to any wallet/ledger yet — see pending_settlement_data.
            // 'rejected' = an approver declined it; also never credited.
            $table->string('approval_status', 20)->default('approved')->after('is_cancelled');
            $table->foreignId('approved_by_staff_id')->nullable()->after('approval_status')
                ->constrained('staff_members')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_staff_id');
            $table->text('approval_rejection_reason')->nullable()->after('approved_at');
            // Serialized validated fee_items + computed totals, captured at submission time so
            // approval can replay the exact same settlement the staff member originally submitted.
            $table->json('pending_settlement_data')->nullable()->after('approval_rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by_staff_id');
            $table->dropColumn(['approval_status', 'approved_at', 'approval_rejection_reason', 'pending_settlement_data']);
        });
    }
};
