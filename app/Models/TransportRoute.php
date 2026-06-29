<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    protected $fillable = [
        'institute_id',
        'route_code',
        'name',
        'start_point',
        'end_point',
        'distance_km',
        'fee_amount',
        'billing_frequency',
        'morning_time',
        'evening_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function stops()
    {
        return $this->hasMany(TransportRouteStop::class)->orderBy('sequence');
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class);
    }

    public function activeAllocations()
    {
        return $this->hasMany(TransportAllocation::class)->where('is_active', true);
    }

    public function assignments()
    {
        return $this->hasMany(TransportRouteAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(TransportRouteAssignment::class)->where('status', true)->latest();
    }
}
