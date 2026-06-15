@extends('institute.layout')
@section('title', 'Course Subject Mapping')
@section('breadcrumb', 'Master / Course / ' . $stream->course->name . ' / ' . $stream->name . ' / Subjects')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Subject Mapping</h4>
        <small class="text-muted">
            {{ $stream->course->name }} &rarr; {{ $stream->name }}
            &nbsp;|&nbsp; Duration: {{ $stream->course->duration }} {{ $stream->course->duration_type ?? 'years' }}
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.courses.streams.index', $stream->course_id) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Streams
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- ── LEFT: Add Subject Form ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Add Subject to Stream
                </h6>
            </div>
            <div class="card-body p-3">
                <form method="POST"
                      action="{{ route('master.streams.subjects.store', $stream) }}"
                      novalidate>
                    @csrf

                    {{-- Year --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Year <span class="text-danger">*</span>
                        </label>
                        <select name="year_number" id="year_select"
                                class="form-select form-select-sm @error('year_number') is-invalid @enderror"
                                onchange="updateSubjectOptions()" required>
                            @foreach($years as $y)
                            <option value="{{ $y }}" {{ old('year_number', 1) == $y ? 'selected' : '' }}>
                                Year {{ $y }}
                                @if($y == 1) (1st Year) @elseif($y == 2) (2nd Year) @elseif($y == 3) (3rd Year) @endif
                            </option>
                            @endforeach
                        </select>
                        @error('year_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Subject --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Subject <span class="text-danger">*</span>
                        </label>
                        <select name="subject_id" id="subject_select"
                                class="form-select form-select-sm @error('subject_id') is-invalid @enderror"
                                required>
                            <option value="">-- Select Subject --</option>
                            @foreach($availableSubjects as $sub)
                            <option value="{{ $sub->id }}"
                                    data-id="{{ $sub->id }}"
                                    data-name="{{ $sub->name }}{{ $sub->code ? ' ('.$sub->code.')' : '' }}{{ $sub->has_practical ? ' 🔬' : '' }}"
                                    {{ old('subject_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                                @if($sub->code) ({{ $sub->code }}) @endif
                                @if($sub->has_practical) 🔬 @endif
                            </option>
                            @endforeach
                        </select>
                        @error('subject_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Subject Role <span class="text-danger">*</span>
                        </label>
                        <select name="subject_role" class="form-select form-select-sm @error('subject_role') is-invalid @enderror"
                                onchange="updateChooseable(this)" required>
                            @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('subject_role') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                        @error('subject_role')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text small">
                            <strong>Major:</strong> Student selects (only 1 allowed) &nbsp;|&nbsp;
                            <strong>Minor:</strong> Student selects (multiple allowed) &nbsp;|&nbsp;
                            <strong>Compulsory:</strong> Auto-included &nbsp;|&nbsp;
                            <strong>Optional:</strong> Student selects &nbsp;|&nbsp;
                            <strong class="text-purple">Both:</strong> Same subject used as major or minor
                        </div>
                    </div>

                    {{-- Chooseable toggle --}}
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_chooseable" value="0">
                            <input class="form-check-input" type="checkbox"
                                   name="is_chooseable" id="is_chooseable" value="1"
                                   {{ old('is_chooseable', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="is_chooseable">
                                Student Can Choose
                            </label>
                        </div>
                        <div class="form-text small text-muted">
                            OFF = Compulsory (auto-added on admission)
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order"
                               class="form-control form-control-sm"
                               value="{{ old('sort_order', 0) }}"
                               min="0" max="999">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i> Add Subject
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Mapped Subjects by Year ── --}}
    <div class="col-lg-8">

        {{-- Year Tabs --}}
        <ul class="nav nav-pills mb-3 gap-2" id="yearTabs">
            @foreach($years as $y)
            <li class="nav-item">
                <button class="nav-link {{ $y == 1 ? 'active' : '' }}"
                        data-bs-toggle="pill"
                        data-bs-target="#year{{ $y }}"
                        type="button">
                    Year {{ $y }}
                    <span class="badge bg-white text-primary ms-1">
                        {{ ($subjectsByYear[$y] ?? collect())->count() }}
                    </span>
                </button>
            </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach($years as $y)
            @php $yearSubjects = $subjectsByYear[$y] ?? collect(); @endphp
            <div class="tab-pane fade {{ $y == 1 ? 'show active' : '' }}" id="year{{ $y }}">

                {{-- Year summary --}}
                <div class="row g-2 mb-3">
                    @foreach(['major' => 'primary', 'minor' => 'info', 'compulsory' => 'success', 'optional' => 'secondary'] as $role => $color)
                    @php $cnt = $yearSubjects->where('subject_role', $role)->count(); @endphp
                    <div class="col-3">
                        <div class="card border-0 bg-{{ $color }} bg-opacity-10 text-center py-2">
                            <div class="fw-bold text-{{ $color == 'info' ? 'dark' : $color }}">{{ $cnt }}</div>
                            <div class="small text-muted">{{ ucfirst($role) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($yearSubjects->isEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No subjects mapped for Year {{ $y }}.<br>
                        <small>Add subjects from the left panel.</small>
                    </div>
                </div>
                @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width:30px;">#</th>
                                    <th>Subject</th>
                                    <th style="width:100px;">Role</th>
                                    <th style="width:100px;">Chooseable</th>
                                    <th style="width:70px;">Status</th>
                                    <th style="width:120px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($yearSubjects->sortBy('sort_order') as $mapping)
                                <tr class="{{ !$mapping->is_active ? 'opacity-50' : '' }}">
                                    <td class="text-muted small">{{ $mapping->sort_order }}</td>
                                    <td>
                                        <div class="fw-semibold small">
                                            {{ $mapping->subject->name ?? 'N/A' }}
                                            @if($mapping->subject?->code)
                                            <span class="text-muted">({{ $mapping->subject->code }})</span>
                                            @endif
                                        </div>
                                        @if($mapping->subject?->has_practical)
                                        <span class="badge bg-warning text-dark" style="font-size:9px;">
                                            🔬 Has Practical
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $mapping->role_badge_class }}">
                                            {{ $mapping->role_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($mapping->is_chooseable)
                                            <span class="badge bg-success bg-opacity-10 text-success border" style="font-size:10px;">
                                                ✓ Yes
                                            </span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border" style="font-size:10px;">
                                                Auto
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST"
                                              action="{{ route('master.streams.subjects.toggle', [$stream, $mapping]) }}"
                                              class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="badge border-0 {{ $mapping->is_active ? 'bg-success' : 'bg-secondary' }}"
                                                    style="cursor:pointer; font-size:10px;">
                                                {{ $mapping->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            {{-- Edit button --}}
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm py-0 px-2"
                                                    style="font-size:11px;"
                                                    onclick="openEditModal(
                                                        {{ $mapping->id }},
                                                        '{{ $mapping->subject_role }}',
                                                        {{ $mapping->is_chooseable ? 'true' : 'false' }},
                                                        {{ $mapping->sort_order }}
                                                    )">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            {{-- Delete button --}}
                                            <form method="POST"
                                                  action="{{ route('master.streams.subjects.destroy', [$stream, $mapping]) }}"
                                                  onsubmit="return confirm('Remove this subject mapping?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-outline-danger btn-sm py-0 px-2"
                                                        style="font-size:11px;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold">Edit Subject Mapping</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                @csrf
                @method('PATCH')
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Role</label>
                        <select name="subject_role" id="edit_role" class="form-select form-select-sm">
                            @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_chooseable" value="0">
                            <input class="form-check-input" type="checkbox"
                                   name="is_chooseable" id="edit_chooseable" value="1">
                            <label class="form-check-label small" for="edit_chooseable">
                                Chooseable
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort"
                               class="form-control form-control-sm" min="0" max="999">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mapped subject+year combinations from PHP
// Key format: "subject_id_year_number" e.g. "3_1"
const mappedSubjectYears = @json($mappedSubjectYears);

// Year change hone pe subjects ko enable/disable karo
function updateSubjectOptions() {
    const year      = document.getElementById('year_select').value;
    const selectEl  = document.getElementById('subject_select');
    const current   = selectEl.value;

    Array.from(selectEl.options).forEach(opt => {
        if (!opt.value) return;
        const key     = opt.value + '_' + year;
        const isMapped = mappedSubjectYears[key] === true;
        const baseName = opt.getAttribute('data-name') || opt.text.replace(' — Already Added', '');
        opt.disabled  = isMapped;
        opt.text      = isMapped ? baseName + ' — Already Added' : baseName;
    });

    // Reset selection agar selected option disable ho gayi
    if (selectEl.value && selectEl.options[selectEl.selectedIndex]?.disabled) {
        selectEl.value = '';
    }
}

// Page load pe bhi run karo (default year ke liye)
document.addEventListener('DOMContentLoaded', function () {
    updateSubjectOptions();
});

function updateChooseable(select) {
    const checkbox = document.getElementById('is_chooseable');
    if (select.value === 'compulsory') {
        checkbox.checked = false;
    } else {
        // major, minor, optional, both — sab chooseable hote hain
        checkbox.checked = true;
    }
}

function openEditModal(mappingId, role, isChooseable, sortOrder) {
    document.getElementById('editForm').action =
        '{{ route("master.streams.subjects.update", [$stream, "__ID__"]) }}'
        .replace('__ID__', mappingId);

    document.getElementById('edit_role').value   = role;
    document.getElementById('edit_chooseable').checked = isChooseable;
    document.getElementById('edit_sort').value   = sortOrder;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

@endsection