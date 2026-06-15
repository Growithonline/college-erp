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
@endphp
@extends('institute.layout')
@section('title', $pageTitle)
@section('breadcrumb', 'Students / ' . $pageTitle)

@section('content')
<style>
.sub-rpt { font-size: 11.5px; }
.sub-rpt th { font-size: 11px; font-weight: 700; padding: 4px 6px !important; white-space: nowrap; }
.sub-rpt td { padding: 4px 6px !important; vertical-align: middle; }
.sub-rpt .badge { font-size: 10px; }
.print-header { display: none; }
@media print {
    .no-print, form, .card-footer, nav[aria-label="pagination"] { display: none !important; }
    .print-header { display: block !important; }
    body, table, .sub-rpt { font-size: 8.5px !important; }
    .sub-rpt th, .sub-rpt td { font-size: 8.5px !important; padding: 2px 4px !important; white-space: nowrap; }
    h4, h5 { font-size: 13px !important; }
    .badge { font-size: 7.5px !important; padding: 1px 3px !important; border: none !important; background: none !important; color: inherit !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi {{ $pageIcon }} text-{{ $pageColor }} me-2"></i>{{ $pageTitle }}
        </h4>
        <small class="text-muted">{{ $pageDesc }} — {{ $sessionObj?->name ?? '' }}</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Summary Card --}}
<div class="row g-3 mb-3 no-print">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-{{ $pageColor }} bg-opacity-10 p-2">
                        <i class="bi bi-people text-{{ $pageColor }} fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total</div>
                        <div class="fw-bold fs-5">{{ number_format($total) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-calendar3 text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Session</div>
                        <div class="fw-bold small">{{ $sessionObj?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-1" style="min-width:110px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>{{ $sess->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto" style="min-width:90px;">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="">All Sem</option>
                    @foreach(range(1, 8) as $sem)
                        <option value="{{ $sem }}" {{ request('semester') == $sem ? 'selected' : '' }}>Sem {{ $sem }}</option>
                    @endforeach
                </select>
            </div>

            @if($type === 'centre')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Centre</label>
                <select name="center_id" class="form-select form-select-sm">
                    <option value="">All Centres</option>
                    @foreach($centers as $c)
                        <option value="{{ $c->id }}" {{ request('center_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($type === 'channel-partner')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Channel Partner</label>
                <select name="partner_id" class="form-select form-select-sm">
                    <option value="">All Partners</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($type === 'staff')
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Staff</label>
                <select name="staff_id" class="form-select form-select-sm">
                    <option value="">All Staff</option>
                    @foreach($staffList as $st)
                        <option value="{{ $st->id }}" {{ request('staff_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" style="width:130px;">
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" style="width:130px;">
            </div>
            <div class="col-md-2">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Father / Mobile / UID"
                       class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Per Page</label>
                <select name="per_page" class="form-select form-select-sm" style="width:70px;">
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

{{-- Print header (only visible on print) --}}
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
                <thead class="table-dark">
                    <tr>
                        <th class="ps-2">#</th>
                        <th>Std ID</th>
                        <th>Name</th>
                        <th>Father</th>
                        <th>Mother</th>
                        <th>Mobile</th>
                        <th>Course / Stream</th>
                        <th>Sem</th>
                        <th>Adm. Date</th>
                        <th>Submitted</th>
                        @if($type === 'centre')          <th>Centre</th> @endif
                        @if($type === 'channel-partner') <th>Partner</th> @endif
                        @if($type === 'staff')           <th>Staff</th> @endif
                        @if($type === 'blocked')         <th>Status</th><th>Reason</th> @endif
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                    @php
                        $sourceName = '';
                        if ($type === 'centre')          $sourceName = $centersMap[$student->admission_source_id] ?? '—';
                        if ($type === 'channel-partner') $sourceName = $partnersMap[$student->admission_source_id] ?? '—';
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
                        <td class="ps-2 text-muted">{{ $students->firstItem() + $i }}</td>
                        <td style="white-space:nowrap;">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px;">
                                {{ $student->student_uid ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:11.5px;">{{ $student->name }}</div>
                            <div class="text-muted" style="font-size:10px;">{{ $student->gender ? ucfirst($student->gender) : '' }}</div>
                        </td>
                        <td>{{ $student->father_name ?: '—' }}</td>
                        <td>{{ $student->mother_name ?: '—' }}</td>
                        <td style="white-space:nowrap;">{{ $student->mobile }}</td>
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
                        <td style="white-space:nowrap;">{{ $student->admission_date?->format('d M Y') ?? '—' }}</td>
                        <td style="white-space:nowrap;">{{ $student->created_at?->format('d M Y') ?? '—' }}</td>
                        @if($type === 'centre')
                            <td>{{ $sourceName }}</td>
                        @endif
                        @if($type === 'channel-partner')
                            <td>{{ $sourceName }}</td>
                        @endif
                        @if($type === 'staff')
                            <td>{{ $student->admittedBy?->name ?? '—' }}</td>
                        @endif
                        @if($type === 'blocked')
                            <td>
                                <span class="badge border {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $student->status ?? '')) }}
                                </span>
                            </td>
                            <td class="text-muted" style="max-width:160px;word-break:break-word;">
                                {{ $student->status_reason ?: '—' }}
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
                        <td colspan="14" class="text-center py-4 text-muted">
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
