@php
    $stmtLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
    $stmtSearchRoute = auth()->guard('staff')->check() ? '/staff/statement/search-student' : '/statement/search-student';
    $stmtBalanceRoute = auth()->guard('staff')->check() ? '/staff/statement/balance' : '/statement/balance';
    $currentYearLabel = $student
        ? \App\Support\AcademicState::yearLabel(
            $student->stream?->course?->structure_type,
            $student->current_semester,
            $student->coursePart?->year_number,
            $student->stream?->course?->effectiveSemestersPerYear() ?? 0
        )
        : '—';
@endphp
@extends($stmtLayout)
@section('title','Get Student Balance')
@section('breadcrumb','Statement / Student Balance')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i>Get Student Balance</h4>
        <small class="text-muted">Session wise + complete course fee balance</small>
    </div>
</div>

{{-- Student Search --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2">
        <h6 class="mb-0 fw-semibold small"><i class="bi bi-search me-2 text-primary"></i>Search Student</h6>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label small fw-semibold">Name / Student ID / Mobile</label>
                <div class="position-relative">
                    <input type="text" id="studentSearch" class="form-control"
                           placeholder="Type name, UID, or mobile..."
                           value="{{ $student?->name ?? '' }}"
                           autocomplete="off">
                    <div id="searchDropdown" class="position-absolute w-100 bg-white border rounded-bottom shadow-sm"
                         style="z-index:1000;display:none;max-height:260px;overflow-y:auto;top:100%;left:0;"></div>
                </div>
                <input type="hidden" id="selectedStudentId" value="{{ $student?->id ?? '' }}">
            </div>
            <div class="col-md-4">
                <button onclick="loadBalance()" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Show Balance
                </button>
            </div>
        </div>
    </div>
</div>

@if($student)
{{-- Student Info Card --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-3" style="font-size:13px;">
            <div class="col-6 col-md-2">
                <div class="text-muted small">Student ID</div>
                <div class="fw-bold text-primary">{{ $student->student_uid }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small">Student Name</div>
                <div class="fw-bold">{{ $student->name }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small">Father Name</div>
                <div class="fw-semibold">{{ $student->father_name ?? '—' }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small">Mother Name</div>
                <div class="fw-semibold">{{ $student->mother_name ?? '—' }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small">Course</div>
                <div class="fw-semibold">{{ $student->stream->course->name ?? '—' }}</div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-muted small">Year / Semester / Session</div>
                <div class="fw-semibold">
                    {{ $currentYearLabel }}
                    @if($student->current_semester) &bull; Sem {{ $student->current_semester }} @endif
                    &bull; {{ $student->session->name ?? '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Balance Table --}}
@if($balances->isNotEmpty())
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold small"><i class="bi bi-table me-2 text-primary"></i>Session Wise Balance</h6>
        <div class="d-flex gap-2">
            <a href="{{ $stmtBalanceRoute }}?student_id={{ $student->id }}&print=a4" target="_blank"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer me-1"></i> A4 Print
            </a>
            <a href="{{ $stmtBalanceRoute }}?student_id={{ $student->id }}&print=thermal" target="_blank"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-receipt me-1"></i> Thermal Print
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Session</th>
                    <th class="text-end">Fee Paid (₹)</th>
                    <th class="text-end">Discount (₹)</th>
                    <th class="text-end">Balance / Due (₹)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $overallPaid = 0;
                    // Overall due = sirf last (current) session ka due, taaki double count na ho
                    $overallDue  = $balances->last()['due'] ?? 0;
                @endphp
                @foreach($balances as $b)
                @php $overallPaid += $b['paid']; @endphp
                <tr>
                    <td class="ps-3 small fw-semibold">{{ $b['session']->name }}</td>
                    <td class="text-end small text-success fw-semibold">₹ {{ number_format($b['paid'], 0) }}</td>
                    <td class="text-end small text-warning">₹ {{ number_format($b['discount'], 0) }}</td>
                    <td class="text-end small">
                        @if($b['carried_forward'] ?? false)
                            <span class="text-muted">Carried Forward →</span>
                        @elseif($b['due'] > 0)
                            <span class="text-danger fw-bold">₹ {{ number_format($b['due'], 0) }} <small>(due)</small></span>
                        @else
                            <span class="text-success fw-semibold">Clear ✓</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <td class="ps-3 fw-bold">Overall Total</td>
                    <td class="text-end fw-bold">₹ {{ number_format($overallPaid, 0) }}</td>
                    <td></td>
                    <td class="text-end fw-bold">
                        @if($overallDue > 0)
                            <span class="text-danger">₹ {{ number_format($overallDue, 0) }} due</span>
                        @else
                            <span class="text-success">All Clear ✓</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@else
<div class="alert alert-info border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i> No fee record found for this student.
</div>
@endif
@endif

@push('scripts')
<script>
let searchTimeout;
const searchInput = document.getElementById('studentSearch');
const dropdown    = document.getElementById('searchDropdown');
const hiddenId    = document.getElementById('selectedStudentId');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { dropdown.style.display = 'none'; return; }
    searchTimeout = setTimeout(() => fetchStudents(q), 300);
});

function fetchStudents(q) {
    fetch(`{{ $stmtSearchRoute }}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.length) { dropdown.style.display = 'none'; return; }
            dropdown.innerHTML = data.map(s => `
                <div class="p-2 border-bottom" style="cursor:pointer;"
                     onmouseenter="this.style.background='#f0f9ff'"
                     onmouseleave="this.style.background=''"
                     onclick="selectStudent(${s.id}, '${s.name.replace(/'/g,"\\'")}')">
                    <div class="fw-semibold small">${s.name} <span class="text-muted">(${s.student_uid})</span></div>
                    <div class="text-muted" style="font-size:11px;">${s.course} • ${s.part} • ${s.semester} • ${s.session}</div>
                    ${(s.father_name||s.mother_name) ? `<div class="text-muted" style="font-size:11px;">Father: ${s.father_name||'—'} &bull; Mother: ${s.mother_name||'—'}</div>` : ''}
                </div>`).join('');
            dropdown.style.display = 'block';
        });
}

function selectStudent(id, name) {
    hiddenId.value      = id;
    searchInput.value   = name;
    dropdown.style.display = 'none';
}

function loadBalance() {
    const id = hiddenId.value;
    if (!id) { alert('Please select a student first'); return; }
    window.location = `{{ $stmtBalanceRoute }}?student_id=${id}`;
}

document.addEventListener('click', e => {
    if (!e.target.closest('#studentSearch') && !e.target.closest('#searchDropdown')) {
        dropdown.style.display = 'none';
    }
});
</script>
@endpush
@endsection
