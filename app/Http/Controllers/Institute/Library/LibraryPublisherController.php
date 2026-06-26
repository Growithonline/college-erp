<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryPublisher;
use Illuminate\Http\Request;

class LibraryPublisherController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryPublisher::forInstitute($this->instituteId())
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Publishers',
            'pageIcon' => 'bi-buildings',
            'pageDescription' => 'Manage publisher contact details for book records.',
            'routePrefix' => $this->routeName('publishers'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Publisher Name', 'required' => true, 'placeholder' => 'e.g. Oxford University Press'],
                ['name' => 'mobile', 'label' => 'Mobile', 'placeholder' => 'Optional'],
                ['name' => 'email', 'label' => 'Email', 'placeholder' => 'Optional'],
                ['name' => 'address', 'label' => 'Address', 'placeholder' => 'Optional'],
            ],
            'columns' => [
                ['label' => 'Publisher', 'value' => fn($record) => $record->name],
                ['label' => 'Contact', 'value' => fn($record) => $record->mobile ?: ($record->email ?: '-')],
                ['label' => 'Books', 'value' => fn($record) => $record->books_count],
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

        LibraryPublisher::create([
            'institute_id' => $this->instituteId(),
            'name' => trim($request->name),
            'mobile' => trim((string) $request->mobile) ?: null,
            'email' => trim((string) $request->email) ?: null,
            'address' => trim((string) $request->address) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Publisher added successfully.');
    }

    public function update(Request $request, LibraryPublisher $publisher)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($publisher->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);

        $publisher->update([
            'name' => trim($request->name),
            'mobile' => trim((string) $request->mobile) ?: null,
            'email' => trim((string) $request->email) ?: null,
            'address' => trim((string) $request->address) ?: null,
        ]);

        return back()->with('success', 'Publisher updated successfully.');
    }

    public function toggle(LibraryPublisher $publisher)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($publisher->institute_id !== $this->instituteId(), 403);
        $publisher->update(['is_active' => !$publisher->is_active]);

        return back()->with('success', 'Publisher status updated.');
    }

    public function destroy(LibraryPublisher $publisher)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($publisher->institute_id !== $this->instituteId(), 403);

        if ($publisher->books()->exists()) {
            return back()->withErrors(['delete' => 'This publisher is linked to one or more books and cannot be deleted.']);
        }

        $publisher->delete();

        return back()->with('success', 'Publisher deleted.');
    }
}
