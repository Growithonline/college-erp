<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportVehicle;
use App\Models\TransportVehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TransportVehicleController extends TransportBaseController
{
    public function index()
    {
        $vehicles = TransportVehicle::where('institute_id', $this->instituteId())
            ->orderBy('vehicle_no')
            ->paginate(20);

        return view('institute.transport.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $vehicleTypes = Schema::hasTable('transport_vehicle_types')
            ? TransportVehicleType::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get()
            : collect();

        return view('institute.transport.vehicles.create', compact('vehicleTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transport_vehicle_type_id' => ['nullable', 'exists:transport_vehicle_types,id'],
            'vehicle_no'       => ['required', 'string', 'max:50'],
            'registration_no'  => ['nullable', 'string', 'max:60'],
            'model'            => ['nullable', 'string', 'max:100'],
            'capacity'         => ['nullable', 'integer', 'min:0', 'max:500'],
            'fuel_type'        => ['nullable', 'string', 'max:30'],
            'insurance_expiry' => ['nullable', 'date'],
            'permit_expiry'    => ['nullable', 'date'],
            'fitness_expiry'   => ['nullable', 'date'],
            'pollution_expiry' => ['nullable', 'date'],
            'service_due_date' => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
            'status'           => ['nullable', 'boolean'],
        ]);

        $instituteId = $this->instituteId();

        if (TransportVehicle::where('institute_id', $instituteId)->whereRaw('LOWER(vehicle_no) = ?', [strtolower(trim($data['vehicle_no']))])->exists()) {
            return back()->withInput()->withErrors(['vehicle_no' => 'This vehicle number already exists.']);
        }

        TransportVehicle::create([
            'institute_id'              => $instituteId,
            'transport_vehicle_type_id' => $data['transport_vehicle_type_id'] ?? null,
            'vehicle_no'                => strtoupper(trim($data['vehicle_no'])),
            'registration_no'           => $data['registration_no'] ?? null,
            'model'                     => $data['model'] ?? null,
            'capacity'                  => $data['capacity'] ?? 0,
            'fuel_type'                 => $data['fuel_type'] ?? null,
            'insurance_expiry'          => $data['insurance_expiry'] ?? null,
            'permit_expiry'             => $data['permit_expiry'] ?? null,
            'fitness_expiry'            => $data['fitness_expiry'] ?? null,
            'pollution_expiry'          => $data['pollution_expiry'] ?? null,
            'service_due_date'          => $data['service_due_date'] ?? null,
            'notes'                     => $data['notes'] ?? null,
            'status'                    => $request->boolean('status', true),
        ]);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle added successfully.');
    }

    public function edit(TransportVehicle $vehicle)
    {
        $this->assertInstituteModel($vehicle);
        $vehicleTypes = Schema::hasTable('transport_vehicle_types')
            ? TransportVehicleType::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get()
            : collect();

        return view('institute.transport.vehicles.edit', compact('vehicle', 'vehicleTypes'));
    }

    public function update(Request $request, TransportVehicle $vehicle)
    {
        $this->assertInstituteModel($vehicle);

        $data = $request->validate([
            'transport_vehicle_type_id' => ['nullable', 'exists:transport_vehicle_types,id'],
            'vehicle_no'       => ['required', 'string', 'max:50'],
            'registration_no'  => ['nullable', 'string', 'max:60'],
            'model'            => ['nullable', 'string', 'max:100'],
            'capacity'         => ['nullable', 'integer', 'min:0', 'max:500'],
            'fuel_type'        => ['nullable', 'string', 'max:30'],
            'insurance_expiry' => ['nullable', 'date'],
            'permit_expiry'    => ['nullable', 'date'],
            'fitness_expiry'   => ['nullable', 'date'],
            'pollution_expiry' => ['nullable', 'date'],
            'service_due_date' => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
            'status'           => ['nullable', 'boolean'],
        ]);

        if (TransportVehicle::where('institute_id', $vehicle->institute_id)
            ->where('id', '!=', $vehicle->id)
            ->whereRaw('LOWER(vehicle_no) = ?', [strtolower(trim($data['vehicle_no']))])
            ->exists()) {
            return back()->withInput()->withErrors(['vehicle_no' => 'This vehicle number already exists.']);
        }

        $vehicle->update([
            'transport_vehicle_type_id' => $data['transport_vehicle_type_id'] ?? null,
            'vehicle_no'                => strtoupper(trim($data['vehicle_no'])),
            'registration_no'           => $data['registration_no'] ?? null,
            'model'                     => $data['model'] ?? null,
            'capacity'                  => $data['capacity'] ?? 0,
            'fuel_type'                 => $data['fuel_type'] ?? null,
            'insurance_expiry'          => $data['insurance_expiry'] ?? null,
            'permit_expiry'             => $data['permit_expiry'] ?? null,
            'fitness_expiry'            => $data['fitness_expiry'] ?? null,
            'pollution_expiry'          => $data['pollution_expiry'] ?? null,
            'service_due_date'          => $data['service_due_date'] ?? null,
            'notes'                     => $data['notes'] ?? null,
            'status'                    => $request->boolean('status', true),
        ]);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(TransportVehicle $vehicle)
    {
        $this->assertInstituteModel($vehicle);
        abort_if($vehicle->allocations()->where('is_active', true)->exists(), 422, 'Vehicle is assigned to active transport allocation.');

        $vehicle->delete();

        return back()->with('success', 'Vehicle deleted successfully.');
    }

    public function toggle(TransportVehicle $vehicle)
    {
        $this->assertInstituteModel($vehicle);
        $vehicle->update(['status' => !$vehicle->status]);

        return back()->with('success', 'Vehicle status updated.');
    }
}
