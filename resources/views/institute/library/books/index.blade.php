@extends($libraryLayout)
@section('title', 'Library Books')
@section('breadcrumb', 'Library / Books')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Books & Copies</h4>
        <small class="text-muted">Title master aur physical copies dono ko yahin manage karo.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.books.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Book</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-8">
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search title, ISBN, subject, author">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                <a href="{{ route($libraryRoutePrefix . '.books.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Book</th>
                    <th>Category</th>
                    <th>Authors</th>
                    <th>Copies</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($books as $book)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $book->title }}</div>
                        <small class="text-muted">{{ $book->isbn ?: 'No ISBN' }} @if($book->edition) | {{ $book->edition }} @endif</small>
                    </td>
                    <td>{{ $book->category->name ?? '-' }}</td>
                    <td>
                        <div>{{ $book->authors->pluck('name')->implode(', ') ?: ($book->author_text ?: '-') }}</div>
                        <small class="text-muted">{{ $book->subject->name ?? ($book->subject_name ?: 'No subject') }}</small>
                    </td>
                    <td>
                        <a href="{{ route($libraryRoutePrefix . '.books.show', $book) }}" class="text-decoration-none">
                            <span class="badge bg-secondary-subtle text-secondary border">{{ $book->copies->count() }} total</span>
                            <span class="badge bg-success-subtle text-success border">{{ $book->copies->where('status', 'available')->count() }} available</span>
                        </a>
                    </td>
                    <td><span class="badge {{ $book->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $book->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="d-flex gap-2">
                        <a href="{{ route($libraryRoutePrefix . '.books.show', $book) }}"
                           class="btn btn-primary btn-sm"
                           title="Copies dekhein aur nayi copy add karein">
                            <i class="bi bi-plus-circle me-1"></i>Copies
                        </a>
                        <a href="{{ route($libraryRoutePrefix . '.books.edit', $book) }}" class="btn btn-outline-secondary btn-sm" title="Title edit karein"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route($libraryRoutePrefix . '.books.toggle', $book) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm" title="Active/Inactive toggle"><i class="bi bi-arrow-repeat"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">Koi book title nahi mila.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($books->hasPages())
        <div class="card-footer bg-white">{{ $books->links() }}</div>
    @endif
</div>
@endsection
