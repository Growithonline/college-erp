<?php
/**
 * Run this as: php fix_discounts.php
 * 
 * Strategy:
 * 1. Pehle sab items ka discount = 0 reset karo (rollback)
 * 2. Phir invoice ka total discount usi item pe daalo
 *    jiska fee_name 'subject' contain kare (agar exist kare)
 *    otherwise pehle item pe
 */

// Laravel bootstrap
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FeeInvoice;

$dryRun = in_array('--dry-run', $argv);
echo $dryRun ? "=== DRY RUN ===\n" : "=== LIVE RUN ===\n";

// Step 1: Reset all item discounts to 0 first (rollback previous proportional fix)
if (!$dryRun) {
    DB::table('fee_invoice_items')->update(['discount' => 0]);
    echo "Step 1: All item discounts reset to 0\n\n";
} else {
    echo "Step 1: Would reset all item discounts to 0\n\n";
}

// Step 2: Re-distribute — discount full amount on the most relevant item
$invoices = FeeInvoice::where('discount', '>', 0)->with('items')->get();

echo "Found {$invoices->count()} invoices with discount\n\n";

foreach ($invoices as $invoice) {
    $items       = $invoice->items;
    $discount    = (float) $invoice->discount;
    
    if ($items->isEmpty()) continue;

    // Priority: item jiska fee_name discount-receiving fee se match kare
    // 1. Agar single item — uski hi hai
    // 2. Multi-item — subject fee prefer karo, phir practical, phir first item
    $targetItem = null;

    if ($items->count() === 1) {
        $targetItem = $items->first();
    } else {
        // Subject Fee prefer karo
        $targetItem = $items->first(fn($i) => 
            stripos($i->fee_name, 'subject fee') !== false
        );
        // Practical Fee
        if (!$targetItem) {
            $targetItem = $items->first(fn($i) => 
                stripos($i->fee_name, 'practical') !== false
            );
        }
        // Fallback: pehla item
        if (!$targetItem) {
            $targetItem = $items->first();
        }
    }

    echo "Invoice {$invoice->invoice_no} — ₹{$discount} → {$targetItem->fee_name}\n";

    if (!$dryRun) {
        DB::table('fee_invoice_items')
            ->where('id', $targetItem->id)
            ->update(['discount' => $discount]);
    }
}

echo $dryRun 
    ? "\nDry-run done. Run without --dry-run to apply.\n"
    : "\nDone! All discounts fixed.\n";