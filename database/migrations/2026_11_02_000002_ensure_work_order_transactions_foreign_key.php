<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'expense_vendor_work_order_transactions';

    // The original create migration used an auto-generated FK name over MySQL's
    // 64-char limit, so the table could exist without any of its foreign keys.
    private const FOREIGN_KEYS = [
        'expense_vendor_work_order_id' => ['evwo_txn_work_order_fk', 'expense_vendor_work_orders', 'cascade'],
        'expense_id'                   => ['evwo_txn_expense_fk', 'expenses', 'set null'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (self::FOREIGN_KEYS as $column => [$name, $referencedTable, $onDelete]) {
            $exists = collect(Schema::getForeignKeys(self::TABLE))
                ->contains(fn ($fk) => $fk['columns'] === [$column]);

            if ($exists) {
                continue;
            }

            Schema::table(self::TABLE, function (Blueprint $table) use ($column, $name, $referencedTable, $onDelete) {
                $table->foreign($column, $name)->references('id')->on($referencedTable)->onDelete($onDelete);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (self::FOREIGN_KEYS as [$name]) {
            if (collect(Schema::getForeignKeys(self::TABLE))->contains(fn ($fk) => $fk['name'] === $name)) {
                Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropForeign($name));
            }
        }
    }
};
