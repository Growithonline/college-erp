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
        'academic_session_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'boolean',
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

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    // Helper: given route + session, return active assignment
    public static function forRoute(int $instituteId, int $routeId, ?int $sessionId): ?self
    {
        return static::where('institute_id', $instituteId)
            ->where('transport_route_id', $routeId)
            ->where('status', true)
            ->where(function ($q) use ($sessionId) {
                $q->where('academic_session_id', $sessionId)
                  ->orWhereNull('academic_session_id');
            })
            ->orderByRaw('academic_session_id IS NULL ASC') // session-specific takes priority
            ->first();
    }
}
