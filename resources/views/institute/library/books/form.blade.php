@extends($libraryLayout)
@section('title', $mode === 'store' ? 'Add Book' : 'Edit Book')
@section('breadcrumb', 'Library / Books / ' . ($mode === 'store' ? 'Add' : 'Edit'))
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $mode === 'store' ? 'Add New Book' : 'Edit Book' }}</h4>
        <small class="text-muted">Book title master save karo, copies alag screen par add hongi.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.books.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ $mode === 'store' ? route($libraryRoutePrefix . '.books.store') : route($libraryRoutePrefix . '.books.update', $book) }}">
            @csrf
            @if($mode === 'update') @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $book->title) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ISBN</label>
                    <input type="text" name="isbn" value="{{ old('isbn', $book->isbn) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subtitle</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle', $book->subtitle) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Edition</label>
                    <input type="text" name="edition" value="{{ old('edition', $book->edition) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Language</label>
                    <input type="text" name="language" value="{{ old('language', $book->language ?: 'English') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $book->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Publisher</label>
                    <select name="publisher_id" class="form-select">
                        <option value="">Select</option>
                        @foreach($publishers as $publisher)
                            <option value="{{ $publisher->id }}" @selected(old('publisher_id', $book->publisher_id) == $publisher->id)>{{ $publisher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Subject Master</label>
                    <select name="subject_id" class="form-select">
                        <option value="">Select</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected(old('subject_id', $book->subject_id) == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject_name" value="{{ old('subject_name', $book->subject_name) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Authors</label>
                    <select name="author_ids[]" class="form-select" multiple size="6">
                        @php $selectedAuthors = old('author_ids', $book->authors->pluck('id')->all()); @endphp
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}" @selected(in_array($author->id, $selectedAuthors))>{{ $author->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Ctrl / Cmd press karke multiple authors select kar sakte ho.</small>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Fallback Author Text</label>
                    <input type="text" name="author_text" value="{{ old('author_text', $book->author_text) }}" class="form-control" placeholder="Agar author master me nahi hai to text yahan save karo">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $book->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>{{ $mode === 'store' ? 'Save Book' : 'Update Book' }}</button>
                <a href="{{ route($libraryRoutePrefix . '.books.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
