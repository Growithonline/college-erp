<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\AcademicSession;
use App\Models\TransportDriver;
use App\Models\TransportRoute;
use App\Models\TransportRouteAssignment;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;

class TransportRouteAssignmentController extends TransportBaseController
{
    public function index()
    {
        $assignments = TransportRouteAssignment::with(['route', 'vehicle', 'driver', 'session'])
            ->where('institute_id', $this->instituteId())
            ->orderBy('transport_route_id')
            ->get();

        $routes   = TransportRoute::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->where('status', true)->orderBy('vehicle_no')->get();
        $drivers  = TransportDriver::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get();
        $sessions = AcademicSession::where('institute_id', $this->instituteId())->orderByDesc('id')->get();

        return view('institute.transport.route-assignments.index',
            compact('assignments', 'routes', 'vehicles', 'drivers', 'sessions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transport_route_id'   => ['required', 'exists:transport_routes,id'],
            'transport_vehicle_id' => ['nullable', 'exists:transport_vehicles,id'],
            'transport_driver_id'  => ['nullable', 'exists:transport_drivers,id'],
            'academic_session_id'  => ['nullable', 'exists:academic_sessions,id'],
            'notes'                => ['nullable', 'string', 'max:300'],
        ]);

        $instituteId = $this->instituteId();

        // Duplicate check
        $exists = TransportRouteAssignment::where('institute_id', $instituteId)
            ->where('transport_route_id', $data['transport_route_id'])
            ->where('academic_session_id', $data['academic_session_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->withErrors(['transport_route_id' => 'This route already has an assignment for the selected session.'])->withInput();
        }

        TransportRouteAssignment::create([
            'institute_id'         => $instituteId,
            'transport_route_id'   => $data['transport_route_id'],
            'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
            'transport_driver_id'  => $data['transport_driver_id'] ?? null,
            'academic_session_id'  => $data['academic_session_id'] ?? null,
            'notes'                => $data['notes'] ?? null,
            'status'               => true,
        ]);

        return back()->with('success', 'Route assignment created successfully.');
    }

    public function update(Request $request, TransportRouteAssignment $routeAssignment)
    {
        $this->assertInstituteModel($routeAssignment);

        $data = $request->validate([
            'transport_vehicle_id' => ['nullable', 'exists:transport_vehicles,id'],
            'transport_driver_id'  => ['nullable', 'exists:transport_drivers,id'],
            'notes'                => ['nullable', 'string', 'max:300'],
        ]);

        $routeAssignment->update([
            'transport_vehicle_id' => $data['transport_vehicle_id'] ?? null,
            'transport_driver_id'  => $data['transport_driver_id'] ?? null,
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

    public function toggle(TransportRouteAssignment $routeAssignment)
    {
        $this->assertInstituteModel($routeAssignment);
        $routeAssignment->update(['status' => !$routeAssignment->status]);

        return back()->with('success', 'Assignment status updated.');
    }

    // API: allocation forms call this when route is selected → return vehicle + driver
    public function forRoute(Request $request)
    {
        $routeId   = (int) $request->query('route_id');
        $sessionId = $request->query('session_id') ? (int) $request->query('session_id') : null;

        $assignment = TransportRouteAssignment::forRoute($this->instituteId(), $routeId, $sessionId);

        if (!$assignment) {
            return response()->json(['vehicle_id' => null, 'driver_id' => null, 'vehicle_no' => null, 'driver_name' => null]);
        }

        return response()->json([
            'vehicle_id'   => $assignment->transport_vehicle_id,
            'driver_id'    => $assignment->transport_driver_id,
            'vehicle_no'   => $assignment->vehicle?->vehicle_no,
            'driver_name'  => $assignment->driver?->name,
        ]);
    }
}
