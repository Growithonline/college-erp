<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportVehicle;
use App\Models\TransportVehicleDocument;
use App\Models\TransportVehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

        $documentTypes = TransportVehicleDocument::$types;

        return view('institute.transport.vehicles.create', compact('vehicleTypes', 'documentTypes'));
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
            'notes'            => ['nullable', 'string'],
            'status'           => ['nullable', 'boolean'],
            'documents'                    => ['nullable', 'array'],
            'documents.*.document_type'    => ['required_with:documents.*.file', 'string', 'max:50'],
            'documents.*.document_name'    => ['nullable', 'string', 'max:150'],
            'documents.*.file'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.*.expiry_date'      => ['nullable', 'date'],
            'documents.*.doc_notes'        => ['nullable', 'string', 'max:300'],
        ]);

        $instituteId = $this->instituteId();

        if (TransportVehicle::where('institute_id', $instituteId)->whereRaw('LOWER(vehicle_no) = ?', [strtolower(trim($data['vehicle_no']))])->exists()) {
            return back()->withInput()->withErrors(['vehicle_no' => 'This vehicle number already exists.']);
        }

        $vehicle = TransportVehicle::create([
            'institute_id'              => $instituteId,
            'transport_vehicle_type_id' => $data['transport_vehicle_type_id'] ?? null,
            'vehicle_no'                => strtoupper(trim($data['vehicle_no'])),
            'registration_no'           => $data['registration_no'] ?? null,
            'model'                     => $data['model'] ?? null,
            'capacity'                  => $data['capacity'] ?? 0,
            'fuel_type'                 => $data['fuel_type'] ?? null,
            'notes'                     => $data['notes'] ?? null,
            'status'                    => $request->boolean('status', true),
        ]);

        $this->saveDocuments($request, $vehicle, $instituteId);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle added successfully.');
    }

    public function edit(TransportVehicle $vehicle)
    {
        $this->assertInstituteModel($vehicle);
        $vehicleTypes = Schema::hasTable('transport_vehicle_types')
            ? TransportVehicleType::where('institute_id', $this->instituteId())->where('status', true)->orderBy('name')->get()
            : collect();

        $documentTypes     = TransportVehicleDocument::$types;
        $existingDocuments = $vehicle->documents()->orderBy('document_type')->get();

        return view('institute.transport.vehicles.edit', compact('vehicle', 'vehicleTypes', 'documentTypes', 'existingDocuments'));
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
            'notes'            => ['nullable', 'string'],
            'status'           => ['nullable', 'boolean'],
            'documents'                    => ['nullable', 'array'],
            'documents.*.document_type'    => ['required_with:documents.*.file', 'string', 'max:50'],
            'documents.*.document_name'    => ['nullable', 'string', 'max:150'],
            'documents.*.file'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'documents.*.expiry_date'      => ['nullable', 'date'],
            'documents.*.doc_notes'        => ['nullable', 'string', 'max:300'],
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
            'notes'                     => $data['notes'] ?? null,
            'status'                    => $request->boolean('status', true),
        ]);

        $this->saveDocuments($request, $vehicle, $vehicle->institute_id);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function deleteDocument(TransportVehicle $vehicle, TransportVehicleDocument $document)
    {
        $this->assertInstituteModel($vehicle);
        abort_if($document->transport_vehicle_id !== $vehicle->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    private function saveDocuments(Request $request, TransportVehicle $vehicle, int $instituteId): void
    {
        $rows = $request->input('documents', []);
        $files = $request->file('documents', []);

        foreach ($rows as $i => $row) {
            $file = $files[$i]['file'] ?? null;
            if (!$file) continue;

            $docType = $row['document_type'] ?? '';
            if (!$docType) continue;

            $path = $file->store("transport/vehicles/{$vehicle->id}/documents", 'public');

            TransportVehicleDocument::create([
                'institute_id'          => $instituteId,
                'transport_vehicle_id'  => $vehicle->id,
                'document_type'         => $docType,
                'document_name'         => $row['document_name'] ?? null,
                'file_path'             => $path,
                'original_name'         => $file->getClientOriginalName(),
                'expiry_date'           => $row['expiry_date'] ?? null,
                'notes'                 => $row['doc_notes'] ?? null,
            ]);
        }
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
