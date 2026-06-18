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
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route($admissionReportRoute) }}" id="filterForm">
            <div class="row g-2 align-items-end">

                {{-- Session --}}
                <div class="col-auto" style="min-width:110px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ $sessionId==$sess->id ? 'selected':'' }}>{{ $sess->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Course Type --}}
                @if($courseTypes->isNotEmpty())
                <div class="col-auto" style="min-width:115px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course Type</label>
                    <select name="course_type_id" id="arFilterCourseType" class="form-select form-select-sm"
                            onchange="arFilterCourses(this.value); arFilterStreams('');">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id')==$ct->id ? 'selected':'' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Course --}}
                <div class="col-auto" style="min-width:150px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course</label>
                    <select name="course_id" id="arFilterCourse" class="form-select form-select-sm"
                            onchange="arFilterStreams(this.value);">
                        <option value="">All Courses</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" data-type="{{ $c->course_type_id }}"
                                    {{ request('course_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Stream --}}
                <div class="col-auto" style="min-width:135px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Stream</label>
                    <select name="stream_id" id="arFilterStream" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All Streams</option>
                        @foreach($streams as $st)
                            <option value="{{ $st->id }}" data-course="{{ $st->course_id }}"
                                    {{ request('stream_id')==$st->id ? 'selected':'' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Semester --}}
                <div class="col-auto" style="min-width:90px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Semester</label>
                    <select name="current_semester" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All Sem</option>
                        @for($sem = 1; $sem <= 10; $sem++)
                            <option value="{{ $sem }}" {{ request('current_semester')==$sem ? 'selected':'' }}>Sem {{ $sem }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Source --}}
                <div class="col-auto" style="min-width:130px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Source</label>
                    <select name="admission_source" id="arSourceFilter" class="form-select form-select-sm"
                            onchange="arSourceChanged(this.value);">
                        <option value="">All Sources</option>
                        <option value="direct"          {{ request('admission_source')=='direct'          ? 'selected':'' }}>Direct</option>
                        <option value="center"          {{ request('admission_source')=='center'          ? 'selected':'' }}>Center</option>
                        <option value="channel_partner" {{ request('admission_source')=='channel_partner' ? 'selected':'' }}>Channel Partner</option>
                    </select>
                </div>

                {{-- Center (conditional) --}}
                <div id="arCenterWrap" style="{{ request('admission_source')==='center' ? '' : 'display:none;' }}">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Center</label>
                    <select name="center_id" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All Centers</option>
                        @foreach($centers as $cn)
                            <option value="{{ $cn->id }}" {{ request('center_id')==$cn->id ? 'selected':'' }}>{{ $cn->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Partner (conditional) --}}
                <div id="arPartnerWrap" style="{{ in_array(request('admission_source'),['partner','channel_partner']) ? '' : 'display:none;' }}">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Partner</label>
                    <select name="partner_id" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All Partners</option>
                        @foreach($partners as $pt)
                            <option value="{{ $pt->id }}" {{ request('partner_id')==$pt->id ? 'selected':'' }}>{{ $pt->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Gender --}}
                <div class="col-auto" style="min-width:95px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Gender</label>
                    <select name="gender" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All</option>
                        <option value="male"   {{ request('gender')=='male'   ? 'selected':'' }}>Male</option>
                        <option value="female" {{ request('gender')=='female' ? 'selected':'' }}>Female</option>
                        <option value="other"  {{ request('gender')=='other'  ? 'selected':'' }}>Other</option>
                    </select>
                </div>

                {{-- Category --}}
                <div class="col-auto" style="min-width:90px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Category</label>
                    <select name="category" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All</option>
                        @foreach(['gen'=>'GEN','obc'=>'OBC','sc'=>'SC','st'=>'ST','ews'=>'EWS'] as $v=>$l)
                            <option value="{{ $v }}" {{ request('category')==$v ? 'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-auto" style="min-width:105px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="arAutoSubmit()">
                        <option value="">All Status</option>
                        <option value="pending"   {{ request('status')==='pending'   ? 'selected':'' }}>Pending</option>
                        <option value="active"    {{ request('status')==='active'    ? 'selected':'' }}>Active</option>
                        <option value="inactive"  {{ request('status')==='inactive'  ? 'selected':'' }}>Inactive</option>
                        <option value="detained"  {{ request('status')==='detained'  ? 'selected':'' }}>Detained</option>
                        <option value="cancelled" {{ request('status')==='cancelled' ? 'selected':'' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" style="width:128px;">
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" style="width:128px;">
                </div>

                {{-- Search --}}
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               value="{{ request('search') }}" placeholder="Name, Mobile, UID...">
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="col-auto d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route($admissionReportRoute) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
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
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:12.5px;">
                <thead style="background:#1e3a5f; color:#fff;">
                    <tr>
                        <th class="ps-2 py-2" style="white-space:nowrap;">#</th>
                        <th style="white-space:nowrap;">Session</th>
                        <th style="white-space:nowrap;">Student ID</th>
                        <th style="white-space:nowrap;">Student Name</th>
                        <th style="white-space:nowrap;">Father Name</th>
                        <th style="white-space:nowrap;">Mother Name</th>
                        <th style="white-space:nowrap;">Roll No</th>
                        <th style="white-space:nowrap;">Enroll No</th>
                        <th style="white-space:nowrap;">UIN No</th>
                        <th style="white-space:nowrap;">Course / Stream</th>
                        <th style="white-space:nowrap;">Year/Sem</th>
                        <th style="white-space:nowrap;">Admitted By</th>
                        <th style="white-space:nowrap;">Source</th>
                        <th style="white-space:nowrap;">Adm. Date</th>
                        <th style="white-space:nowrap;">Status</th>
                        <th class="text-center pe-2" style="white-space:nowrap;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $s)
                    @php
                        $rSrc      = $s->admission_source ?? 'direct';
                        $srcName   = match($rSrc) {
                            'center'                     => ($centersMap[$s->admission_source_id]  ?? null) ? 'Center: '  . $centersMap[$s->admission_source_id]  : 'Center',
                            'partner', 'channel_partner' => ($partnersMap[$s->admission_source_id] ?? null) ? 'Partner: ' . $partnersMap[$s->admission_source_id] : 'Partner',
                            default                      => 'Direct',
                        };
                        $admittedByLabel = $s->admittedBy?->name ?? match($rSrc) {
                            'center'                     => $srcName,
                            'partner', 'channel_partner' => $srcName,
                            default                      => 'Admin / Direct',
                        };
                        $admittedByBadge = match(true) {
                            (bool) $s->admittedBy                             => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                            in_array($rSrc, ['center'])                       => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                            in_array($rSrc, ['partner','channel_partner'])    => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
                            default                                           => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
                        };
                        $srcBadge  = match($rSrc) {
                            'center'                     => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                            'partner', 'channel_partner' => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
                            default                      => 'bg-success bg-opacity-10 text-success border border-success-subtle',
                        };
                        $srcShort  = match($rSrc) {
                            'center'                     => 'Center',
                            'partner', 'channel_partner' => 'Partner',
                            default                      => 'Direct',
                        };
                        $statusColor = match($s->status ?? 'pending') {
                            'active'    => 'bg-success bg-opacity-10 text-success',
                            'inactive'  => 'bg-secondary bg-opacity-10 text-secondary',
                            'detained'  => 'bg-danger bg-opacity-10 text-danger',
                            'cancelled' => 'bg-dark bg-opacity-10 text-dark',
                            default     => 'bg-warning bg-opacity-10 text-warning',
                        };
                    @endphp
                    <tr>
                        <td class="ps-2 fw-semibold text-muted">{{ $students->firstItem() + $i }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle fw-semibold" style="font-size:10px;">
                                <i class="bi bi-calendar3 me-1"></i>{{ $s->session?->name ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-semibold" style="font-size:10.5px;">
                                {{ $s->student_uid ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="white-space:nowrap;">{{ $s->name }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $s->mobile }}</div>
                        </td>
                        <td class="fw-semibold" style="color:#444;">{{ $s->father_name ?: '—' }}</td>
                        <td class="fw-semibold" style="color:#444;">{{ $s->mother_name ?: '—' }}</td>
                        <td class="fw-semibold">{{ $s->roll_no ?: '—' }}</td>
                        <td class="fw-semibold">{{ $s->enrollment_no ?: '—' }}</td>
                        <td class="fw-semibold">{{ $s->uin_no ?: '—' }}</td>
                        <td>
                            <div class="fw-semibold" style="white-space:nowrap;">{{ $s->stream?->course?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $s->stream?->name ?? '' }}</div>
                        </td>
                        <td style="white-space:nowrap;">
                            <span class="fw-semibold">{{ $s->resolved_year_label ?? '—' }}</span>
                            @if($s->current_semester)
                                <span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size:9px;">S{{ $s->current_semester }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $admittedByBadge }} fw-semibold" style="font-size:10px; white-space:normal; max-width:130px; word-break:break-word;">
                                {{ $admittedByLabel }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $srcBadge }} fw-semibold" style="font-size:10px; white-space:normal; max-width:120px; word-break:break-word;">
                                {{ $srcName }}
                            </span>
                        </td>
                        <td class="fw-semibold" style="white-space:nowrap; color:#444;">
                            {{ $s->admission_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $statusColor }} fw-semibold" style="font-size:10px;">
                                {{ ucfirst($s->status ?? 'pending') }}
                            </span>
                        </td>
                        <td class="text-center pe-2">
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
(function () {
    function getEl(id) { return document.getElementById(id); }

    function arFilterOptions(selectId, dataAttr, val) {
        const sel = getEl(selectId);
        if (!sel) return;
        let stillVisible = false;
        Array.from(sel.options).forEach(function (opt) {
            if (opt.value === '') return;
            const match = !val || opt.dataset[dataAttr] === String(val);
            opt.hidden   = !match;
            opt.disabled = !match;
            if (match && opt.selected) stillVisible = true;
        });
        if (!stillVisible) sel.value = '';
    }

    window.arFilterCourses = function (courseTypeId) {
        arFilterOptions('arFilterCourse', 'type', courseTypeId);
        const cVal = getEl('arFilterCourse') ? getEl('arFilterCourse').value : '';
        arFilterOptions('arFilterStream', 'course', cVal);
        arAutoSubmit();
    };

    window.arFilterStreams = function (courseId) {
        arFilterOptions('arFilterStream', 'course', courseId);
        arAutoSubmit();
    };

    window.arSourceChanged = function (val) {
        const cWrap = getEl('arCenterWrap');
        const pWrap = getEl('arPartnerWrap');
        if (cWrap) { cWrap.style.display = val === 'center' ? '' : 'none'; if (val !== 'center') { const s = cWrap.querySelector('select'); if(s) s.value=''; } }
        if (pWrap) { pWrap.style.display = val === 'channel_partner' ? '' : 'none'; if (val !== 'channel_partner') { const s = pWrap.querySelector('select'); if(s) s.value=''; } }
        arAutoSubmit();
    };

    window.arAutoSubmit = function () {
        const form = getEl('filterForm');
        if (form) form.submit();
    };

    // Center detail panel toggle
    window.showCenterDetail = function () {
        const panel = getEl('centerDetailPanel');
        if (panel) panel.style.display = panel.style.display === 'none' ? '' : 'none';
    };

    // On page load: restore dropdown visibility
    document.addEventListener('DOMContentLoaded', function () {
        const ctVal = getEl('arFilterCourseType') ? getEl('arFilterCourseType').value : '';
        const cVal  = getEl('arFilterCourse')     ? getEl('arFilterCourse').value     : '';
        if (ctVal) arFilterOptions('arFilterCourse', 'type', ctVal);
        if (cVal)  arFilterOptions('arFilterStream', 'course', cVal);
    });
}());

function printReport() {
    const head  = 'Admission Report — {{ $sessionObj?->name ?? "" }}';
    const table = document.getElementById('reportTable').innerHTML;
    const win   = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head><title>Admission Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>body{font-size:10px;}th,td{padding:3px 5px!important;font-size:10px;}@media print{.btn{display:none!important}}</style>
    </head><body class="p-3">
    <div class="d-flex justify-content-between mb-3">
        <h5>${head}</h5>
        <div class="text-muted small">Print: ${new Date().toLocaleDateString('en-IN')}</div>
    </div>${table}</body></html>`);
    win.document.close(); win.print();
}
</script>
@endpush
