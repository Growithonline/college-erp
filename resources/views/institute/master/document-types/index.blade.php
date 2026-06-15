@extends('institute.layout')
@section('title', 'Document Types')
@section('breadcrumb', 'Master / Documents / Types')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Document Types</h4>
        <small class="text-muted">Define the name, max size, and allowed formats for each document type</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.document-categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-folder2 me-1"></i>Categories
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

    {{-- Add Form --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Document Type</span>
            </div>
            <div class="card-body p-3">
                @if($allCategories->isEmpty())
                <div class="alert alert-warning small py-2 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Please <a href="{{ route('master.document-categories.index') }}">create a category first</a>.
                </div>
                @else
                <form method="POST" action="{{ route('master.document-types.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="document_category_id" class="form-select @error('document_category_id') is-invalid @enderror">
                            <option value="">-- Select --</option>
                            @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('document_category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('document_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Document Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. 10TH MARKSHEET, AADHAAR CARD"
                               style="text-transform:uppercase"
                               autocomplete="off">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Max File Size <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="max_size_kb" class="form-control @error('max_size_kb') is-invalid @enderror"
                                   value="{{ old('max_size_kb', 512) }}" min="50" max="10240">
                            <span class="input-group-text">KB</span>
                        </div>
                        <div class="form-text text-muted">512 KB | 1024 KB = 1 MB | 2048 KB = 2 MB</div>
                        @error('max_size_kb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Allowed Formats <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['pdf','jpg','jpeg','png','doc','docx'] as $fmt)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="allowed_formats[]"
                                       value="{{ $fmt }}" id="fmt_{{ $fmt }}_new"
                                       @checked(in_array($fmt, old('allowed_formats', ['pdf','jpg','jpeg','png'])))>
                                <label class="form-check-label small" for="fmt_{{ $fmt }}_new">.{{ $fmt }}</label>
                            </div>
                            @endforeach
                        </div>
                        @error('allowed_formats')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add Document Type
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- List grouped by category --}}
    <div class="col-md-8">
        @if($categories->isEmpty() || $categories->every(fn($c) => $c->documentTypes->isEmpty()))
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-file-earmark-text" style="font-size:3rem;color:#94a3b8;"></i>
                <h5 class="mt-3 text-muted">No Document Types Yet</h5>
                <p class="text-muted small">Add the first type using the form on the left.</p>
            </div>
        </div>
        @else
        @foreach($categories as $cat)
        @if($cat->documentTypes->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-light border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open text-primary"></i>
                <span class="fw-semibold small">{{ $cat->name }}</span>
                <span class="badge bg-secondary-subtle text-secondary border ms-auto">{{ $cat->documentTypes->count() }} types</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Max Size</th>
                            <th>Formats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cat->documentTypes as $dt)
                        <tr>
                            <td>
                                <form method="POST" action="{{ route('master.document-types.update', $dt) }}" class="d-flex gap-1 align-items-center">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="document_category_id" value="{{ $cat->id }}">
                                    <input type="hidden" name="max_size_kb" value="{{ $dt->max_size_kb }}">
                                    @foreach(explode(',', $dt->allowed_formats) as $f)
                                    <input type="hidden" name="allowed_formats[]" value="{{ trim($f) }}">
                                    @endforeach
                                    <input type="text" name="name"
                                           class="form-control form-control-sm"
                                           value="{{ $dt->name }}" style="min-width:150px;text-transform:uppercase">
                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Save name">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="text-muted">{{ number_format($dt->max_size_kb) }} KB</td>
                            <td>
                                @foreach(explode(',', $dt->allowed_formats) as $f)
                                <span class="badge bg-secondary-subtle text-secondary border me-1">.{{ trim($f) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.document-types.toggle', $dt) }}">@csrf
                                    <button class="btn btn-sm {{ $dt->status ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $dt->status ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    {{-- Edit modal trigger --}}
                                    <button class="btn btn-outline-secondary btn-sm" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#editModal{{ $dt->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('master.document-types.destroy', $dt) }}"
                                          onsubmit="return confirm('Delete this document type?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Edit Modals --}}
        @foreach($cat->documentTypes as $dt)
        <div class="modal fade" id="editModal{{ $dt->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('master.document-types.update', $dt) }}">
                        @csrf @method('PUT')
                        <div class="modal-header">
                            <h6 class="modal-title fw-semibold">Edit Document Type</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Category</label>
                                <select name="document_category_id" class="form-select">
                                    @foreach($allCategories as $c)
                                    <option value="{{ $c->id }}" @selected($c->id == $dt->document_category_id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $dt->name }}" style="text-transform:uppercase">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Max Size (KB)</label>
                                <input type="number" name="max_size_kb" class="form-control" value="{{ $dt->max_size_kb }}" min="50" max="10240">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Allowed Formats</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php $existing = array_map('trim', explode(',', $dt->allowed_formats)); @endphp
                                    @foreach(['pdf','jpg','jpeg','png','doc','docx'] as $fmt)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="allowed_formats[]"
                                               value="{{ $fmt }}" id="fmt_{{ $fmt }}_{{ $dt->id }}"
                                               @checked(in_array($fmt, $existing))>
                                        <label class="form-check-label small" for="fmt_{{ $fmt }}_{{ $dt->id }}">.{{ $fmt }}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach

        @endif
        @endforeach
        @endif
    </div>

</div>
@endsection
