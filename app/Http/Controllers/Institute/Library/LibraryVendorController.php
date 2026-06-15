<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryVendor;
use Illuminate\Http\Request;

class LibraryVendorController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryVendor::forInstitute($this->instituteId())
            ->withCount('copies')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Vendors',
            'pageIcon' => 'bi-truck',
            'pageDescription' => 'Book procurement aur stock source tracking ke liye vendor master manage karo.',
            'routePrefix' => $this->routeName('vendors'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Vendor Name', 'required' => true, 'placeholder' => 'e.g. ABC Book Depot'],
                ['name' => 'mobile', 'label' => 'Mobile', 'placeholder' => 'Optional'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Optional'],
                ['name' => 'address', 'label' => 'Address', 'placeholder' => 'Optional'],
            ],
            'columns' => [
                ['label' => 'Vendor', 'value' => fn($record) => $record->name],
                ['label' => 'Contact', 'value' => fn($record) => $record->mobile ?: ($record->email ?: '-')],
                ['label' => 'Copies', 'value' => fn($record) => $record->copies_count],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        LibraryVendor::create([
            'institute_id' => $this->instituteId(),
            'name' => trim((string) $request->name),
            'mobile' => trim((string) $request->mobile) ?: null,
            'email' => trim((string) $request->email) ?: null,
            'address' => trim((string) $request->address) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Vendor add ho gaya.');
    }

    public function update(Request $request, LibraryVendor $vendor)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($vendor->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $vendor->update([
            'name' => trim((string) $request->name),
            'mobile' => trim((string) $request->mobile) ?: null,
            'email' => trim((string) $request->email) ?: null,
            'address' => trim((string) $request->address) ?: null,
        ]);

        return back()->with('success', 'Vendor update ho gaya.');
    }

    public function toggle(LibraryVendor $vendor)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($vendor->institute_id !== $this->instituteId(), 403);
        $vendor->update(['is_active' => !$vendor->is_active]);

        return back()->with('success', 'Vendor status update ho gaya.');
    }

    public function destroy(LibraryVendor $vendor)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($vendor->institute_id !== $this->instituteId(), 403);

        if ($vendor->copies()->exists()) {
            return back()->withErrors(['delete' => 'Is vendor se copies linked hain.']);
        }

        $vendor->delete();

        return back()->with('success', 'Vendor delete ho gaya.');
    }
}
