@extends('institute.layout')
@section('title', 'Course Types')
@section('breadcrumb', 'Master / Course Types')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Course Types</h4>
        <small class="text-muted">{{ $courseTypes->count() }} types configured — e.g. UG, PG, Diploma, Vocational</small>
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
                <span class="fw-semibold small"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Course Type</span>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('master.course-types.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. Vocational, ITI, Diploma"
                               style="text-transform:uppercase"
                               autocomplete="off">
                        <div class="form-text text-muted">Will be saved in uppercase (e.g. UG, PG, DIPLOMA)</div>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Education Level</label>
                        <select name="education_level" class="form-select form-select-sm">
                            <option value="">— Not Set (show all rows) —</option>
                            @foreach(\App\Models\CourseType::EDUCATION_LEVELS as $val => $label)
                            <option value="{{ $val }}" {{ old('education_level') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">Controls which exam rows appear in the admission form.</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i>Add Course Type
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="col-md-8">
        @if($courseTypes->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-mortarboard" style="font-size:3rem;color:#94a3b8;"></i>
                <h5 class="mt-3 text-muted">No Course Types Yet</h5>
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
                            <th>Education Level</th>
                            <th>Courses</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courseTypes as $i => $ct)
                        <tr>
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td>
                                <form method="POST" action="{{ route('master.course-types.update', $ct) }}" class="d-flex gap-2 align-items-center flex-wrap">
                                    @csrf @method('PUT')
                                    <input type="text" name="name"
                                           class="form-control form-control-sm @error('name_' . $ct->id) is-invalid @enderror"
                                           value="{{ old('name_' . $ct->id, $ct->name) }}"
                                           style="max-width:140px;text-transform:uppercase">
                                    <select name="education_level" class="form-select form-select-sm" style="max-width:160px;">
                                        <option value="">— Not Set —</option>
                                        @foreach(\App\Models\CourseType::EDUCATION_LEVELS as $val => $label)
                                        <option value="{{ $val }}" {{ old('education_level_' . $ct->id, $ct->education_level) === $val ? 'selected' : '' }}>{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @error('name_' . $ct->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Save">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <span class="badge rounded-pill
                                    {{ $ct->education_level === 'ug' ? 'bg-primary-subtle text-primary border border-primary-subtle' :
                                      ($ct->education_level === 'pg' ? 'bg-success-subtle text-success border border-success-subtle' :
                                      ($ct->education_level === 'diploma' ? 'bg-warning-subtle text-warning border border-warning-subtle' :
                                      ($ct->education_level === 'certificate' ? 'bg-info-subtle text-info border border-info-subtle' :
                                      ($ct->education_level === 'phd' ? 'bg-danger-subtle text-danger border border-danger-subtle' :
                                      'bg-secondary-subtle text-secondary border')))) }} small">
                                    {{ $ct->education_level ? strtoupper($ct->education_level) : '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">{{ $ct->courses->count() }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.course-types.toggle', $ct) }}">@csrf
                                    <button class="btn btn-sm {{ $ct->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        <i class="bi bi-{{ $ct->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                        {{ $ct->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('master.course-types.destroy', $ct) }}"
                                      onsubmit="return confirm('Delete \"{{ $ct->name }}\"? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"
                                            {{ $ct->courses->count() > 0 ? 'disabled title=Courses are linked' : '' }}>
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

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Note:</strong> Course types with linked courses cannot be deleted.
            Setting a type to Inactive hides it from new admission forms.
        </div>
        @endif
    </div>

</div>
@endsection
