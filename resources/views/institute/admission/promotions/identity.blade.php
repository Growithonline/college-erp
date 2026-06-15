@extends('institute.layout')
@section('title', 'Roll No / Form No Assignment')
@section('breadcrumb', 'Admissions / Academic Identity')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Roll No / Form No Assignment</h4>
        <small class="text-muted">Office staff har session ke liye roll no aur form no assign kare</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admissions.promote.semester') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-arrow-up-circle me-1"></i>Semester
        </a>
        <a href="{{ route('admissions.promote.session') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-calendar-arrow-up me-1"></i>Session
        </a>
        <a href="{{ route('admissions.promote.report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Report
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('warning'))
<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm">
    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm">
    @foreach($errors->all() as $e)<div><i class="bi bi-x-circle me-1"></i>{{ $e }}</div>@endforeach
</div>
@endif

{{-- Pending summary --}}
@if($pendingCount > 0)
<div class="alert alert-warning border-0 shadow-sm py-2 mb-3">
    <i class="bi bi-clock me-2"></i>
    <strong>{{ $pendingCount }}</strong> record(s) still need roll no / form no assigned
</div>
@else
<div class="alert alert-success border-0 shadow-sm py-2 mb-3">
    <i class="bi bi-check-circle me-2"></i> All records for this session are complete
</div>
@endif

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected':'' }}>
                            {{ $s->name }}{{ $s->is_active ? ' ✓':'' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Year/Part</label>
                <select name="course_part_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Parts</option>
                    @foreach($courseParts as $part)
                        <option value="{{ $part->id }}" {{ request('course_part_id') == $part->id ? 'selected':'' }}>
                            {{ $part->course->name ?? 'Course' }} - {{ $part->year_label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Pending</label>
                <select name="pending" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Records</option>
                    <option value="both" {{ request('pending')=='both' ? 'selected':'' }}>Both Missing</option>
                    <option value="roll" {{ request('pending')=='roll' ? 'selected':'' }}>Roll No Missing</option>
                    <option value="form" {{ request('pending')=='form' ? 'selected':'' }}>Form No Missing</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <input type="number" name="current_semester" min="1" max="20" value="{{ request('current_semester') }}"
                       class="form-control form-control-sm" placeholder="All">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, UID...">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Filter</button>
                <a href="{{ route('admissions.promote.identity') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Update Form --}}
<form method="POST" action="{{ route('admissions.promote.identity.bulk') }}" id="bulkForm">
@csrf
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <i class="bi bi-person-badge me-2 text-primary"></i>
            Students ({{ $identities->total() }})
        </span>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-save me-1"></i>Save All Changes
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Student</th>
                        <th>Course / Year</th>
                        <th>Session</th>
                        <th>Source</th>
                        <th style="width:140px;">Form No</th>
                        <th style="width:140px;">Roll No</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Quick Save</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($identities as $i => $ident)
                    <tr class="{{ (is_null($ident->roll_no) || is_null($ident->form_no)) ? 'table-warning bg-opacity-50' : '' }}">
                        <td class="ps-3 text-muted small">{{ $identities->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $ident->student->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $ident->student->student_uid ?? '' }}</div>
                        </td>
                        <td>
                            <div class="small">{{ $ident->course->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $ident->student->coursePart->year_label ?? '—' }}</div>
                        </td>
                        <td class="small text-muted">{{ $ident->session->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $ident->source === 'admission' ? 'bg-info bg-opacity-15 text-info' : 'bg-warning bg-opacity-15 text-warning-emphasis' }} border" style="font-size:10px;">
                                {{ ucfirst($ident->source) }}
                            </span>
                        </td>
                        <td>
                            <input type="text" name="identities[{{ $ident->id }}][form_no]"
                                   class="form-control form-control-sm {{ is_null($ident->form_no) ? 'border-warning' : '' }}"
                                   value="{{ $ident->form_no }}"
                                   placeholder="Form No..."
                                   style="width:130px;">
                        </td>
                        <td>
                            <input type="text" name="identities[{{ $ident->id }}][roll_no]"
                                   class="form-control form-control-sm {{ is_null($ident->roll_no) ? 'border-warning' : '' }}"
                                   value="{{ $ident->roll_no }}"
                                   placeholder="Roll No..."
                                   style="width:130px;">
                        </td>
                        <td class="text-center">
                            @if($ident->roll_no && $ident->form_no)
                                <span class="badge bg-success bg-opacity-15 text-success border" style="font-size:10px;">
                                    <i class="bi bi-check"></i> Complete
                                </span>
                            @elseif($ident->roll_no || $ident->form_no)
                                <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border" style="font-size:10px;">
                                    <i class="bi bi-dash"></i> Partial
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-15 text-danger border" style="font-size:10px;">
                                    <i class="bi bi-x"></i> Pending
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{-- Quick individual save --}}
                            <form method="POST" action="{{ route('admissions.promote.identity.update', $ident) }}"
                                  style="display:inline;" onsubmit="return quickSave(this, {{ $ident->id }})">
                                @csrf
                                <input type="hidden" name="_quick" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Quick Save">
                                    <i class="bi bi-save"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-person-badge fs-2 d-block mb-2"></i>No records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
        {{ $identities->withQueryString()->links() }}
        <button type="submit" class="btn btn-success btn-sm">
            <i class="bi bi-save me-1"></i>Save All Changes
        </button>
    </div>
</div>
</form>

<script>
function quickSave(form, identId) {
    // Get row values and submit
    const row = form.closest('tr');
    const rollInput = row.querySelector(`input[name="identities[${identId}][roll_no]"]`);
    const formInput = row.querySelector(`input[name="identities[${identId}][form_no]"]`);
    if (rollInput) form.querySelector('input[name="roll_no"]') || form.insertAdjacentHTML('beforeend',
        `<input type="hidden" name="roll_no" value="${rollInput.value}">`);
    if (formInput) form.querySelector('input[name="form_no"]') || form.insertAdjacentHTML('beforeend',
        `<input type="hidden" name="form_no" value="${formInput.value}">`);
    return true;
}
</script>
@endsection
