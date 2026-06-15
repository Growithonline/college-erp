<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportMaintenanceLog extends Model
{
    protected $fillable = [
        'transport_vehicle_id',
        'institute_id',
        'service_date',
        'next_service_due',
        'odometer_km',
        'service_type',
        'garage_name',
        'cost',
        'status',
        'issues_found',
        'remarks',
        'by_user_id',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_service_due' => 'date',
        'odometer_km' => 'integer',
        'cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }
}
