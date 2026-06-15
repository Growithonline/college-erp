<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportMonthlyCharge extends Model
{
    protected $fillable = [
        'transport_allocation_id',
        'institute_id',
        'charge_month',
        'amount',
        'generated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(TransportAllocation::class, 'transport_allocation_id');
    }
}
