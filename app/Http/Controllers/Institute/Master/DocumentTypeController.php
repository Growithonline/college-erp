<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index()
    {
        $categories = DocumentCategory::forInstitute($this->instituteId())
            ->active()
            ->with('documentTypes')
            ->orderBy('name')
            ->get();

        $allCategories = DocumentCategory::forInstitute($this->instituteId())
            ->active()
            ->orderBy('name')
            ->get();

        return view('institute.master.document-types.index', compact('categories', 'allCategories'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $request->validate([
            'document_category_id' => 'required|exists:document_categories,id',
            'name'                 => 'required|string|max:150',
            'max_size_kb'          => 'required|integer|min:50|max:10240',
            'allowed_formats'      => 'required|array|min:1',
            'allowed_formats.*'    => 'in:pdf,jpg,jpeg,png,doc,docx',
        ]);

        // Verify category belongs to this institute
        $cat = DocumentCategory::where('id', $request->document_category_id)
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        DocumentType::create([
            'institute_id'         => $instituteId,
            'document_category_id' => $cat->id,
            'name'                 => strtoupper(trim($request->name)),
            'max_size_kb'          => $request->max_size_kb,
            'allowed_formats'      => implode(',', $request->allowed_formats),
            'status'               => true,
        ]);

        return back()->with('success', 'Document type added!');
    }

    public function update(Request $request, DocumentType $documentType)
    {
        abort_if($documentType->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'document_category_id' => 'required|exists:document_categories,id',
            'name'                 => 'required|string|max:150',
            'max_size_kb'          => 'required|integer|min:50|max:10240',
            'allowed_formats'      => 'required|array|min:1',
            'allowed_formats.*'    => 'in:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $documentType->update([
            'document_category_id' => $request->document_category_id,
            'name'                 => strtoupper(trim($request->name)),
            'max_size_kb'          => $request->max_size_kb,
            'allowed_formats'      => implode(',', $request->allowed_formats),
        ]);

        return back()->with('success', 'Document type updated!');
    }

    public function toggle(DocumentType $documentType)
    {
        abort_if($documentType->institute_id !== $this->instituteId(), 403);

        $documentType->update(['status' => !$documentType->status]);

        return back()->with('success', 'Status updated!');
    }

    public function destroy(DocumentType $documentType)
    {
        abort_if($documentType->institute_id !== $this->instituteId(), 403);

        if ($documentType->admissionDocs()->exists()) {
            return back()->withErrors(['delete' => 'Students have uploaded documents of this type. Cannot delete.']);
        }

        $documentType->uploadRules()->delete();
        $documentType->delete();

        return back()->with('success', 'Document type deleted!');
    }
}
