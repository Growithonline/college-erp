@extends('institute.layout')
@section('title', 'Course Fee Structure')
@section('breadcrumb', 'Master / Fee Structure / Course Fees')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-currency-rupee me-2 text-primary"></i>Course Fee Structure
        </h4>
        <small class="text-muted">Set fee rules by course, year, student type and session</small>
    </div>
    <a href="{{ route('master.fee-structure.subject-fees') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-book me-1"></i> Subject Fees
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show py-2">
    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                    <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                        {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $c)
                    <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} ({{ $c->duration }} {{ $c->duration_type == 'year' ? 'Yr' : 'Mo' }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Load Fee Rules
                </button>
            </div>
        </form>
    </div>
</div>

@if($selectedCourse)

{{-- Add Rule Form --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-3" style="background:#1e293b; color:white;">
        <span class="fw-bold small">
            <i class="bi bi-plus-circle me-2"></i>Add Fee Rule — {{ $selectedCourse->name }}
        </span>
    </div>
    <div class="card-body p-3">
        <form method="POST" action="{{ route('master.fee-structure.course-fees.store') }}" novalidate>
            @csrf
            <input type="hidden" name="course_id"           value="{{ $selectedCourse->id }}">
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">

            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Fee Type <span class="text-danger">*</span></label>
                    <select name="fee_type_id" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        @foreach($feeTypes as $ft)
                        <option value="{{ $ft->id }}" {{ old('fee_type_id') == $ft->id ? 'selected' : '' }}>{{ $ft->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Year</label>
                    <select name="course_part" id="feeYear" class="form-select form-select-sm" required onchange="updateSemOptions()">
                        <option value="0">All</option>
                        @php $maxYear = (int)($selectedCourse->duration ?? 3); @endphp
                        @for($i = 1; $i <= $maxYear; $i++)
                        <option value="{{ $i }}" {{ old('course_part') == $i ? 'selected' : '' }}>
                            {{ $i }}{{ $i==1?'st':($i==2?'nd':($i==3?'rd':'th')) }}
                        </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Sem</label>
                    <select name="semester" id="feeSem" class="form-select form-select-sm" required>
                        @foreach($selectedCourse->semesterOptions() as $val => $lbl)
                        <option value="{{ $val }}" {{ old('semester') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Student Type</label>
                    <select name="student_type" class="form-select form-select-sm" required>
                        <option value="all">All Types</option>
                        @foreach($studentTypes as $st)
                        <option value="{{ $st->slug }}" {{ old('student_type') == $st->slug ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Source</label>
                    <select name="admission_source" class="form-select form-select-sm" required>
                        <option value="all">All Sources</option>
                        <option value="direct"          {{ old('admission_source')=='direct'?'selected':'' }}>Direct</option>
                        <option value="center"          {{ old('admission_source')=='center'?'selected':'' }}>Center</option>
                        <option value="channel_partner" {{ old('admission_source')=='channel_partner'?'selected':'' }}>Channel Partner</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm" required>
                        <option value="all">All</option>
                        <option value="general">General</option>
                        <option value="obc">OBC</option>
                        <option value="sc">SC</option>
                        <option value="st">ST</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select form-select-sm" required>
                        <option value="all">All</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold">Amount ₹ <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control form-control-sm"
                           min="0" max="999999" step="1" value="{{ old('amount', 0) }}" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <input type="text" name="remarks" class="form-control form-control-sm"
                           placeholder="Remarks (optional)" maxlength="255" value="{{ old('remarks') }}">
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Rules Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold small">
                <i class="bi bi-table me-2"></i>Fee Rules — {{ $selectedCourse->name }}
                &nbsp;|&nbsp; Session: {{ $sessions->find($sessionId)?->name ?? '' }}
            </span>
            <span class="badge bg-secondary">{{ $rules->count() }} rules</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
            <thead class="table-light">
                <tr>
                    <th>Fee Type</th>
                    <th>Year</th>
                    <th>Sem</th>
                    <th>Student Type</th>
                    <th>Source</th>
                    <th>Category</th>
                    <th>Gender</th>
                    <th>Remarks</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center" style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                <tr>
                    <td class="fw-semibold">{{ $rule->feeType->name ?? '—' }}</td>
                    <td>
                        @if($rule->course_part == 0) <span class="badge bg-secondary">All</span>
                        @else {{ $rule->course_part }}{{ $rule->course_part==1?'st':($rule->course_part==2?'nd':($rule->course_part==3?'rd':'th')) }} Yr
                        @endif
                    </td>
                    <td>
                        {{ $selectedCourse->semesterLabel($rule->semester, $rule->course_part) }}
                    </td>
                    <td><span class="badge {{ $rule->student_type=='all'?'bg-secondary':'bg-primary' }}">{{ $rule->student_type_label }}</span></td>
                    <td><span class="badge {{ $rule->admission_source=='all'?'bg-secondary':'bg-info text-dark' }}">{{ $rule->admission_source_label }}</span></td>
                    <td><span class="badge {{ $rule->category=='all'?'bg-secondary':'bg-warning text-dark' }}">{{ strtoupper($rule->category) }}</span></td>
                    <td class="text-muted">{{ ucfirst($rule->gender) }}</td>
                    <td class="text-muted" style="font-size:11px;">{{ $rule->remarks ?? '—' }}</td>
                    <td class="text-end fw-bold text-success">₹ {{ number_format($rule->amount, 2) }}</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            {{-- Edit --}}
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm py-0 px-2"
                                    onclick="openEditModal({{ $rule->id }}, {{ $rule->amount }}, '{{ addslashes($rule->remarks ?? '') }}')"
                                    title="Edit Amount">
                                <i class="bi bi-pencil"></i>
                            </button>
                            {{-- Delete --}}
                            <form method="POST"
                                  action="{{ route('master.fee-structure.course-fees.destroy', $rule) }}"
                                  onsubmit="return confirm('Delete this fee rule?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                        No fee rules found — add one using the form above
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="text-center text-muted py-5">
    <i class="bi bi-arrow-up-circle fs-2 d-block mb-3 text-primary"></i>
    <h6>Select a Course</h6>
    <p class="small">Select a course from the filter above and click Load</p>
</div>
@endif

{{-- ── Edit Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold">Edit Fee Rule</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Amount ₹ <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="amount" id="edit_amount"
                                   class="form-control" min="0" max="999999" step="1" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <input type="text" name="remarks" id="edit_remarks"
                               class="form-control form-control-sm" maxlength="255"
                               placeholder="Optional...">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(ruleId, amount, remarks) {
    document.getElementById('editForm').action =
        '{{ url("master/fee-structure/course-fees") }}/' + ruleId;
    document.getElementById('edit_amount').value  = amount;
    document.getElementById('edit_remarks').value = remarks;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

@if($selectedCourse)
// Semester options per year — Year=0 (All) has relative values; Year>0 has absolute values
const semesterData = @json($selectedCourse->allSemesterOptionsByYear());

function updateSemOptions() {
    const yearSel = document.getElementById('feeYear');
    const semSel  = document.getElementById('feeSem');
    if (!yearSel || !semSel) return;

    const year    = parseInt(yearSel.value, 10);
    const options = semesterData[year] || semesterData[0];
    const oldVal  = parseInt(semSel.value, 10);

    semSel.innerHTML = '';
    Object.entries(options).forEach(([val, lbl]) => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = lbl;
        if (parseInt(val, 10) === oldVal) opt.selected = true;
        semSel.appendChild(opt);
    });

    // If old value no longer exists in new options, reset to 0 (All)
    if (!semSel.querySelector('option[selected]')) {
        semSel.value = '0';
    }
}

// Init on page load (respects old() values after validation failure)
document.addEventListener('DOMContentLoaded', updateSemOptions);
@endif
</script>

@endsection