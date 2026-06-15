<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportMaintenanceLog;
use App\Models\TransportVehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransportMaintenanceController extends TransportBaseController
{
    public function index(Request $request)
    {
        $query = TransportMaintenanceLog::with(['vehicle:id,vehicle_no,model'])
            ->where('institute_id', $this->instituteId())
            ->orderByDesc('service_date')
            ->orderByDesc('id');

        if ($request->filled('vehicle_id')) {
            $query->where('transport_vehicle_id', $request->integer('vehicle_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $logs = $query->paginate(20)->withQueryString();
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->orderBy('vehicle_no')->get(['id', 'vehicle_no']);

        return view('institute.transport.maintenance.index', compact('logs', 'vehicles'));
    }

    public function create()
    {
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())->orderBy('vehicle_no')->get(['id', 'vehicle_no', 'model']);

        return view('institute.transport.maintenance.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transport_vehicle_id' => ['required', Rule::exists('transport_vehicles', 'id')->where('institute_id', $this->instituteId())],
            'service_date' => ['required', 'date'],
            'next_service_due' => ['nullable', 'date', 'after_or_equal:service_date'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'service_type' => ['nullable', 'string', 'max:80'],
            'garage_name' => ['nullable', 'string', 'max:120'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
            'issues_found' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        TransportMaintenanceLog::create([
            'transport_vehicle_id' => $data['transport_vehicle_id'],
            'institute_id' => $this->instituteId(),
            'service_date' => $data['service_date'],
            'next_service_due' => $data['next_service_due'] ?? null,
            'odometer_km' => $data['odometer_km'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'garage_name' => $data['garage_name'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'status' => $data['status'] ?? 'completed',
            'issues_found' => $data['issues_found'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'by_user_id' => auth()->id(),
        ]);

        if (!empty($data['next_service_due'])) {
            TransportVehicle::where('institute_id', $this->instituteId())
                ->where('id', $data['transport_vehicle_id'])
                ->update(['service_due_date' => $data['next_service_due']]);
        }

        return redirect()->route('transport.maintenance.index')->with('success', 'Maintenance log saved successfully.');
    }

    public function destroy(TransportMaintenanceLog $maintenance)
    {
        abort_unless($maintenance->institute_id === $this->instituteId(), 403);
        $maintenance->delete();

        return back()->with('success', 'Maintenance log deleted successfully.');
    }
}
