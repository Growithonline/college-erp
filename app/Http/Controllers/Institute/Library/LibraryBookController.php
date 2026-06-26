<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryAuthor;
use App\Models\Library\LibraryBook;
use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryCategory;
use App\Models\Library\LibraryPublisher;
use App\Models\Library\LibraryRack;
use App\Models\Library\LibrarySubject;
use App\Models\Library\LibraryTransaction;
use App\Models\Library\LibraryVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LibraryBookController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('view');
        $instituteId = $this->instituteId();
        $search     = trim((string) $request->input('search', ''));
        $searchLike = $this->escapeLike($search);

        $books = LibraryBook::forInstitute($instituteId)
            ->with(['category', 'publisher', 'subject', 'authors', 'copies'])
            ->when($search !== '', function ($query) use ($searchLike) {
                $query->where(function ($builder) use ($searchLike) {
                    $builder->where('title', 'like', '%' . $searchLike . '%')
                        ->orWhere('isbn', 'like', '%' . $searchLike . '%')
                        ->orWhere('subject_name', 'like', '%' . $searchLike . '%')
                        ->orWhere('author_text', 'like', '%' . $searchLike . '%');
                });
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('institute.library.books.index', compact('books', 'search'));
    }

    public function create()
    {
        $this->ensureLibraryPermission('manage');
        return view('institute.library.books.form', $this->formData(new LibraryBook(), 'store'));
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('manage');
        $data = $this->validatedBookData($request);
        $data['institute_id'] = $this->instituteId();
        $data['is_active'] = true;

        $book = LibraryBook::create($data);
        $book->authors()->sync($request->input('author_ids', []));

        return $this->redirectRoute('books.show', $book)->with('success', 'Book saved. You can now add copies.');
    }

    public function show(LibraryBook $book)
    {
        $this->ensureLibraryPermission('view');
        abort_if($book->institute_id !== $this->instituteId(), 403);

        $book->load(['category', 'publisher', 'subject', 'authors', 'copies.rack', 'copies.vendor']);
        $transactions = LibraryTransaction::forInstitute($this->instituteId())
            ->with(['member', 'copy.book'])
            ->whereHas('copy', fn($query) => $query->where('book_id', $book->id))
            ->latest('id')
            ->limit(20)
            ->get();

        return view('institute.library.books.show', [
            'book' => $book,
            'racks' => LibraryRack::forInstitute($this->instituteId())->where('is_active', true)->orderBy('rack_code')->get(),
            'vendors' => LibraryVendor::forInstitute($this->instituteId())->where('is_active', true)->orderBy('name')->get(),
            'transactions' => $transactions,
        ]);
    }

    public function labels(LibraryBook $book)
    {
        $this->ensureLibraryPermission('view');
        abort_if($book->institute_id !== $this->instituteId(), 403);

        $book->load(['copies.rack', 'authors']);

        return view('institute.library.books.labels', compact('book'));
    }

    public function edit(LibraryBook $book)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($book->institute_id !== $this->instituteId(), 403);

        return view('institute.library.books.form', $this->formData($book, 'update'));
    }

    public function update(Request $request, LibraryBook $book)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($book->institute_id !== $this->instituteId(), 403);

        $book->update($this->validatedBookData($request));
        $book->authors()->sync($request->input('author_ids', []));

        return $this->redirectRoute('books.show', $book)->with('success', 'Book updated successfully.');
    }

    public function toggle(LibraryBook $book)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($book->institute_id !== $this->instituteId(), 403);
        $book->update(['is_active' => !$book->is_active]);

        return back()->with('success', 'Book status updated.');
    }

    public function storeCopy(Request $request, LibraryBook $book)
    {
        $this->ensureLibraryPermission('manage');
        abort_if($book->institute_id !== $this->instituteId(), 403);

        $instituteId = $this->instituteId();

        $bulk = $request->validate([
            'accession_prefix' => 'required|string|max:50',
            'accession_start'  => ['required', 'regex:/^\d+$/'],
            'quantity'         => 'required|integer|min:1|max:50',
        ]);

        $common = $request->validate([
            'rack_id'        => ['nullable', 'integer', Rule::exists('library_racks', 'id')->where('institute_id', $instituteId)],
            'vendor_id'      => ['nullable', 'integer', Rule::exists('library_vendors', 'id')->where('institute_id', $instituteId)],
            'purchase_date'  => 'nullable|date',
            'price'          => 'nullable|numeric|min:0',
            'status'         => 'required|in:available,lost,damaged,withdrawn',
            'condition_note' => 'nullable|string|max:255',
        ]);

        $prefix   = trim($bulk['accession_prefix']);
        $startStr = $bulk['accession_start'];
        $start    = (int) $startStr;
        $pad      = strlen($startStr);
        $qty      = (int) $bulk['quantity'];

        $accessionNos = [];
        for ($i = 0; $i < $qty; $i++) {
            $accessionNos[] = $prefix . str_pad((string) ($start + $i), $pad, '0', STR_PAD_LEFT);
        }

        $existing = LibraryBookCopy::where('institute_id', $instituteId)
            ->whereIn('accession_no', $accessionNos)
            ->pluck('accession_no')
            ->toArray();

        if (!empty($existing)) {
            return back()->withErrors(['accession_prefix' => 'The following accession numbers already exist: ' . implode(', ', $existing)]);
        }

        DB::transaction(function () use ($accessionNos, $common, $book, $instituteId) {
            foreach ($accessionNos as $accNo) {
                LibraryBookCopy::create(array_merge($common, [
                    'institute_id' => $instituteId,
                    'book_id'      => $book->id,
                    'accession_no' => $accNo,
                ]));
            }
        });

        $count = count($accessionNos);
        return back()->with('success', $count . ' ' . ($count === 1 ? 'copy' : 'copies') . ' added: ' . implode(', ', $accessionNos));
    }

    public function updateCopy(Request $request, LibraryBook $book, LibraryBookCopy $copy)
    {
        $this->ensureLibraryPermission('manage');
        abort_if(
            $book->institute_id !== $this->instituteId()
            || $copy->book_id !== $book->id
            || $copy->institute_id !== $this->instituteId(),
            403
        );

        if ($copy->status === 'issued') {
            return back()->withErrors(['copy' => 'This copy is currently issued and cannot be updated. Please return it first.']);
        }

        $copy->update($this->validatedCopyData($request, $copy));

        return back()->with('success', 'Book copy updated successfully.');
    }

    private function formData(LibraryBook $book, string $mode): array
    {
        return [
            'book' => $book->loadMissing('authors'),
            'mode' => $mode,
            'categories' => LibraryCategory::forInstitute($this->instituteId())->where('is_active', true)->orderBy('name')->get(),
            'publishers' => LibraryPublisher::forInstitute($this->instituteId())->where('is_active', true)->orderBy('name')->get(),
            'subjects' => LibrarySubject::forInstitute($this->instituteId())->where('is_active', true)->orderBy('name')->get(),
            'authors' => LibraryAuthor::forInstitute($this->instituteId())->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validatedBookData(Request $request): array
    {
        $instituteId = $this->instituteId();

        return $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('library_categories', 'id')->where('institute_id', $instituteId)],
            'publisher_id' => ['nullable', 'integer', Rule::exists('library_publishers', 'id')->where('institute_id', $instituteId)],
            'subject_id'   => ['nullable', 'integer', Rule::exists('library_subjects', 'id')->where('institute_id', $instituteId)],
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'edition' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'subject_name' => 'nullable|string|max:150',
            'author_text' => 'nullable|string',
            'description' => 'nullable|string',
            'author_ids'   => 'nullable|array',
            'author_ids.*' => ['integer', Rule::exists('library_authors', 'id')->where('institute_id', $instituteId)],
        ]);
    }

    private function validatedCopyData(Request $request, ?LibraryBookCopy $existingCopy): array
    {
        $instituteId = $this->instituteId();

        return $request->validate([
            'rack_id'   => ['nullable', 'integer', Rule::exists('library_racks', 'id')->where('institute_id', $instituteId)],
            'vendor_id' => ['nullable', 'integer', Rule::exists('library_vendors', 'id')->where('institute_id', $instituteId)],
            'accession_no' => [
                'required', 'string', 'max:80',
                Rule::unique('library_book_copies', 'accession_no')
                    ->where('institute_id', $instituteId)
                    ->ignore($existingCopy?->id),
            ],
            'barcode' => [
                'nullable', 'string', 'max:120',
                Rule::unique('library_book_copies', 'barcode')
                    ->where('institute_id', $instituteId)
                    ->ignore($existingCopy?->id),
            ],
            'purchase_date' => 'nullable|date',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,issued,reserved,lost,damaged,withdrawn',
            'condition_note' => 'nullable|string|max:255',
        ]);
    }
}
