@extends('institute.layout')
@section('title', 'Student Types')
@section('breadcrumb', 'Master / Student Types')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Student Types</h4>
        <small class="text-muted">{{ $studentTypes->count() }} types configured — used in admission forms and fee structure</small>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="row g-4">

    {{-- Add New --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Student Type</span>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('master.student-types.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. REGULAR, PRIVATE, DISTANCE"
                               style="text-transform:uppercase"
                               autocomplete="off">
                        <div class="form-text text-muted">Will be saved in uppercase (e.g. REGULAR, PRIVATE)</div>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add Student Type
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-md-8">
        @if($studentTypes->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-person-badge" style="font-size:3rem;color:#94a3b8;"></i>
                <h5 class="mt-3 text-muted">No Student Types Yet</h5>
                <p class="text-muted small">Add the first type from the left panel.</p>
            </div>
        </div>
        @else
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-list-ul me-2 text-secondary"></i>Configured Types</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-muted" style="width:40px;">#</th>
                            <th>Name</th>
                            <th>Slug <small class="text-muted">(internal identifier)</small></th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentTypes as $i => $st)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td>
                                {{-- Inline Edit --}}
                                <form method="POST" action="{{ route('master.student-types.update', $st) }}" class="d-flex gap-2 align-items-center">
                                    @csrf @method('PUT')
                                    <input type="text" name="name"
                                           class="form-control form-control-sm @error('name_' . $st->id) is-invalid @enderror"
                                           value="{{ old('name_' . $st->id, $st->name) }}"
                                           style="max-width:180px;text-transform:uppercase">
                                    @error('name_' . $st->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Save">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td><code class="text-secondary small">{{ $st->slug }}</code></td>
                            <td>
                                <form method="POST" action="{{ route('master.student-types.toggle', $st) }}">@csrf
                                    <button class="btn btn-sm {{ $st->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        <i class="bi bi-{{ $st->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                        {{ $st->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.student-types.destroy', $st) }}"
                                      onsubmit="return confirm('Delete \"{{ $st->name }}\"? Students with this type will have their type cleared.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Note:</strong>
            Here you can manage the student types that will be available in the admission form and fee structure. Deactivating a type will not delete any existing student data; it will just hide that type from the selection options in the forms.
        </div>
        @endif
    </div>

</div>
@endsection
