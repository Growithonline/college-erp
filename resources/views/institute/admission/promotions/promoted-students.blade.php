@extends('institute.layout')
@section('title', 'Promoted Students')
@section('breadcrumb', 'Admissions / Promoted Students')
@section('content')
@php
    $isStaff    = auth()->guard('staff')->check();
    $_rp        = $isStaff ? 'staff.admissions.promote.' : 'admissions.promote.';
    $rSession   = $_rp . 'session';
    $rReport    = $_rp . 'report';
    $rOutcomes  = $_rp . 'outcomes';
    $rPromoted  = $_rp . 'promoted-students';
    $profileRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-up-circle me-2 text-dark"></i>Promoted Students</h4>
        <small class="text-muted">Students jo is session mein the lekin promote hokar aage chale gaye</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        <a href="{{ route($rSession) }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-calendar-arrow-up me-1"></i> Session
        </a>
        <a href="{{ route($rReport) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i> Report
        </a>
        <a href="{{ route($rOutcomes) }}" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-award me-1"></i> Outcomes
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session (as of)</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) $sessionId === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}{{ $session->is_active ? ' *' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string) $courseId === (string) $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, UID, mobile...">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route($rPromoted) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small"><i class="bi bi-people me-2 text-dark"></i>Students ({{ $students->total() }})</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="min-width:110px;">Std ID</th>
                        <th style="min-width:130px;">Student</th>
                        <th style="min-width:100px;">Father</th>
                        <th style="min-width:100px;">Mother</th>
                        <th style="min-width:80px;">Roll No</th>
                        <th style="min-width:85px;">Enroll No</th>
                        <th style="min-width:80px;">UIN No</th>
                        <th>Course</th>
                        <th>Year/Sem (As Of)</th>
                        <th>Status (As Of)</th>
                        <th>Promoted To</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="ps-3" style="white-space:nowrap;">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10.5px;">
                                    {{ $student->student_uid ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $student->name }}</div>
                            </td>
                            <td class="small">{{ $student->father_name ?: '—' }}</td>
                            <td class="small">{{ $student->mother_name ?: '—' }}</td>
                            <td class="small text-muted">{{ $student->roll_no ?: '—' }}</td>
                            <td class="small text-muted">{{ $student->enrollment_no ?: '—' }}</td>
                            <td class="small text-muted">{{ $student->uin_no ?: '—' }}</td>
                            <td>
                                <div>{{ $student->stream?->course?->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $student->stream?->name ?? '—' }}</div>
                            </td>
                            <td>
                                @if($student->display_year_label)
                                    <div>{{ $student->display_year_label }}</div>
                                @endif
                                <div class="text-muted" style="font-size:11px;">Sem {{ $student->display_semester ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    {{ ucfirst($student->display_status ?? '—') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border" style="white-space:nowrap;">
                                    <i class="bi bi-arrow-up-circle me-1"></i>{{ $student->session?->name ?? '—' }}
                                </span>
                                <div class="text-muted" style="font-size:11px;">
                                    {{ $student->coursePart?->year_label ?? '—' }} · Sem {{ $student->current_semester ?? '—' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route($profileRoute, $student->id) }}"
                                   class="btn btn-xs btn-outline-primary"
                                   style="font-size:11px;padding:2px 8px;">
                                    <i class="bi bi-person me-1"></i>Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes fs-2 d-block mb-2"></i>Is session ke koi promoted students nahi mile
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0">
        {{ $students->withQueryString()->links() }}
    </div>
</div>
@endsection
