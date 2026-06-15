<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use Illuminate\Http\Request;

class DocumentCategoryController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index()
    {
        $categories = DocumentCategory::forInstitute($this->instituteId())
            ->withCount('documentTypes')
            ->orderBy('name')
            ->get();

        return view('institute.master.document-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);

        $instituteId = $this->instituteId();

        $exists = DocumentCategory::forInstitute($instituteId)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'This category already exists.'])->withInput();
        }

        DocumentCategory::create([
            'institute_id' => $instituteId,
            'name'         => strtoupper(trim($request->name)),
            'status'       => true,
        ]);

        return back()->with('success', 'Document category added!');
    }

    public function update(Request $request, DocumentCategory $documentCategory)
    {
        abort_if($documentCategory->institute_id !== $this->instituteId(), 403);

        $request->validate(['name' => 'required|string|max:100']);

        $exists = DocumentCategory::forInstitute($this->instituteId())
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->where('id', '!=', $documentCategory->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name_' . $documentCategory->id => 'This name already exists.'])->withInput();
        }

        $documentCategory->update(['name' => strtoupper(trim($request->name))]);

        return back()->with('success', 'Category updated!');
    }

    public function toggle(DocumentCategory $documentCategory)
    {
        abort_if($documentCategory->institute_id !== $this->instituteId(), 403);

        $documentCategory->update(['status' => !$documentCategory->status]);

        return back()->with('success', 'Status updated!');
    }

    public function destroy(DocumentCategory $documentCategory)
    {
        abort_if($documentCategory->institute_id !== $this->instituteId(), 403);

        if ($documentCategory->documentTypes()->exists()) {
            return back()->withErrors(['delete' => 'Delete all document types in this category first.']);
        }

        $documentCategory->delete();

        return back()->with('success', 'Category deleted!');
    }
}
