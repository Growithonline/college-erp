<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_category_l1_id')->nullable()->after('description');
            $table->unsignedBigInteger('expense_category_l2_id')->nullable()->after('expense_category_l1_id');
            $table->unsignedBigInteger('expense_vendor_id')->nullable()->after('expense_category_l2_id');

            // approval_status: auto_approved (no limit exceeded), pending (waiting for approval),
            // approved (manually approved), rejected
            $table->string('approval_status')->default('auto_approved')->after('expense_vendor_id');
            $table->unsignedBigInteger('approved_by_staff_id')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by_staff_id');
            $table->text('approval_rejection_reason')->nullable()->after('approved_at');

            $table->boolean('wallet_debited')->default(false)->after('approval_rejection_reason');

            $table->foreign('expense_category_l1_id')->references('id')->on('expense_categories_l1')->onDelete('set null');
            $table->foreign('expense_category_l2_id')->references('id')->on('expense_categories_l2')->onDelete('set null');
            $table->foreign('expense_vendor_id')->references('id')->on('expense_vendors')->onDelete('set null');

            $table->index(['institute_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_l1_id']);
            $table->dropForeign(['expense_category_l2_id']);
            $table->dropForeign(['expense_vendor_id']);
            $table->dropIndex(['institute_id', 'approval_status']);
            $table->dropColumn([
                'expense_category_l1_id', 'expense_category_l2_id', 'expense_vendor_id',
                'approval_status', 'approved_by_staff_id', 'approved_at',
                'approval_rejection_reason', 'wallet_debited',
            ]);
        });
    }
};
