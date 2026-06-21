@extends('institute.layout')
@section('title', 'Course Subject Mapping')
@section('breadcrumb', 'Master / Course / ' . $stream->course->name . ' / ' . $stream->name . ' / Subjects')

@section('content')

<style>
.subj-row { transition: opacity .28s ease, transform .28s ease; }
.subj-row.removing { opacity: 0; transform: translateX(-16px); pointer-events: none; }
.subj-row.added    { animation: rowSlideIn .35s ease forwards; }
@keyframes rowSlideIn {
    from { opacity: 0; transform: translateX(14px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>

{{-- Toast --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:1200;">
    <div id="subjectToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

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
                            <option value="0" {{ old('year_number', 1) == 0 ? 'selected' : '' }}>
                                All Years (add to every year)
                            </option>
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

                    <button type="submit" id="addSubjectBtn" class="btn btn-primary btn-sm w-100">
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
                                <tr class="subj-row {{ !$mapping->is_active ? 'opacity-50' : '' }}"
                                    data-mapping-id="{{ $mapping->id }}"
                                    data-year="{{ $mapping->year_number }}"
                                    data-role="{{ $mapping->subject_role }}">
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
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm py-0 px-2"
                                                    style="font-size:11px;"
                                                    onclick="openDeleteModal(this)"
                                                    data-delete-url="{{ route('master.streams.subjects.destroy', [$stream, $mapping]) }}"
                                                    data-subject-name="{{ $mapping->subject->name ?? 'this subject' }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

{{-- ── Delete Confirm Modal ── --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10"
                          style="width:56px;height:56px;">
                        <i class="bi bi-trash3-fill text-danger fs-4"></i>
                    </span>
                </div>
                <h6 class="fw-bold mb-1">Remove Subject?</h6>
                <p class="text-muted small mb-4" id="deleteSubjectName" style="min-height:1.2em;"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger btn-sm px-4" id="confirmDeleteBtn">
                        <i class="bi bi-trash me-1"></i>Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mapped subject+year combinations from PHP
// Key format: "subject_id_year_number" e.g. "3_1"
const mappedSubjectYears = @json($mappedSubjectYears);
const allYears = @json($years);

// Year change hone pe subjects ko enable/disable karo
function updateSubjectOptions() {
    const year     = document.getElementById('year_select').value;
    const selectEl = document.getElementById('subject_select');
    const btn      = document.getElementById('addSubjectBtn');

    Array.from(selectEl.options).forEach(opt => {
        if (!opt.value) return;
        const baseName = opt.getAttribute('data-name') ||
            opt.text.replace(' — Already Added', '').replace(' — All years mapped', '');

        if (year === '0') {
            // "All Years" — disable only if subject is mapped in EVERY year
            const mappedInAll = allYears.every(y => mappedSubjectYears[opt.value + '_' + y] === true);
            opt.disabled = mappedInAll;
            opt.text     = mappedInAll ? baseName + ' — All years mapped' : baseName;
        } else {
            const isMapped = mappedSubjectYears[opt.value + '_' + year] === true;
            opt.disabled = isMapped;
            opt.text     = isMapped ? baseName + ' — Already Added' : baseName;
        }
    });

    // Reset selection agar selected option disable ho gayi
    if (selectEl.value && selectEl.options[selectEl.selectedIndex]?.disabled) {
        selectEl.value = '';
    }

    // Button text update
    if (year === '0') {
        btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add to All Years';
        btn.classList.replace('btn-primary', 'btn-success');
    } else {
        btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add Subject';
        btn.classList.replace('btn-success', 'btn-primary');
    }
}

// Page load pe bhi run karo (default year ke liye)
document.addEventListener('DOMContentLoaded', function () {
    updateSubjectOptions();
});

function updateChooseable(select) {
    const checkbox = document.getElementById('is_chooseable');
    checkbox.checked = (select.value !== 'compulsory');
}

function openEditModal(mappingId, role, isChooseable, sortOrder) {
    document.getElementById('editForm').action =
        '{{ route("master.streams.subjects.update", [$stream, "__ID__"]) }}'
        .replace('__ID__', mappingId);

    document.getElementById('edit_role').value         = role;
    document.getElementById('edit_chooseable').checked = isChooseable;
    document.getElementById('edit_sort').value         = sortOrder;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ── Delete Modal ──────────────────────────────────────────────────────────
let _deleteUrl = null, _deleteRow = null, _deleteModal = null;

function openDeleteModal(btn) {
    _deleteUrl  = btn.dataset.deleteUrl;
    _deleteRow  = btn.closest('tr');
    // dataset reads HTML-decoded value safely — no XSS risk
    document.getElementById('deleteSubjectName').textContent =
        '"' + btn.dataset.subjectName + '" will be removed from this stream.';
    _deleteModal = _deleteModal || new bootstrap.Modal(document.getElementById('deleteModal'));
    _deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    updateSubjectOptions();

    // ── Confirm delete ────────────────────────────────────────────────────
    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
        if (!_deleteUrl || !_deleteRow) return;

        const btn = this;
        btn.disabled    = true;
        btn.innerHTML   = '<span class="spinner-border spinner-border-sm me-1"></span>Removing…';

        const formData = new FormData();
        formData.append('_token',  document.querySelector('meta[name="csrf-token"]').content);
        formData.append('_method', 'DELETE');

        fetch(_deleteUrl, {
            method: 'POST',
            body:   formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error('Failed');

            _deleteModal.hide();

            // Animate row out
            _deleteRow.classList.add('removing');
            setTimeout(() => {
                const year      = _deleteRow.dataset.year;
                const role      = _deleteRow.dataset.role;
                const tbody     = _deleteRow.closest('tbody');
                _deleteRow.remove();

                // Update nav pill badge
                updateYearBadge(year, -1);
                // Update role summary card
                updateRoleSummary(year, role, -1);
                // Show empty state if no rows left
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    showEmptyState(year);
                }
                showToast('Subject removed successfully!', 'success');
            }, 290);
        })
        .catch(() => showToast('Something went wrong. Please try again.', 'danger'))
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-trash me-1"></i>Remove';
        });
    });

    // ── Add form loading state ────────────────────────────────────────────
    document.querySelector('form[action*="subjects"]').addEventListener('submit', function () {
        const btn = document.getElementById('addSubjectBtn');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Adding…';
    });
});

// ── Helpers ───────────────────────────────────────────────────────────────
function showToast(msg, type) {
    const el = document.getElementById('subjectToast');
    el.className = 'toast align-items-center text-white border-0 bg-' + type;
    document.getElementById('toastMsg').textContent = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3000 }).show();
}

function updateYearBadge(year, delta) {
    const btn = document.querySelector('#yearTabs button[data-bs-target="#year' + year + '"]');
    if (!btn) return;
    const badge = btn.querySelector('.badge');
    if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent || '0') + delta);
}

function updateRoleSummary(year, role, delta) {
    const pane = document.getElementById('year' + year);
    if (!pane) return;
    const cards = pane.querySelectorAll('.card .fw-bold');
    const labels = ['major', 'minor', 'compulsory', 'optional'];
    cards.forEach((card, i) => {
        if (labels[i] === role) {
            card.textContent = Math.max(0, parseInt(card.textContent || '0') + delta);
        }
    });
}

function showEmptyState(year) {
    const pane = document.getElementById('year' + year);
    if (!pane) return;
    const table = pane.querySelector('.card.border-0.shadow-sm');
    if (table) {
        table.outerHTML = `<div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No subjects mapped for Year ${year}.<br>
                <small>Add subjects from the left panel.</small>
            </div>
        </div>`;
    }
}
</script>

@endsection