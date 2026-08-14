<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseVendorWorkOrderTransaction extends Model
{
    protected $fillable = [
        'expense_vendor_work_order_id', 'type', 'amount',
        'balance_after', 'expense_id', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(ExpenseVendorWorkOrder::class, 'expense_vendor_work_order_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
