@php
    $titles = [
        'full-form'       => ['Full Form Admissions',        'Students admitted via full form', 'bi-file-earmark-person', 'primary'],
        'online'          => ['Online Admissions',           'Students from online / website', 'bi-globe', 'info'],
        'centre'          => ['Centre Admissions',           'Students admitted via centre', 'bi-building', 'success'],
        'channel-partner' => ['Channel Partner Admissions',  'Students admitted via channel partner', 'bi-people', 'warning'],
        'staff'           => ['Staff Admissions',            'Students admitted by staff', 'bi-person-badge', 'secondary'],
        'blocked'         => ['Blocked / Inactive Students', 'Inactive / Detained / Cancelled students', 'bi-slash-circle', 'danger'],
    ];
    [$pageTitle, $pageDesc, $pageIcon, $pageColor] = $titles[(string) ($type ?? '')] ?? ['Admission Report', '', 'bi-list', 'primary'];
    $centersMap  = $centersMap  ?? collect();
    $partnersMap = $partnersMap ?? collect();
    $courseTypes = $courseTypes ?? collect();
    $streams     = $streams     ?? collect();
@endphp
@extends('institute.layout')
@section('title', $pageTitle)
@section('breadcrumb', 'Students / ' . $pageTitle)

@section('content')
<style>
.sub-rpt { font-size: 11.5px; }
.sub-rpt thead th {
    background: #1e3a5f !important;
    color: #fff !important;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 8px !important;
    white-space: nowrap;
    border-color: #0d2540 !important;
}
.sub-rpt td { padding: 5px 8px !important; vertical-align: middle; }
.sub-rpt .badge { font-size: 10px; }
.sub-rpt tbody tr:hover { background: #f0f4f8 !important; }
.summary-card { border-left: 4px solid; border-radius: 8px; }
.print-header { display: none; }
@media print {
    .no-print, form, .card-footer, nav[aria-label="pagination"] { display: none !important; }
    .print-header { display: block !important; }
    body, table, .sub-rpt { font-size: 8.5px !important; }
    .sub-rpt th, .sub-rpt td { font-size: 8.5px !important; padding: 2px 4px !important; white-space: nowrap; }
    h4, h5 { font-size: 13px !important; }
    .badge { font-size: 7.5px !important; padding: 1px 3px !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>

{{-- Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi {{ $pageIcon }} text-{{ $pageColor }} me-2"></i>{{ $pageTitle }}
        </h4>
        <small class="text-muted">{{ $pageDesc }} — {{ $sessionObj?->name ?? '' }}</small>
    </div>
    <div class="d-flex gap-2">
        
        <div class="dropdown">
            <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (Print)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-2 text-secondary"></i> CSV
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
@php
    $maleCount   = $students->where('gender', 'male')->count();
    $femaleCount = $students->where('gender', 'female')->count();
    $cardColors  = ['primary' => '#0d6efd', 'info' => '#0dcaf0', 'success' => '#198754', 'warning' => '#ffc107', 'secondary' => '#6c757d', 'danger' => '#dc3545'];
    $borderColor = $cardColors[$pageColor] ?? '#0d6efd';
@endphp
<div class="row g-3 mb-3 no-print">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm summary-card" style="border-left-color:{{ $borderColor }} !important;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2" style="background:{{ $borderColor }}20;">
                        <i class="bi bi-people fs-5" style="color:{{ $borderColor }};"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Students</div>
                        <div class="fw-bold fs-4" style="color:{{ $borderColor }};">{{ number_format($total) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm summary-card" style="border-left-color:#0d6efd !important;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10">
                        <i class="bi bi-gender-male fs-5 text-primary"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Male</div>
                        <div class="fw-bold fs-4 text-primary">{{ $maleCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm summary-card" style="border-left-color:#e91e8c !important;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2" style="background:#e91e8c20;">
                        <i class="bi bi-gender-female fs-5" style="color:#e91e8c;"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Female</div>
                        <div class="fw-bold fs-4" style="color:#e91e8c;">{{ $femaleCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm summary-card" style="border-left-color:#20c997 !important;">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-2 bg-success bg-opacity-10">
                        <i class="bi bi-calendar3 fs-5 text-success"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Session</div>
                        <div class="fw-bold small text-success">{{ $sessionObj?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2 px-3">
        <form method="GET" id="srFilterForm" class="row g-2 align-items-end">

            {{-- Session --}}
            <div class="col-auto" style="min-width:110px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                <select name="session_id" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>{{ $sess->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Course Type --}}
            <div class="col-auto" style="min-width:105px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course Type</label>
                <select name="course_type_id" id="srCourseType" class="form-select form-select-sm" onchange="srFilterCourses();srAutoSubmit()">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>
                            {{ $ct->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Course --}}
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course</label>
                <select name="course_id" id="srCourse" class="form-select form-select-sm" onchange="srFilterStreams();srAutoSubmit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}"
                            data-type="{{ $c->course_type_id }}"
                            {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Stream --}}
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Stream</label>
                <select name="stream_id" id="srStream" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Streams</option>
                    @foreach($streams as $st)
                        <option value="{{ $st->id }}"
                            data-course="{{ $st->course_id }}"
                            {{ request('stream_id') == $st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Semester --}}
            <div class="col-auto" style="min-width:90px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Sem</option>
                    @foreach(range(1, 10) as $sem)
                        <option value="{{ $sem }}" {{ request('semester') == $sem ? 'selected' : '' }}>Sem {{ $sem }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status (not for blocked) --}}
            @if($type !== 'blocked')
            <div class="col-auto" style="min-width:100px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Status</option>
                    <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                    <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                    <option value="inactive"  {{ request('status') === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                    <option value="detained"  {{ request('status') === 'detained'  ? 'selected' : '' }}>Detained</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            @endif

            {{-- Centre filter --}}
            @if($type === 'centre')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Centre</label>
                <select name="center_id" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ request('center_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Channel Partner filter --}}
            @if($type === 'channel-partner')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Channel Partner</label>
                <select name="partner_id" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Partners</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Staff filter --}}
            @if($type === 'staff')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Staff</label>
                <select name="staff_id" class="form-select form-select-sm" onchange="srAutoSubmit()">
                    <option value="">All Staff</option>
                    @foreach($staffList as $st)
                        <option value="{{ $st->id }}" {{ request('staff_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Date range --}}
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" style="width:130px;">
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" style="width:130px;">
            </div>

            {{-- Search --}}
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Father / Mobile / UID"
                       class="form-control form-control-sm">
            </div>

            {{-- Per Page --}}
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Per Page</label>
                <select name="per_page" class="form-select form-select-sm" style="width:70px;" onchange="srAutoSubmit()">
                    @foreach([10,20,50,100] as $pp)
                        <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-auto d-flex gap-1 align-items-end">
                <button class="btn btn-primary btn-sm px-3"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Print header --}}
<div class="d-none d-print-block mb-3">
    <h5 class="fw-bold">{{ $pageTitle }} — {{ $sessionObj?->name ?? '' }}</h5>
    <small class="text-muted">Total: {{ $total }} &nbsp;|&nbsp; Printed: {{ now()->format('d M Y, h:i A') }}</small>
    <hr>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0 sub-rpt">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Session</th>
                        <th>Std ID</th>
                        <th>Name</th>
                        <th>Father</th>
                        <th>Mother</th>
                        <th style="white-space:nowrap;">Roll No</th>
                        <th style="white-space:nowrap;">Enroll No</th>
                        <th style="white-space:nowrap;">UIN No</th>
                        <th>Course / Stream</th>
                        <th>Sem</th>
                        <th>Admitted By</th>
                        <th>Adm. Date</th>
                        @if($type === 'full-form') <th>Submitted</th> @endif
                        @if($type === 'blocked') <th>Status</th><th>Reason</th> @endif
                        @if($type !== 'blocked') <th>Status</th> @endif
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                    @php
                        $rSrc = $student->admission_source ?? 'direct';
                        $admittedByLabel = $student->admittedBy?->name
                            ?? match($rSrc) {
                                'center'                     => ($centersMap[$student->admission_source_id]  ?? null)
                                                                ? $centersMap[$student->admission_source_id]
                                                                : 'Center',
                                'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                                                ? $partnersMap[$student->admission_source_id]
                                                                : 'Partner',
                                default                      => 'Admin / Direct',
                            };
                        $statusColor = match($student->status ?? '') {
                            'active'    => 'bg-success-subtle text-success border-success-subtle',
                            'pending'   => 'bg-warning-subtle text-warning border-warning-subtle',
                            'inactive'  => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                            'detained'  => 'bg-danger-subtle text-danger border-danger-subtle',
                            'cancelled' => 'bg-dark-subtle text-dark border-dark-subtle',
                            default     => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        };
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $students->firstItem() + $i }}</td>
                        <td style="white-space:nowrap;">
                            @if($student->session?->name)
                                <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle" style="font-size:10px;">
                                    {{ $student->session->name }}
                                </span>
                            @else —
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px;">
                                {{ $student->student_uid ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:11.5px;">{{ $student->name }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $student->mobile }}</div>
                        </td>
                        <td>{{ $student->father_name ?: '—' }}</td>
                        <td>{{ $student->mother_name ?: '—' }}</td>
                        <td style="white-space:nowrap;">{{ $student->roll_no ?: '—' }}</td>
                        <td style="white-space:nowrap;">{{ $student->enrollment_no ?: '—' }}</td>
                        <td style="white-space:nowrap;">{{ $student->uin_no ?: '—' }}</td>
                        <td>
                            <div style="font-size:11px;">{{ $student->stream?->course?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $student->stream?->name ?? '' }}</div>
                        </td>
                        <td class="text-center">
                            @if($student->current_semester)
                                <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">
                                    S{{ $student->current_semester }}
                                </span>
                            @else —
                            @endif
                        </td>
                        <td style="max-width:150px; word-break:break-word;">
                            <div style="font-size:11px;">{{ $admittedByLabel }}</div>
                        </td>
                        <td style="white-space:nowrap;">{{ $student->admission_date?->format('d M Y') ?? '—' }}</td>
                        @if($type === 'full-form')
                            <td style="white-space:nowrap;">{{ $student->created_at?->format('d M Y') ?? '—' }}</td>
                        @endif
                        @if($type === 'blocked')
                            <td>
                                <span class="badge border {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $student->status ?? '')) }}
                                </span>
                            </td>
                            <td class="text-muted" style="max-width:160px;word-break:break-word;font-size:10px;">
                                {{ $student->status_reason ?: '—' }}
                            </td>
                        @else
                            <td>
                                <span class="badge border {{ $statusColor }}">
                                    {{ ucfirst($student->status ?? 'pending') }}
                                </span>
                            </td>
                        @endif
                        <td class="no-print">
                            <a href="{{ route('admissions.show', $student) }}"
                               class="btn btn-outline-primary py-0 px-2" style="font-size:11px;">
                                <i class="bi bi-person"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="18" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($students->hasPages())
<div class="mt-3 d-flex justify-content-between align-items-center no-print">
    <small class="text-muted">
        Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students
    </small>
    {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    // Course Type → Course filter
    function srFilterCourses() {
        var typeId = document.getElementById('srCourseType').value;
        var courseEl = document.getElementById('srCourse');
        var opts = courseEl.querySelectorAll('option');
        opts.forEach(function (o) {
            if (!o.value) return;
            o.hidden = typeId && o.dataset.type != typeId;
        });
        if (courseEl.options[courseEl.selectedIndex]?.hidden) {
            courseEl.value = '';
        }
        srFilterStreams();
    }

    // Course → Stream filter
    function srFilterStreams() {
        var courseId = document.getElementById('srCourse').value;
        var streamEl = document.getElementById('srStream');
        var opts = streamEl.querySelectorAll('option');
        opts.forEach(function (o) {
            if (!o.value) return;
            o.hidden = courseId && o.dataset.course != courseId;
        });
        if (streamEl.options[streamEl.selectedIndex]?.hidden) {
            streamEl.value = '';
        }
    }

    function srAutoSubmit() {
        document.getElementById('srFilterForm').submit();
    }

    // Expose for onchange handlers
    window.srFilterCourses = srFilterCourses;
    window.srFilterStreams  = srFilterStreams;
    window.srAutoSubmit    = srAutoSubmit;

    // Restore filter state on page load
    srFilterCourses();
    srFilterStreams();
})();
</script>
@endpush
