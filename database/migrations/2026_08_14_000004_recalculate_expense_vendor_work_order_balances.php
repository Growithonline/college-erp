<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data repair: ExpenseVendorWorkOrder::credit()/debit()/creditBack() originally used
 * update() to write total_budget/total_spent/remaining_amount, but those columns were
 * excluded from $fillable — the writes were silently dropped, so every work order's
 * running balance (and every transaction's stored balance_after, since it read the
 * same never-updated remaining_amount) went stale after the first transaction. Fixed
 * going forward in ExpenseVendorWorkOrder (now uses forceFill()); this recomputes the
 * existing rows from their transaction history so already-created test data is correct.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('expense_vendor_work_orders')) {
            return;
        }

        $workOrders = DB::table('expense_vendor_work_orders')->get(['id']);

        foreach ($workOrders as $wo) {
            $running = 0.0;

            $transactions = DB::table('expense_vendor_work_order_transactions')
                ->where('expense_vendor_work_order_id', $wo->id)
                ->orderBy('id')
                ->get();

            $totalBudget = 0.0;
            $totalSpent  = 0.0;

            foreach ($transactions as $tx) {
                if ($tx->type === 'credit') {
                    $running     += (float) $tx->amount;
                    $totalBudget += (float) $tx->amount;
                } else {
                    $running    -= (float) $tx->amount;
                    $totalSpent += (float) $tx->amount;
                }

                DB::table('expense_vendor_work_order_transactions')
                    ->where('id', $tx->id)
                    ->update(['balance_after' => round($running, 2)]);
            }

            DB::table('expense_vendor_work_orders')->where('id', $wo->id)->update([
                'total_budget'     => round($totalBudget, 2),
                'total_spent'      => round($totalSpent, 2),
                'remaining_amount' => round($totalBudget - $totalSpent, 2),
            ]);
        }
    }

    public function down(): void
    {
        // Data repair only — not reversible.
    }
};
