<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRouteAssignment extends Model
{
    protected $fillable = [
        'institute_id',
        'transport_route_id',
        'transport_vehicle_id',
        'transport_driver_id',
        'transport_helper_id',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(TransportVehicle::class, 'transport_vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(TransportDriver::class, 'transport_driver_id');
    }

    public function helper()
    {
        return $this->belongsTo(TransportHelper::class, 'transport_helper_id');
    }

    public function isCurrent(): bool
    {
        return $this->end_date === null;
    }

    // Return the current active assignment for a route (session-independent)
    public static function forRoute(int $instituteId, int $routeId): ?self
    {
        return static::where('institute_id', $instituteId)
            ->where('transport_route_id', $routeId)
            ->whereNull('end_date')
            ->latest('start_date')
            ->first();
    }

    // Close this assignment (set end_date = yesterday so new one can start today)
    public function close(?string $endDate = null): void
    {
        $this->update(['end_date' => $endDate ?? now()->subDay()->toDateString()]);
    }
}
