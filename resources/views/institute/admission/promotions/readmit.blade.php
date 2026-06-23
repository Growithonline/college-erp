@extends('institute.layout')
@section('title', 'Re-Admission — ' . $student->name)
@section('breadcrumb', 'Admissions / Re-Admission')
@section('content')
@php
    $isStaff     = auth()->guard('staff')->check();
    $_rp         = $isStaff ? 'staff.admissions.promote.' : 'admissions.promote.';
    $rSemester   = $_rp . 'semester';
    $rReadmitDo  = $_rp . 'readmit.do';

    $statusColors = [
        'passed_out' => ['bg' => '#f0fdf4', 'border' => '#86efac', 'text' => '#166534', 'badge' => 'success'],
        'backlog'    => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'text' => '#92400e', 'badge' => 'warning'],
        'failed'     => ['bg' => '#fff1f0', 'border' => '#fca5a5', 'text' => '#991b1b', 'badge' => 'danger'],
        'dropped'    => ['bg' => '#f8f9fa', 'border' => '#dee2e6', 'text' => '#495057', 'badge' => 'secondary'],
    ];
    $sc = $statusColors[$student->status] ?? $statusColors['dropped'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-check me-2 text-success"></i>Re-Admission</h4>
        <small class="text-muted">Reinstate a terminal student to active status in a new session</small>
    </div>
    <a href="{{ route($rSemester) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Promotions
    </a>
</div>

{{-- Student info card --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                     style="width:52px;height:52px;font-size:20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
            </div>
            <div class="col">
                <div class="fw-bold fs-5">{{ $student->name }}</div>
                <div class="text-muted small">
                    {{ $student->student_uid ?? 'No UID' }}
                    &nbsp;·&nbsp; {{ $student->stream?->course?->name ?? 'Unknown Course' }}
                    &nbsp;·&nbsp; {{ $student->stream?->name ?? '' }}
                </div>
                <div class="text-muted small">
                    Last session: {{ $student->session?->name ?? '—' }}
                    &nbsp;·&nbsp; Sem {{ $student->current_semester ?? '—' }}
                    @if($student->father_name) &nbsp;·&nbsp; Father: {{ $student->father_name }} @endif
                </div>
            </div>
            <div class="col-auto">
                <span class="badge bg-{{ $sc['badge'] }} fs-6 px-3 py-2">
                    {{ ucwords(str_replace('_', ' ', $student->status)) }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-sm alert-dismissible fade show"
     style="background:#f0fdf4;border:1px solid #86efac;" role="alert">
    <div style="width:32px;height:32px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:15px;"></i>
    </div>
    <div class="flex-grow-1" style="font-size:13px;color:#166534;font-weight:500;padding-top:5px;">{{ session('success') }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:11px;"></button>
</div>
@endif
@if($errors->any())
<div class="d-flex align-items-start gap-3 mb-3 p-3 rounded-3 shadow-sm"
     style="background:#fff1f0;border:1px solid #fca5a5;">
    <div style="width:32px;height:32px;border-radius:8px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
        <i class="bi bi-x-circle-fill" style="color:#dc2626;font-size:15px;"></i>
    </div>
    <div style="flex-grow:1;">
        @foreach($errors->all() as $e)
        <div style="font-size:13px;color:#991b1b;font-weight:500;{{ !$loop->first ? 'margin-top:4px;' : '' }}">{{ $e }}</div>
        @endforeach
    </div>
</div>
@endif

{{-- Re-admission form --}}
<div class="card border-0 shadow-sm">
    <div class="card-header py-2"
         style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;">
        <i class="bi bi-person-check me-1"></i>Re-Admission Details
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route($rReadmitDo, $student) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Target Session <span class="text-danger">*</span></label>
                    <select name="to_session_id" class="form-select form-select-sm" required>
                        <option value="">— Select Session —</option>
                        @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ old('to_session_id') == $s->id ? 'selected' : ($s->is_active ? 'selected' : '') }}>
                            {{ $s->name }}{{ $s->is_active ? ' ★' : '' }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text">Session to re-admit the student into</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Year / Part <span class="text-danger">*</span></label>
                    <select name="course_part_id" class="form-select form-select-sm" required>
                        <option value="">— Select Year/Part —</option>
                        @foreach($courseParts as $part)
                        <option value="{{ $part->id }}"
                                data-year="{{ $part->year_number }}"
                                {{ old('course_part_id') == $part->id ? 'selected' : ($student->course_part_id == $part->id ? 'selected' : '') }}>
                            {{ $part->part_name }} (Year {{ $part->year_number }})
                        </option>
                        @endforeach
                        @if($courseParts->isEmpty())
                        <option disabled>No course parts found for this course</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester <span class="text-danger">*</span></label>
                    <input type="number" name="current_semester" class="form-control form-control-sm"
                           min="1" max="20" required
                           value="{{ old('current_semester', $student->current_semester ?? 1) }}">
                    <div class="form-text">Semester to re-admit into</div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Re-Admission Reason</label>
                    <input type="text" name="remarks" class="form-control form-control-sm"
                           placeholder="e.g. Backlog cleared"
                           value="{{ old('remarks') }}">
                </div>

                <div class="col-12">
                    <div class="alert alert-warning border-0 py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Note:</strong> Re-admission sets the student to <strong>Active</strong> status in the
                        selected session and year. A Promotion Log entry of type <em>Re-Admission</em> will be created.
                        Outstanding dues from previous sessions are <strong>not</strong> automatically carried forward —
                        handle them via fee collection if needed.
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm fw-semibold"
                            onclick="return confirm('Re-admit {{ addslashes($student->name) }} as active student?')">
                        <i class="bi bi-person-check me-1"></i>Confirm Re-Admission
                    </button>
                    <a href="{{ route($rSemester) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
