<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportVehicleType;
use Illuminate\Http\Request;

class TransportVehicleTypeController extends TransportBaseController
{
    public function index()
    {
        $types = TransportVehicleType::withCount('vehicles')
            ->where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->get();

        return view('institute.transport.vehicle-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:80'],
            'default_capacity' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $instituteId = $this->instituteId();

        if (TransportVehicleType::where('institute_id', $instituteId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($data['name']))])
            ->exists()) {
            return back()->withErrors(['name' => 'This vehicle type already exists.'])->withInput();
        }

        TransportVehicleType::create([
            'institute_id'     => $instituteId,
            'name'             => trim($data['name']),
            'default_capacity' => $data['default_capacity'] ?? 0,
            'status'           => true,
        ]);

        return back()->with('success', 'Vehicle type added.');
    }

    public function update(Request $request, TransportVehicleType $vehicleType)
    {
        $this->assertInstituteModel($vehicleType);

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:80'],
            'default_capacity' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        if (TransportVehicleType::where('institute_id', $vehicleType->institute_id)
            ->where('id', '!=', $vehicleType->id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($data['name']))])
            ->exists()) {
            return back()->withErrors(['name' => 'This vehicle type name already exists.'])->withInput();
        }

        $vehicleType->update([
            'name'             => trim($data['name']),
            'default_capacity' => $data['default_capacity'] ?? 0,
        ]);

        return back()->with('success', 'Vehicle type updated.');
    }

    public function toggle(TransportVehicleType $vehicleType)
    {
        $this->assertInstituteModel($vehicleType);
        $vehicleType->update(['status' => !$vehicleType->status]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(TransportVehicleType $vehicleType)
    {
        $this->assertInstituteModel($vehicleType);

        if ($vehicleType->vehicles()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete — vehicles are assigned to this type.']);
        }

        $vehicleType->delete();

        return back()->with('success', 'Vehicle type deleted.');
    }
}
