@extends('institute.layout')
@section('title', 'Document Categories')
@section('breadcrumb', 'Master / Documents / Categories')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Document Categories</h4>
        <small class="text-muted">{{ $categories->count() }} categories — e.g. Education, Address Proof, Income Proof</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.document-types.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Document Types
        </a>
        <a href="{{ route('master.document-rules.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders me-1"></i>Upload Rules
        </a>
    </div>
</div>

@if($errors->has('delete'))
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('delete') }}</div>
@elseif($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row g-4">

    {{-- Add New --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Category</span>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('master.document-categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. EDUCATION, ADDRESS PROOF"
                               style="text-transform:uppercase"
                               autocomplete="off">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add Category
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            Document types are created within categories. E.g. the <strong>Education</strong> category
            may contain <em>10th Marksheet, 12th Certificate, UG Degree</em>, etc.
        </div>
    </div>

    {{-- List --}}
    <div class="col-md-8">
        @if($categories->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-folder2-open" style="font-size:3rem;color:#94a3b8;"></i>
                <h5 class="mt-3 text-muted">No Categories Yet</h5>
                <p class="text-muted small">Add the first category from the left panel.</p>
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-list-ul me-2 text-secondary"></i>Configured Categories</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted" style="width:40px;">#</th>
                            <th>Name</th>
                            <th>Document Types</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $i => $cat)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td>
                                <form method="POST" action="{{ route('master.document-categories.update', $cat) }}" class="d-flex gap-2 align-items-center">
                                    @csrf @method('PUT')
                                    <input type="text" name="name"
                                           class="form-control form-control-sm @error('name_' . $cat->id) is-invalid @enderror"
                                           value="{{ old('name_' . $cat->id, $cat->name) }}"
                                           style="max-width:200px;text-transform:uppercase">
                                    @error('name_' . $cat->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Save">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border">{{ $cat->document_types_count }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.document-categories.toggle', $cat) }}">@csrf
                                    <button class="btn btn-sm {{ $cat->status ? 'btn-success' : 'btn-secondary' }}">
                                        <i class="bi bi-{{ $cat->status ? 'check-circle' : 'x-circle' }}"></i>
                                        {{ $cat->status ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.document-categories.destroy', $cat) }}"
                                      onsubmit="return confirm('Delete \"{{ $cat->name }}\"? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"
                                            {{ $cat->document_types_count > 0 ? 'disabled title=Document types are linked' : '' }}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
