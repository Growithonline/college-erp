<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportDriver;
use Illuminate\Http\Request;

class TransportDriverController extends TransportBaseController
{
    public function index()
    {
        $drivers = TransportDriver::where('institute_id', $this->instituteId())
            ->orderBy('name')
            ->paginate(20);

        return view('institute.transport.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('institute.transport.drivers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['nullable', 'digits:10'],
            'license_no' => ['nullable', 'string', 'max:80'],
            'license_expiry' => ['nullable', 'date'],
            'helper_name' => ['nullable', 'string', 'max:120'],
            'helper_mobile' => ['nullable', 'digits:10'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        TransportDriver::create([
            'institute_id' => $this->instituteId(),
            'name' => trim($data['name']),
            'mobile' => $data['mobile'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'helper_name' => $data['helper_name'] ?? null,
            'helper_mobile' => $data['helper_mobile'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $request->boolean('status', true),
        ]);

        return redirect()->route('transport.drivers.index')->with('success', 'Driver added successfully.');
    }

    public function edit(TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);

        return view('institute.transport.drivers.edit', compact('driver'));
    }

    public function update(Request $request, TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['nullable', 'digits:10'],
            'license_no' => ['nullable', 'string', 'max:80'],
            'license_expiry' => ['nullable', 'date'],
            'helper_name' => ['nullable', 'string', 'max:120'],
            'helper_mobile' => ['nullable', 'digits:10'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        $driver->update([
            'name' => trim($data['name']),
            'mobile' => $data['mobile'] ?? null,
            'license_no' => $data['license_no'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'helper_name' => $data['helper_name'] ?? null,
            'helper_mobile' => $data['helper_mobile'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $request->boolean('status', true),
        ]);

        return redirect()->route('transport.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);
        abort_if($driver->allocations()->where('is_active', true)->exists(), 422, 'Driver is assigned to active transport allocation.');

        $driver->delete();

        return back()->with('success', 'Driver deleted successfully.');
    }

    public function toggle(TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);
        $driver->update(['status' => !$driver->status]);

        return back()->with('success', 'Driver status updated.');
    }
}
