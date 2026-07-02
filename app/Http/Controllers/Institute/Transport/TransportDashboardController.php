<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportAllocation;
use App\Models\TransportDriver;
use App\Models\TransportPayment;
use App\Models\TransportRoute;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;

class TransportDashboardController extends TransportBaseController
{
    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $summary = [
            'vehicles'         => TransportVehicle::where('institute_id', $instituteId)->count(),
            'routes'           => TransportRoute::where('institute_id', $instituteId)->count(),
            'drivers'          => TransportDriver::where('institute_id', $instituteId)->count(),
            'allocations'      => TransportAllocation::where('institute_id', $instituteId)->count(),
            'active_allocations' => TransportAllocation::where('institute_id', $instituteId)->where('is_active', true)->count(),
            'payments'         => TransportPayment::where('institute_id', $instituteId)->where('is_reversed', false)->count(),
            'total_due'        => (float) TransportAllocation::where('institute_id', $instituteId)
                ->where('is_active', true)
                ->selectRaw('COALESCE(SUM(fee_amount - paid_amount), 0) as due')
                ->value('due'),
            'total_collected'  => (float) TransportPayment::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->sum('amount'),
        ];

        $dueAllocations = TransportAllocation::with(['student:id,name,roll_no', 'route:id,name', 'stop:id,transport_route_id,stop_name'])
            ->where('institute_id', $instituteId)
            ->where('is_active', true)
            ->whereRaw('COALESCE(charged_amount, 0) > COALESCE(paid_amount, 0)')
            ->orderByDesc('id')
            ->get();

        $recentPayments = TransportPayment::with(['student:id,name,roll_no', 'allocation.route:id,name'])
            ->where('institute_id', $instituteId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $expiringVehicles = TransportVehicle::where('institute_id', $instituteId)
            ->where(function ($query) {
                $query->whereBetween('insurance_expiry', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->orWhereBetween('permit_expiry', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->orWhereBetween('fitness_expiry', [now()->toDateString(), now()->addDays(30)->toDateString()])
                    ->orWhereBetween('pollution_expiry', [now()->toDateString(), now()->addDays(30)->toDateString()]);
            })
            ->orderBy('vehicle_no')
            ->get();

        return view('institute.transport.dashboard', compact('summary', 'dueAllocations', 'recentPayments', 'expiringVehicles'));
    }
}
