<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryCategory;
use Illuminate\Http\Request;

class LibraryCategoryController extends BaseLibraryController
{
    public function index()
    {
        $this->ensureLibraryPermission('manage');
        $records = LibraryCategory::forInstitute($this->instituteId())
            ->withCount('books')
            ->orderBy('name')
            ->get();

        return view('institute.library.masters.index', [
            'pageTitle' => 'Library Categories',
            'pageIcon' => 'bi-tags',
            'pageDescription' => 'Book classification aur shelf grouping ke liye categories manage karo.',
            'routePrefix' => $this->routeName('categories'),
            'records' => $records,
            'fields' => [
                ['name' => 'name', 'label' => 'Category Name', 'required' => true, 'placeholder' => 'e.g. Science, Commerce'],
                ['name' => 'code', 'label' => 'Code', 'placeholder' => 'e.g. SCI'],
            ],
            'columns' => [
                ['label' => 'Category', 'value' => fn($record) => $record->name],
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

        LibraryCategory::create([
            'institute_id' => $this->instituteId(),
            'name' => trim($request->name),
            'code' => trim((string) $request->code) ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category add ho gayi.');
    }

    public function update(Request $request, LibraryCategory $category)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($category->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
        ]);

        $category->update([
            'name' => trim($request->name),
            'code' => trim((string) $request->code) ?: null,
        ]);

        return back()->with('success', 'Category update ho gayi.');
    }

    public function toggle(LibraryCategory $category)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($category->institute_id !== $this->instituteId(), 403);
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'Category status update ho gaya.');
    }

    public function destroy(LibraryCategory $category)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($category->institute_id !== $this->instituteId(), 403);

        if ($category->books()->exists()) {
            return back()->withErrors(['delete' => 'Is category me books linked hain.']);
        }

        $category->delete();

        return back()->with('success', 'Category delete ho gayi.');
    }
}
