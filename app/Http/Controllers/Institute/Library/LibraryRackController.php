<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryRack;
use Illuminate\Http\Request;

class LibraryRackController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryRack::forInstitute($this->instituteId())
            ->withCount('copies')
            ->orderBy('room_name')
            ->orderBy('rack_code')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Racks & Shelves',
            'pageIcon' => 'bi-grid-3x3-gap',
            'pageDescription' => 'Physical shelf map maintain karo taaki copies trace ho sakein.',
            'routePrefix' => $this->routeName('racks'),
            'records' => $records,
            'fields' => [
                ['name' => 'room_name', 'label' => 'Room', 'placeholder' => 'e.g. Main Hall'],
                ['name' => 'rack_code', 'label' => 'Rack Code', 'required' => true, 'placeholder' => 'e.g. R-01'],
                ['name' => 'shelf_code', 'label' => 'Shelf Code', 'placeholder' => 'e.g. S-02'],
                ['name' => 'remarks', 'label' => 'Remarks', 'placeholder' => 'Optional'],
            ],
            'columns' => [
                ['label' => 'Rack', 'value' => fn($record) => $record->display_name],
                ['label' => 'Copies', 'value' => fn($record) => $record->copies_count],
                ['label' => 'Remarks', 'value' => fn($record) => $record->remarks ?: '-'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $request->validate([
            'room_name' => 'nullable|string|max:100',
            'rack_code' => 'required|string|max:50',
            'shelf_code' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:255',
        ]);

        LibraryRack::create([
            'institute_id' => $this->instituteId(),
            'room_name' => trim((string) $request->room_name) ?: null,
            'rack_code' => trim($request->rack_code),
            'shelf_code' => trim((string) $request->shelf_code) ?: null,
            'remarks' => trim((string) $request->remarks) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Rack add ho gaya.');
    }

    public function update(Request $request, LibraryRack $rack)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rack->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'room_name' => 'nullable|string|max:100',
            'rack_code' => 'required|string|max:50',
            'shelf_code' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:255',
        ]);

        $rack->update([
            'room_name' => trim((string) $request->room_name) ?: null,
            'rack_code' => trim($request->rack_code),
            'shelf_code' => trim((string) $request->shelf_code) ?: null,
            'remarks' => trim((string) $request->remarks) ?: null,
        ]);

        return back()->with('success', 'Rack update ho gaya.');
    }

    public function toggle(LibraryRack $rack)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rack->institute_id !== $this->instituteId(), 403);
        $rack->update(['is_active' => !$rack->is_active]);

        return back()->with('success', 'Rack status update ho gaya.');
    }

    public function destroy(LibraryRack $rack)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($rack->institute_id !== $this->instituteId(), 403);

        if ($rack->copies()->exists()) {
            return back()->withErrors(['delete' => 'Is rack me copies mapped hain.']);
        }

        $rack->delete();

        return back()->with('success', 'Rack delete ho gaya.');
    }
}
