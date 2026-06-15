@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $admissionReportRoute = $isStaff ? 'staff.reports.admission' : 'reports.admission';
    $streamsRoute = $isStaff ? 'staff.reports.streams' : 'reports.streams';
    $profileRoute = $isStaff ? 'staff.admissions.show' : 'admissions.show';
@endphp
@extends($layout)
@section('title', 'Admission Report')
@section('breadcrumb', 'Reports / Admission Report')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Admission Report</h4>
        <small class="text-muted">{{ $sessionObj?->name ?? '' }} — Course-wise, source-wise statistics</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="printReport()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <a href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="bi bi-people text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Admissions</div>
                        <div class="fw-bold fs-6">{{ number_format($totalAdmissions) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-person text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Male / Female</div>
                        <div class="fw-bold fs-6">{{ $maleCount }} / {{ $femaleCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="bi bi-calendar-check text-success fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Today</div>
                        <div class="fw-bold fs-6 text-success">{{ $todayCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="bi bi-bar-chart text-warning fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Courses</div>
                        <div class="fw-bold fs-6">{{ $courseWise->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Breakdowns --}}
<div class="row g-3 mb-4">
    {{-- Course-wise with semester --}}
    @if($courseWise->isNotEmpty())
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-book me-1 text-primary"></i> Course-wise</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Course</th>
                            <th class="text-center">Sem / Year</th>
                            <th class="text-end pe-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courseWise as $courseName => $semRows)
                        @php $courseTotal = $semRows->sum('cnt'); @endphp
                        <tr class="table-light">
                            <td class="ps-3 fw-semibold" colspan="2">{{ $courseName }}</td>
                            <td class="text-end pe-3 fw-bold text-primary">{{ $courseTotal }}</td>
                        </tr>
                        @foreach($semRows as $row)
                        <tr>
                            <td class="ps-3 text-muted" style="padding-left:1.5rem!important;font-size:11px;">
                                ↳
                            </td>
                            <td class="text-center">
                                @if($row->semester > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px;">
                                        Sem {{ $row->semester }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px;">Untagged</span>
                                @endif
                            </td>
                            <td class="text-end pe-3 text-muted">{{ $row->cnt }}</td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark fw-semibold">
                        <tr>
                            <td class="ps-3" colspan="2">Total</td>
                            <td class="text-end pe-3">{{ $courseWiseRaw->sum('cnt') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Source-wise with center drill-down --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><i class="bi bi-signpost me-1 text-success"></i> Source-wise</span>
                @if($centerDetail->isNotEmpty())
                    <span class="text-muted" style="font-size:10px;"><i class="bi bi-hand-index me-1"></i>Click Center for detail</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Source</th>
                            <th class="text-end pe-3">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sourceWise as $s)
                        @php $src = $s->admission_source ?? 'direct'; @endphp
                        <tr @if($src === 'center' && $centerDetail->isNotEmpty()) style="cursor:pointer;" onclick="showCenterDetail()" @endif>
                            <td class="ps-3 small">
                                <span class="badge
                                    {{ $src === 'direct' ? 'bg-success bg-opacity-10 text-success' : '' }}
                                    {{ $src === 'center' ? 'bg-info bg-opacity-10 text-info' : '' }}
                                    {{ $src === 'channel_partner' ? 'bg-warning bg-opacity-10 text-warning' : '' }}
                                    fw-normal">
                                    {{ ucwords(str_replace('_',' ', $src)) }}
                                </span>
                                @if($src === 'center' && $centerDetail->isNotEmpty())
                                    <i class="bi bi-chevron-right ms-1 text-muted" style="font-size:9px;"></i>
                                @endif
                            </td>
                            <td class="text-end pe-3 fw-semibold">{{ $s->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr><td class="ps-3">Total</td><td class="text-end pe-3">{{ $sourceWise->sum('cnt') }}</td></tr>
                    </tfoot>
                </table>

                {{-- Center inline detail --}}
                @if($centerDetail->isNotEmpty())
                <div id="centerDetailPanel" class="border-top" style="display:none;">
                    <div class="px-3 py-2 bg-info bg-opacity-10">
                        <span class="small fw-semibold text-info">Center-wise Admission Detail</span>
                    </div>
                    @foreach($centerDetail as $centerName => $courseRows)
                    <div class="border-bottom px-3 py-2">
                        <div class="fw-semibold small text-dark mb-1">
                            <i class="bi bi-building me-1 text-info"></i>{{ $centerName }}
                            <span class="badge bg-info text-dark ms-1">{{ $courseRows->sum('cnt') }}</span>
                        </div>
                        @foreach($courseRows as $cr)
                        <div class="d-flex justify-content-between ps-3" style="font-size:11px;">
                            <span class="text-muted">↳ {{ $cr->course_name }}</span>
                            <span class="fw-semibold">{{ $cr->cnt }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Staff-wise admissions --}}
    @if(!$isStaff && isset($staffWise) && $staffWise->isNotEmpty())
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-person-badge me-1 text-primary"></i> Staff-wise</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr><th class="ps-3">Staff</th><th class="text-end pe-3">Count</th></tr>
                    </thead>
                    <tbody>
                        @foreach($staffWise as $sw)
                        <tr>
                            <td class="ps-3 small">{{ $sw->staff_name }}</td>
                            <td class="text-end pe-3 fw-semibold">{{ $sw->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr><td class="ps-3">Total</td><td class="text-end pe-3">{{ $staffWise->sum('cnt') }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Category-wise --}}
    @if($categoryWise->isNotEmpty())
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-people-fill me-1 text-warning"></i> Category-wise</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th class="ps-3">Category</th><th class="text-end pe-3">Count</th></tr></thead>
                    <tbody>
                        @foreach($categoryWise as $c)
                        <tr>
                            <td class="ps-3 small">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-normal">
                                    {{ strtoupper($c->category ?? 'Unknown') }}
                                </span>
                            </td>
                            <td class="text-end pe-3 fw-semibold">{{ $c->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-semibold">
                        <tr><td class="ps-3">Total</td><td class="text-end pe-3">{{ $categoryWise->sum('cnt') }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($admissionReportRoute) }}" id="filterForm">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ $sessionId==$s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Course</label>
                    <select name="course_id" id="courseFilter" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ request('course_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Stream</label>
                    <select name="stream_id" id="streamFilter" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" {{ request('stream_id')==$st->id ? 'selected':'' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Source</label>
                    <select name="admission_source" id="sourceFilter" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        <option value="direct"          {{ request('admission_source')=='direct'          ? 'selected':'' }}>Direct</option>
                        <option value="center"          {{ request('admission_source')=='center'          ? 'selected':'' }}>Center</option>
                        <option value="channel_partner" {{ request('admission_source')=='channel_partner' ? 'selected':'' }}>Channel Partner</option>
                    </select>
                </div>
                <div class="col-md-2" id="centerFilterWrap" style="{{ request('admission_source')==='center' ? '' : 'display:none;' }}">
                    <label class="form-label small fw-semibold">Center Name</label>
                    <select name="center_id" class="form-select form-select-sm">
                        <option value="">— All Centers —</option>
                        @foreach($centers as $cn)
                            <option value="{{ $cn->id }}" {{ request('center_id')==$cn->id ? 'selected':'' }}>{{ $cn->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2" id="partnerFilterWrap" style="{{ in_array(request('admission_source'), ['partner','channel_partner']) ? '' : 'display:none;' }}">
                    <label class="form-label small fw-semibold">Partner Name</label>
                    <select name="partner_id" class="form-select form-select-sm">
                        <option value="">— All Partners —</option>
                        @foreach($partners as $pt)
                            <option value="{{ $pt->id }}" {{ request('partner_id')==$pt->id ? 'selected':'' }}>{{ $pt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        <option value="male"   {{ request('gender')=='male'   ? 'selected':'' }}>Male</option>
                        <option value="female" {{ request('gender')=='female' ? 'selected':'' }}>Female</option>
                        <option value="other"  {{ request('gender')=='other'  ? 'selected':'' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">— All —</option>
                        @foreach(['gen'=>'GEN','obc'=>'OBC','sc'=>'SC','st'=>'ST','ews'=>'EWS'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('category')==$v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           value="{{ request('search') }}" placeholder="Name, Mobile, UID...">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route($admissionReportRoute) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                </div>
            </div>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
        </form>
    </div>
</div>

{{-- Students Table --}}
<div class="card border-0 shadow-sm" id="reportTable">
    <div class="card-body p-0">
        @if($students->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                No students found matching your filters.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Std ID</th>
                        <th>Student</th>
                        <th>Father Name</th>
                        <th>Mother Name</th>
                        <th>Course / Stream</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Admitted By</th>
                        <th>Adm. Date</th>
                        <th>Submitted</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $s)
                    <tr>
                        <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"
                                  style="font-size:10.5px;">{{ $s->student_uid ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $s->name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $s->mobile }}</div>
                        </td>
                        <td class="small">{{ $s->father_name ?: '—' }}</td>
                        <td class="small">{{ $s->mother_name ?: '—' }}</td>
                        <td>
                            <div>{{ $s->stream?->course?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $s->stream?->name ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">
                                Year {{ $s->coursePart?->year_number ?? '—' }}
                            </span>
                        </td>
                        <td>{{ ucfirst($s->gender ?? '—') }}</td>
                        <td>
                            @if($s->category)
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-normal">
                                {{ strtoupper($s->category) }}
                            </span>
                            @else —
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-success bg-opacity-10 text-success fw-normal">
                                {{ ucwords(str_replace('_',' ', $s->admission_source ?? 'direct')) }}
                            </span>
                        </td>
                        <td>
                            @if($s->admittedBy)
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-normal">{{ $s->admittedBy->name }}</span>
                            @else
                                <span class="text-muted small">Admin</span>
                            @endif
                        </td>
                        <td class="text-muted" style="white-space:nowrap;">
                            {{ $s->admission_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="text-muted" style="white-space:nowrap;">
                            {{ $s->created_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="text-center pe-3">
                            <a href="{{ route($profileRoute, $s->id) }}"
                               class="btn btn-outline-primary btn-sm py-0 px-2" title="Profile">
                                <i class="bi bi-person"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 pb-3">
            @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage])
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
// Source filter → show/hide center / partner dropdowns
document.getElementById('sourceFilter').addEventListener('change', function() {
    const centerWrap  = document.getElementById('centerFilterWrap');
    const partnerWrap = document.getElementById('partnerFilterWrap');
    const isCenter  = this.value === 'center';
    const isPartner = this.value === 'channel_partner';
    centerWrap.style.display  = isCenter  ? '' : 'none';
    partnerWrap.style.display = isPartner ? '' : 'none';
    if (!isCenter)  centerWrap.querySelector('select').value  = '';
    if (!isPartner) partnerWrap.querySelector('select').value = '';
});

// Center detail panel toggle
function showCenterDetail() {
    const panel = document.getElementById('centerDetailPanel');
    if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
}

document.getElementById('courseFilter').addEventListener('change', function() {
    const cid = this.value;
    const sel = document.getElementById('streamFilter');
    sel.innerHTML = '<option value="">Loading...</option>';
    if (!cid) { sel.innerHTML = '<option value="">— All —</option>'; return; }
    fetch(`{{ route($streamsRoute) }}?course_id=${cid}`)
        .then(r => r.json())
        .then(data => {
            sel.innerHTML = '<option value="">— All Streams —</option>';
            data.forEach(s => sel.innerHTML += `<option value="${s.id}">${s.name}</option>`);
        });
});

function printReport() {
    const head = 'Admission Report — {{ $sessionObj?->name ?? "" }}';
    const table = document.getElementById('reportTable').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head><title>Admission Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{font-size:10px;}th,td{padding:3px 6px!important;font-size:10px;}@media print{.btn{display:none!important}}</style>
    </head><body class="p-3">
    <div class="d-flex justify-content-between mb-3">
        <h5>${head}</h5>
        <div class="text-muted small">Print: ${new Date().toLocaleDateString('en-IN')}</div>
    </div>${table}</body></html>`);
    win.document.close(); win.print();
}
</script>
@endpush
