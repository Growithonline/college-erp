<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRouteStop extends Model
{
    protected $fillable = [
        'transport_route_id',
        'stop_name',
        'landmark',
        'sequence',
        'pickup_time',
        'drop_time',
        'fee_amount',
        'status',
    ];

    protected $casts = [
        'sequence'   => 'integer',
        'fee_amount' => 'decimal:2',
        'status'     => 'boolean',
    ];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }
}
