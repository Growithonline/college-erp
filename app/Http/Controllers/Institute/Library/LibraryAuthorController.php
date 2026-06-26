<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryAuthor;
use Illuminate\Http\Request;

class LibraryAuthorController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryAuthor::forInstitute($this->instituteId())
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Authors',
            'pageIcon' => 'bi-pen',
            'pageDescription' => 'Manage the master list of book authors.',
            'routePrefix' => $this->routeName('authors'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Author Name', 'required' => true, 'placeholder' => 'e.g. R.K. Narayan'],
            ],
            'columns' => [
                ['label' => 'Author', 'value' => fn($record) => $record->name],
                ['label' => 'Books', 'value' => fn($record) => $record->books_count],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $request->validate(['name' => 'required|string|max:150']);

        LibraryAuthor::create([
            'institute_id' => $this->instituteId(),
            'name' => trim($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Author added successfully.');
    }

    public function update(Request $request, LibraryAuthor $author)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($author->institute_id !== $this->instituteId(), 403);
        $request->validate(['name' => 'required|string|max:150']);
        $author->update(['name' => trim($request->name)]);

        return back()->with('success', 'Author updated successfully.');
    }

    public function toggle(LibraryAuthor $author)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($author->institute_id !== $this->instituteId(), 403);
        $author->update(['is_active' => !$author->is_active]);

        return back()->with('success', 'Author status updated.');
    }

    public function destroy(LibraryAuthor $author)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($author->institute_id !== $this->instituteId(), 403);

        if ($author->books()->exists()) {
            return back()->withErrors(['delete' => 'This author is linked to one or more books and cannot be deleted.']);
        }

        $author->delete();

        return back()->with('success', 'Author deleted.');
    }
}
