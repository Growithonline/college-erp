<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportVehicle extends Model
{
    protected $fillable = [
        'institute_id',
        'transport_vehicle_type_id',
        'vehicle_no',
        'registration_no',
        'model',
        'capacity',
        'fuel_type',
        'insurance_expiry',
        'permit_expiry',
        'fitness_expiry',
        'pollution_expiry',
        'service_due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'boolean',
        'insurance_expiry' => 'date',
        'permit_expiry' => 'date',
        'fitness_expiry' => 'date',
        'pollution_expiry' => 'date',
        'service_due_date' => 'date',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(TransportVehicleType::class, 'transport_vehicle_type_id');
    }

    public function allocations()
    {
        return $this->hasMany(TransportAllocation::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(TransportMaintenanceLog::class);
    }

    public function documents()
    {
        return $this->hasMany(TransportVehicleDocument::class);
    }
}
