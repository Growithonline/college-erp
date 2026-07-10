<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * charged_amount previously defaulted to 0 and stayed 0 for two very different
     * situations: "never billed yet" and "billed, then resolved down to nothing owed"
     * (a yearly cross-session skip, or a fully credited allocation). Both read as the
     * same value, so any code deciding "is this due?" could not tell them apart and
     * fell back to treating a resolved-to-zero allocation as if it were still fully
     * due. Making the column nullable lets NULL mean "not billed yet" and a literal
     * 0 mean "billed and resolved — nothing owed", which is what the application
     * code (TransportAllocation::getBalanceAttribute and friends) now expects.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `transport_allocations` MODIFY `charged_amount` DECIMAL(12,2) NULL DEFAULT NULL');

        // Reinterpret existing rows. An active (not yet closed), non-yearly allocation
        // with charged_amount = 0 is genuinely pending a future bill (e.g. charge_now was
        // left unchecked, waiting on the semester Billing > Generate run) — that becomes
        // NULL so it keeps previewing as the nominal fee_amount, matching prior behaviour.
        // Every other zero (closed allocations, or yearly-frequency allocations already
        // resolved by the cross-session skip check) was a deliberate "nothing owed"
        // result and must keep reading as 0, not fall back to fee_amount.
        DB::statement(<<<'SQL'
            UPDATE `transport_allocations` ta
            INNER JOIN `transport_routes` tr ON tr.id = ta.transport_route_id
            SET ta.charged_amount = NULL
            WHERE ta.charged_amount = 0
              AND ta.is_active = 1
              AND tr.billing_frequency <> 'yearly'
        SQL);
    }

    public function down(): void
    {
        DB::table('transport_allocations')->whereNull('charged_amount')->update(['charged_amount' => 0]);

        DB::statement('ALTER TABLE `transport_allocations` MODIFY `charged_amount` DECIMAL(12,2) NOT NULL DEFAULT 0');
    }
};
