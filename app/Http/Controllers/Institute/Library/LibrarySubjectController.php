<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibrarySubject;
use Illuminate\Http\Request;

class LibrarySubjectController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibrarySubject::forInstitute($this->instituteId())
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Subjects',
            'pageIcon' => 'bi-journal-text',
            'pageDescription' => 'Subject-wise cataloging aur OPAC discovery ke liye subject master maintain karo.',
            'routePrefix' => $this->routeName('subjects'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Subject Name', 'required' => true, 'placeholder' => 'e.g. Physics'],
                ['name' => 'code', 'label' => 'Code', 'placeholder' => 'e.g. PHY'],
            ],
            'columns' => [
                ['label' => 'Subject', 'value' => fn($record) => $record->name],
                ['label' => 'Code', 'value' => fn($record) => $record->code ?: '-'],
                ['label' => 'Books', 'value' => fn($record) => $record->books_count],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
        ]);

        LibrarySubject::create([
            'institute_id' => $this->instituteId(),
            'name' => trim((string) $request->name),
            'code' => trim((string) $request->code) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Subject add ho gaya.');
    }

    public function update(Request $request, LibrarySubject $subject)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($subject->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
        ]);

        $subject->update([
            'name' => trim((string) $request->name),
            'code' => trim((string) $request->code) ?: null,
        ]);

        return back()->with('success', 'Subject update ho gaya.');
    }

    public function toggle(LibrarySubject $subject)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($subject->institute_id !== $this->instituteId(), 403);
        $subject->update(['is_active' => !$subject->is_active]);

        return back()->with('success', 'Subject status update ho gaya.');
    }

    public function destroy(LibrarySubject $subject)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($subject->institute_id !== $this->instituteId(), 403);

        if ($subject->books()->exists()) {
            return back()->withErrors(['delete' => 'Is subject se books linked hain.']);
        }

        $subject->delete();

        return back()->with('success', 'Subject delete ho gaya.');
    }
}
