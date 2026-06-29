<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Models\TransportDriver;
use App\Models\TransportDriverDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $documentTypes = TransportDriverDocument::$types;
        return view('institute.transport.drivers.create', compact('documentTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'mobile'        => ['nullable', 'digits:10'],
            'license_no'    => ['nullable', 'string', 'max:80'],
            'helper_name'   => ['nullable', 'string', 'max:120'],
            'helper_mobile' => ['nullable', 'digits:10'],
            'notes'         => ['nullable', 'string'],
            'status'        => ['nullable', 'boolean'],
            'documents'                 => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents.*.file', 'string', 'max:50'],
            'documents.*.document_name' => ['nullable', 'string', 'max:150'],
            'documents.*.file'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:200'],
            'documents.*.expiry_date'   => ['nullable', 'date'],
            'documents.*.doc_notes'     => ['nullable', 'string', 'max:300'],
        ]);

        $instituteId = $this->instituteId();

        $driver = TransportDriver::create([
            'institute_id'  => $instituteId,
            'name'          => trim($data['name']),
            'mobile'        => $data['mobile'] ?? null,
            'license_no'    => $data['license_no'] ?? null,
            'helper_name'   => $data['helper_name'] ?? null,
            'helper_mobile' => $data['helper_mobile'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => $request->boolean('status', true),
        ]);

        $this->saveDocuments($request, $driver, $instituteId);

        return redirect()->route('transport.drivers.index')->with('success', 'Driver added successfully.');
    }

    public function edit(TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);
        $documentTypes     = TransportDriverDocument::$types;
        $existingDocuments = $driver->documents()->orderBy('document_type')->get();

        return view('institute.transport.drivers.edit', compact('driver', 'documentTypes', 'existingDocuments'));
    }

    public function update(Request $request, TransportDriver $driver)
    {
        $this->assertInstituteModel($driver);

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'mobile'        => ['nullable', 'digits:10'],
            'license_no'    => ['nullable', 'string', 'max:80'],
            'helper_name'   => ['nullable', 'string', 'max:120'],
            'helper_mobile' => ['nullable', 'digits:10'],
            'notes'         => ['nullable', 'string'],
            'status'        => ['nullable', 'boolean'],
            'documents'                 => ['nullable', 'array'],
            'documents.*.document_type' => ['required_with:documents.*.file', 'string', 'max:50'],
            'documents.*.document_name' => ['nullable', 'string', 'max:150'],
            'documents.*.file'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:200'],
            'documents.*.expiry_date'   => ['nullable', 'date'],
            'documents.*.doc_notes'     => ['nullable', 'string', 'max:300'],
        ]);

        $driver->update([
            'name'          => trim($data['name']),
            'mobile'        => $data['mobile'] ?? null,
            'license_no'    => $data['license_no'] ?? null,
            'helper_name'   => $data['helper_name'] ?? null,
            'helper_mobile' => $data['helper_mobile'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'status'        => $request->boolean('status', true),
        ]);

        $this->saveDocuments($request, $driver, $driver->institute_id);

        return redirect()->route('transport.drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function deleteDocument(TransportDriver $driver, TransportDriverDocument $document)
    {
        $this->assertInstituteModel($driver);
        abort_if($document->transport_driver_id !== $driver->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
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

    private function saveDocuments(Request $request, TransportDriver $driver, int $instituteId): void
    {
        $rows  = $request->input('documents', []);
        $files = $request->file('documents', []);

        foreach ($rows as $i => $row) {
            $file = $files[$i]['file'] ?? null;
            if (!$file) continue;

            $docType = $row['document_type'] ?? '';
            if (!$docType) continue;

            $path = $file->store("transport/drivers/{$driver->id}/documents", 'public');

            TransportDriverDocument::create([
                'institute_id'        => $instituteId,
                'transport_driver_id' => $driver->id,
                'document_type'       => $docType,
                'document_name'       => $row['document_name'] ?? null,
                'file_path'           => $path,
                'original_name'       => $file->getClientOriginalName(),
                'expiry_date'         => $row['expiry_date'] ?? null,
                'notes'               => $row['doc_notes'] ?? null,
            ]);
        }
    }
}
