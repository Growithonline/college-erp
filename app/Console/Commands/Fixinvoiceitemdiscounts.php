<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FeeInvoice;
use App\Models\FeeInvoiceItem;
use Illuminate\Support\Facades\DB;

class FixInvoiceItemDiscounts extends Command
{
    protected $signature   = 'fix:invoice-item-discounts {--dry-run : Preview without saving}';
    protected $description = 'Fix fee_invoice_items.discount — distribute invoice-level discount to items';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        // Invoices jinme discount > 0 hai lekin kisi bhi item mein discount = 0 hai
        $invoices = FeeInvoice::where('discount', '>', 0)
            ->whereHas('items', fn($q) => $q->where('discount', 0))
            ->with('items')
            ->get();

        $this->info("Found {$invoices->count()} invoices to fix" . ($dryRun ? ' (dry-run)' : ''));

        $fixed = 0;
        foreach ($invoices as $invoice) {
            $items          = $invoice->items;
            $totalDiscount  = (float) $invoice->discount;
            $totalAmount    = $items->sum('amount');

            if ($totalAmount <= 0 || $items->isEmpty()) continue;

            $this->line("Invoice {$invoice->invoice_no} — discount ₹{$totalDiscount} across {$items->count()} items");

            $remaining = $totalDiscount;

            foreach ($items as $i => $item) {
                $isLast = ($i === $items->count() - 1);

                if ($isLast) {
                    // Last item mein baaki sab discount
                    $itemDisc = $remaining;
                } else {
                    // Proportional distribution
                    $proportion = (float) $item->amount / $totalAmount;
                    $itemDisc   = round($totalDiscount * $proportion, 2);
                    $remaining  = round($remaining - $itemDisc, 2);
                }

                $this->line("  → {$item->fee_name}: amount={$item->amount}, discount={$itemDisc}");

                if (!$dryRun) {
                    DB::table('fee_invoice_items')
                        ->where('id', $item->id)
                        ->update(['discount' => $itemDisc]);
                }
            }

            $fixed++;
        }

        $this->info($dryRun
            ? "Dry-run complete — {$fixed} invoices would be fixed."
            : "Done — {$fixed} invoices fixed.");
    }
}