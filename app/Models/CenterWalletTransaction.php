<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterWalletTransaction extends Model
{
    protected $fillable = [
        'center_wallet_id', 'type', 'amount',
        'balance_after', 'fee_invoice_id', 'note', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(CenterWallet::class, 'center_wallet_id');
    }

    public function invoice()
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }
}
