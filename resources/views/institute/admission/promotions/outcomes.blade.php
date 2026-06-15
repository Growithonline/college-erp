@extends('institute.layout')
@section('title', 'Final Outcomes')
@section('breadcrumb', 'Admissions / Final Outcomes')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-dark"></i>Final Outcomes</h4>
        <small class="text-muted">Passed out, backlog, failed aur dropped students ki consolidated list</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        <a href="{{ route('admissions.promote.session') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-calendar-arrow-up me-1"></i> Session
        </a>
        <a href="{{ route('admissions.promote.report') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i> Report
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) $sessionId === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}{{ $session->is_active ? ' *' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Outcome</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['passed_out' => 'Passed Out', 'backlog' => 'Backlog', 'failed' => 'Failed', 'dropped' => 'Dropped'] as $value => $label)
                        <option value="{{ $value }}" {{ (string) $status === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                <a href="{{ route('admissions.promote.outcomes') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Outcome</th>
                        <th class="text-end">Due</th>
                        <th>Updated By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $log = $logs->get($student->id)?->first();
                            $outcome = $log?->terminal_status ?: $student->status;
                        @endphp
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
                            <td>{{ $student->session?->name ?? '—' }}</td>
                            <td>
                                @if($student->coursePart?->year_label)
                                    <div>{{ $student->coursePart->year_label }}</div>
                                @endif
                                <div class="text-muted" style="font-size:11px;">Sem {{ $student->current_semester ?? '—' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-dark-subtle text-dark border">{{ ucwords(str_replace('_', ' ', $outcome)) }}</span>
                            </td>
                            <td class="text-end">
                                @if((float) ($log?->dues_carried_forward ?? 0) > 0)
                                    <span class="text-danger fw-semibold">₹ {{ number_format((float) $log->dues_carried_forward, 2) }}</span>
                                @else
                                    <span class="text-success">Clear</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $log?->promoted_by ?? '—' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ ucfirst($log?->promoted_by_role ?? '') }}</div>
                            </td>
                            <td>
                                <div>{{ $log?->created_at?->format('d M Y') ?? '—' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $log?->created_at?->format('h:i A') ?? '' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes fs-2 d-block mb-2"></i>No final outcome students found
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
