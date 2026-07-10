<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'institute_id',
        'academic_session_id',
        'transport_route_id',
        'transport_route_stop_id',
        'transport_vehicle_id',
        'transport_driver_id',
        'fee_amount',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'charged_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function stop()
    {
        return $this->belongsTo(TransportRouteStop::class, 'transport_route_stop_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(TransportDriver::class, 'transport_driver_id');
    }

    public function payments()
    {
        return $this->hasMany(TransportPayment::class);
    }

    /**
     * charged_amount is null until this allocation has actually been billed, or has been
     * explicitly resolved to a definitive amount (including a literal 0 — e.g. a yearly
     * cross-session skip, or a fully credited/written-off allocation). Only fall back to
     * the nominal fee_amount as a "what this will cost" preview while nothing has been
     * charged yet; once a value has been set, even zero, trust it. This is the single
     * definition of "what this allocation owes" — every balance/credit/proration
     * calculation in the transport module reads it from here rather than re-deriving it.
     */
    public function getEffectiveChargedAttribute(): float
    {
        return $this->charged_amount !== null
            ? (float) $this->charged_amount
            : (float) $this->fee_amount;
    }

    public function getBalanceAttribute(): float
    {
        return round($this->effective_charged - (float) $this->paid_amount, 2);
    }
}
