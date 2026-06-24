@php
    $stmtLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
    $stmtSearchRoute = auth()->guard('staff')->check() ? '/staff/statement/search-student' : '/statement/search-student';
    $stmtRecordRoute = auth()->guard('staff')->check() ? '/staff/statement/fee-record' : '/statement/fee-record';
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
@section('title','Fee Submit Record')
@section('breadcrumb','Statement / Fee Submit Record')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Get Student Fee Submit Record</h4>
        <small class="text-muted">Current and previous fee payment history — complete record</small>
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
                <button onclick="loadRecord()" class="btn btn-success w-100">
                    <i class="bi bi-search me-1"></i> Show Fee Record
                </button>
            </div>
        </div>
    </div>
</div>

@if($student)
{{-- Student Info --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-3" style="font-size:13px;">
            <div class="col-6 col-md-2"><div class="text-muted small">Student ID</div><div class="fw-bold text-primary">{{ $student->student_uid }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Student Name</div><div class="fw-bold">{{ $student->name }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Father Name</div><div class="fw-semibold">{{ $student->father_name ?? '—' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Mother Name</div><div class="fw-semibold">{{ $student->mother_name ?? '—' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Course</div><div class="fw-semibold">{{ $student->stream->course->name ?? '—' }}</div></div>
            <div class="col-6 col-md-2"><div class="text-muted small">Year / Semester / Session</div><div class="fw-semibold">{{ $currentYearLabel }}@if($student->current_semester) &bull; Sem {{ $student->current_semester }}@endif &bull; {{ $student->session->name ?? '—' }}</div></div>
            @if(isset($subjectNames) && $subjectNames->isNotEmpty())
            <div class="col-12"><div class="text-muted small">Subjects</div>
                <div class="fw-semibold small">{{ $subjectNames->implode(', ') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Print Buttons --}}
@if($history->isNotEmpty())
<div class="d-flex gap-2 mb-3">
    <a href="{{ $stmtRecordRoute }}?student_id={{ $student->id }}&print=a4" target="_blank"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer me-1"></i> A4 Print
    </a>
    <a href="{{ $stmtRecordRoute }}?student_id={{ $student->id }}&print=thermal" target="_blank"
       class="btn btn-outline-success btn-sm">
        <i class="bi bi-receipt me-1"></i> Thermal Print
    </a>
</div>

{{-- History Session Wise --}}
@php
    // Overall due = sirf last (current) session ka due — double count avoid
    $overallDue = $history->last()['due'] ?? 0;
@endphp
@foreach($history as $h)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center"
         style="background:#1e293b;color:white;">
        <span class="fw-semibold small">
            <i class="bi bi-calendar3 me-2"></i>{{ $h['session']->name }}
        </span>
        @if($h['carried_forward'] ?? false)
            <span class="badge bg-secondary">Carried Forward →</span>
        @elseif($h['due'] > 0)
            <span class="badge bg-danger">Due: ₹{{ number_format($h['due'],0) }}</span>
        @else
            <span class="badge bg-success">Clear ✓</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Date</th>
                    <th>Receipt No.</th>
                    <th class="text-center">Book No.</th>
                    <th class="text-end">Fine (₹)</th>
                    <th class="text-end">Disc (₹)</th>
                    <th class="text-end pe-3">Paid (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($h['invoices'] as $inv)
                <tr>
                    <td class="ps-3 small">{{ $inv->payment_date?->format('d-M-y') }}</td>
                    <td class="small fw-semibold text-primary">{{ $inv->invoice_no }}</td>
                    <td class="text-center small text-muted">—</td>
                    <td class="text-end small">0</td>
                    <td class="text-end small text-warning">{{ number_format($inv->discount ?? 0, 0) }}</td>
                    <td class="text-end pe-3 small fw-semibold text-success">{{ number_format($inv->paid_amount, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-2 small">No payments found</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="ps-3 fw-bold small">TOTAL:</td>
                    <td class="text-end fw-bold small text-warning">{{ number_format($h['total_discount'], 0) }}</td>
                    <td class="text-end pe-3 fw-bold small text-success">{{ number_format($h['total_paid'], 0) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="ps-3 text-muted small">{{ $h['session']->name }} Balance:</td>
                    <td class="text-end pe-3 fw-bold small {{ $h['due'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $h['due'] > 0 ? number_format($h['due'], 0) : '0' }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endforeach

{{-- Overall Balance --}}
<div class="card border-0 shadow-sm mb-4" style="border-left:4px solid {{ $overallDue > 0 ? '#ef4444' : '#16a34a' }}!important;">
    <div class="card-body py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">Over All Balance:</span>
        <span class="fw-bold fs-5 {{ $overallDue > 0 ? 'text-danger' : 'text-success' }}">
            {{ $overallDue > 0 ? '₹ '.number_format($overallDue, 0).' due' : 'All Clear ✓' }}
        </span>
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
                     onmouseenter="this.style.background='#f0fdf4'"
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
    hiddenId.value = id;
    searchInput.value = name;
    dropdown.style.display = 'none';
}

function loadRecord() {
    const id = hiddenId.value;
    if (!id) { alert('Please select a student first'); return; }
    window.location = `{{ $stmtRecordRoute }}?student_id=${id}`;
}

document.addEventListener('click', e => {
    if (!e.target.closest('#studentSearch') && !e.target.closest('#searchDropdown')) {
        dropdown.style.display = 'none';
    }
});
</script>
@endpush
@endsection
