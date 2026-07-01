<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportDriver;
use App\Models\TransportHelper;
use App\Models\TransportRoute;
use App\Models\TransportRouteAssignment;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportRouteAssignmentController extends TransportBaseController
{
    public function index()
    {
        $instituteId = $this->instituteId();

        // Current active assignments (end_date IS NULL)
        $current = TransportRouteAssignment::with(['route', 'vehicle', 'driver', 'helper'])
            ->where('institute_id', $instituteId)
            ->whereNull('end_date')
            ->orderBy('transport_route_id')
            ->get();

        // History: closed assignments (end_date IS NOT NULL), latest 50
        $history = TransportRouteAssignment::with(['route', 'vehicle', 'driver', 'helper'])
            ->where('institute_id', $instituteId)
            ->whereNotNull('end_date')
            ->orderByDesc('end_date')
            ->limit(50)
            ->get();

        $routes   = TransportRoute::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $vehicles = TransportVehicle::where('institute_id', $instituteId)->where('status', true)->orderBy('vehicle_no')->get();
        $drivers  = TransportDriver::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $helpers  = TransportHelper::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();

        return view('institute.transport.route-assignments.index',
            compact('current', 'history', 'routes', 'vehicles', 'drivers', 'helpers'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'transport_route_id'   => ['required', Rule::exists('transport_routes', 'id')->where('institute_id', $instituteId)],
            'transport_vehicle_id' => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $instituteId)],
            'transport_driver_id'  => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $instituteId)],
            'transport_helper_id'  => ['nullable', Rule::exists('transport_helpers', 'id')->where('institute_id', $instituteId)],
            'start_date'           => ['required', 'date'],
            'notes'                => ['nullable', 'string', 'max:300'],
        ]);

        // Close existing active assignment for this route (if any)
        $existing = TransportRouteAssignment::where('institute_id', $instituteId)
            ->where('transport_route_id', $data['transport_route_id'])
            ->whereNull('end_date')
            ->first();

        if ($existing) {
            $closeDate = date('Y-m-d', strtotime($data['start_date'] . ' -1 day'));
            $existing->update(['end_date' => $closeDate]);
        }

        TransportRouteAssignment::create([
            'institute_id'         => $instituteId,
            'transport_route_id'   => $data['transport_route_id'],
            'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
            'transport_driver_id'  => $data['transport_driver_id'] ?? null,
            'transport_helper_id'  => $data['transport_helper_id'] ?? null,
            'start_date'           => $data['start_date'],
            'end_date'             => null,
            'notes'                => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Route assignment saved. Previous assignment closed automatically.');
    }

    public function update(Request $request, TransportRouteAssignment $routeAssignment)
    {
        $this->assertInstituteModel($routeAssignment);
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'transport_vehicle_id' => ['nullable', Rule::exists('transport_vehicles', 'id')->where('institute_id', $instituteId)],
            'transport_driver_id'  => ['nullable', Rule::exists('transport_drivers', 'id')->where('institute_id', $instituteId)],
            'transport_helper_id'  => ['nullable', Rule::exists('transport_helpers', 'id')->where('institute_id', $instituteId)],
            'notes'                => ['nullable', 'string', 'max:300'],
        ]);

        $routeAssignment->update([
            'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
            'transport_driver_id'  => $data['transport_driver_id'] ?? null,
            'transport_helper_id'  => $data['transport_helper_id'] ?? null,
            'notes'                => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Assignment updated successfully.');
    }

    public function destroy(TransportRouteAssignment $routeAssignment)
    {
        $this->assertInstituteModel($routeAssignment);
        $routeAssignment->delete();

        return back()->with('success', 'Assignment removed.');
    }

    // API: admission form calls this when route is selected
    public function forRoute(Request $request)
    {
        $routeId = (int) $request->query('route_id');

        if (!$routeId) {
            return response()->json(['vehicle_id' => null, 'driver_id' => null, 'helper_id' => null]);
        }

        $assignment = TransportRouteAssignment::forRoute($this->instituteId(), $routeId);

        if (!$assignment) {
            return response()->json(['vehicle_id' => null, 'driver_id' => null, 'helper_id' => null]);
        }

        return response()->json([
            'vehicle_id'   => $assignment->transport_vehicle_id,
            'driver_id'    => $assignment->transport_driver_id,
            'helper_id'    => $assignment->transport_helper_id,
            'vehicle_no'   => $assignment->vehicle?->vehicle_no,
            'driver_name'  => $assignment->driver?->name,
            'helper_name'  => $assignment->helper?->name,
        ]);
    }
}
