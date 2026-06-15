<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerCommissionEntry extends Model
{
    protected $fillable = [
        'partner_id', 'fee_invoice_id',
        'paid_amount', 'commission_percent', 'commission_amount',
    ];

    protected $casts = [
        'paid_amount'        => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount'  => 'decimal:2',
    ];

    public function partner()
    {
        return $this->belongsTo(ChannelPartner::class, 'partner_id');
    }

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }
}
