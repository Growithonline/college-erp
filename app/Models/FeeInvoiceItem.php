<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeInvoiceItem extends Model
{
    protected $fillable = [
        'fee_invoice_id', 'fee_type_id', 'subject_id', 'item_type', 'fee_name', 'amount', 'discount', 'fine', 'total_fee',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'discount'  => 'decimal:2',
        'fine'      => 'decimal:2',
        'total_fee' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
