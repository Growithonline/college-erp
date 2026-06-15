<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportAllocation extends Model
{
    protected $fillable = [
        'student_id',
        'institute_id',
        'academic_session_id',
        'transport_route_id',
        'transport_route_stop_id',
        'transport_vehicle_id',
        'transport_driver_id',
        'fee_amount',
        'charged_amount',
        'paid_amount',
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

    public function monthlyCharges()
    {
        return $this->hasMany(TransportMonthlyCharge::class);
    }

    public function getBalanceAttribute(): float
    {
        // For monthly billing, charged_amount grows each month.
        // For one_time, charged_amount = fee_amount (set on allocation creation).
        $charged = (float) $this->charged_amount > 0
            ? (float) $this->charged_amount
            : (float) $this->fee_amount;

        return round($charged - (float) $this->paid_amount, 2);
    }
}
