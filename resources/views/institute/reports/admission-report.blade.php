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
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0d6efd!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Total Admissions</div>
                        <div class="fw-bold" style="font-size:28px; color:#0d6efd; line-height:1;">{{ number_format($totalAdmissions) }}</div>
                        <div class="text-muted mt-1" style="font-size:10.5px;">Session: {{ $sessionObj?->name ?? 'All' }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:46px;height:46px;background:rgba(13,110,253,.1);">
                        <i class="bi bi-people-fill text-primary" style="font-size:20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0dcaf0!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Male / Female</div>
                        <div class="fw-bold" style="font-size:24px; color:#0dcaf0; line-height:1;">{{ $maleCount }} / {{ $femaleCount }}</div>
                        @php $totalGender = $maleCount + $femaleCount; @endphp
                        @if($totalGender > 0)
                        <div class="progress mt-2" style="height:4px; border-radius:2px;">
                            <div class="progress-bar bg-info" style="width:{{ round($maleCount/$totalGender*100) }}%"></div>
                            <div class="progress-bar bg-warning" style="width:{{ round($femaleCount/$totalGender*100) }}%"></div>
                        </div>
                        <div class="text-muted mt-1" style="font-size:10px;">
                            <span class="text-info fw-semibold">M {{ $totalGender > 0 ? round($maleCount/$totalGender*100) : 0 }}%</span>
                            &nbsp;
                            <span class="text-warning fw-semibold">F {{ $totalGender > 0 ? round($femaleCount/$totalGender*100) : 0 }}%</span>
                        </div>
                        @endif
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:46px;height:46px;background:rgba(13,202,240,.1);">
                        <i class="bi bi-gender-ambiguous text-info" style="font-size:20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #198754!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Today's Admissions</div>
                        <div class="fw-bold" style="font-size:28px; color:#198754; line-height:1;">{{ $todayCount }}</div>
                        <div class="text-muted mt-1" style="font-size:10.5px;">{{ now()->format('d M Y') }}</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:46px;height:46px;background:rgba(25,135,84,.1);">
                        <i class="bi bi-calendar-check-fill text-success" style="font-size:20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #ffc107!important; border-radius:10px;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted mb-1" style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px;">Courses Active</div>
                        <div class="fw-bold" style="font-size:28px; color:#ffc107; line-height:1;">{{ $courseWise->count() }}</div>
                        <div class="text-muted mt-1" style="font-size:10.5px;">{{ $sourceWise->count() }} source{{ $sourceWise->count() != 1 ? 's' : '' }} of admission</div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:46px;height:46px;background:rgba(255,193,7,.1);">
                        <i class="bi bi-mortarboard-fill text-warning" style="font-size:20px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Breakdowns --}}
<div class="row g-3 mb-3">
    {{-- Course-wise with semester --}}
    @if($courseWise->isNotEmpty())
    @php $grandTotal = $courseWiseRaw->sum('cnt'); @endphp
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-book me-1"></i> Course-wise</span>
                <span class="badge bg-white text-primary fw-bold" style="font-size:11px;">{{ $grandTotal }} total</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Course</th>
                            <th class="text-center py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Sem</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courseWise as $courseName => $semRows)
                        @php $courseTotal = $semRows->sum('cnt'); $pct = $grandTotal > 0 ? round($courseTotal/$grandTotal*100) : 0; @endphp
                        <tr style="background:#f8fafc;">
                            <td class="ps-3 fw-semibold" colspan="2" style="font-size:12px;">
                                {{ $courseName }}
                                <div class="progress mt-1" style="height:3px; border-radius:2px; width:80%;">
                                    <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-end pe-3 fw-bold text-primary" style="font-size:13px; vertical-align:middle;">{{ $courseTotal }}</td>
                        </tr>
                        @foreach($semRows as $row)
                        <tr>
                            <td class="ps-4 text-muted" style="font-size:11px; border-left:3px solid #e0e8f4;">
                                <i class="bi bi-arrow-return-right me-1" style="font-size:9px;"></i>
                                @if($row->semester > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:9.5px;">Sem {{ $row->semester }}</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:9.5px;">Untagged</span>
                                @endif
                            </td>
                            <td></td>
                            <td class="text-end pe-3 text-muted fw-semibold" style="font-size:11px;">{{ $row->cnt }}</td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                    <tfoot style="background:#1e3a5f; color:#fff;">
                        <tr>
                            <td class="ps-3 py-2 fw-semibold" colspan="2">Total</td>
                            <td class="text-end pe-3 py-2 fw-bold">{{ $grandTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Source-wise with center drill-down --}}
    @php $sourceTotal = $sourceWise->sum('cnt'); @endphp
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-signpost me-1"></i> Source-wise</span>
                @if($centerDetail->isNotEmpty())
                    <span class="text-white-50" style="font-size:10px; cursor:help;" title="Click Center row for detail">
                        <i class="bi bi-info-circle me-1"></i>Click Center
                    </span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Source</th>
                            <th class="text-center py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">%</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sourceWise as $sw)
                        @php
                            $src = $sw->admission_source ?? 'direct';
                            $pct = $sourceTotal > 0 ? round($sw->cnt / $sourceTotal * 100) : 0;
                            [$srcLabel, $srcBg, $srcBar] = match($src) {
                                'center'          => ['Center',          'bg-info bg-opacity-10 text-info border border-info-subtle',       'bg-info'],
                                'channel_partner' => ['Channel Partner', 'bg-warning bg-opacity-10 text-warning border border-warning-subtle','bg-warning'],
                                default           => ['Direct',          'bg-success bg-opacity-10 text-success border border-success-subtle','bg-success'],
                            };
                        @endphp
                        <tr @if($src === 'center' && $centerDetail->isNotEmpty()) style="cursor:pointer;" onclick="showCenterDetail()" @endif>
                            <td class="ps-3 py-2">
                                <span class="badge {{ $srcBg }} fw-semibold" style="font-size:10.5px;">{{ $srcLabel }}</span>
                                @if($src === 'center' && $centerDetail->isNotEmpty())
                                    <i class="bi bi-chevron-down ms-1 text-info" style="font-size:9px;" id="centerChevron"></i>
                                @endif
                                <div class="progress mt-1" style="height:3px; border-radius:2px; width:90%;">
                                    <div class="progress-bar {{ $srcBar }}" style="width:{{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-center text-muted" style="font-size:11px; vertical-align:middle;">{{ $pct }}%</td>
                            <td class="text-end pe-3 fw-bold" style="font-size:13px; vertical-align:middle;">{{ $sw->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#1e3a5f; color:#fff;">
                        <tr>
                            <td class="ps-3 py-2 fw-semibold" colspan="2">Total</td>
                            <td class="text-end pe-3 py-2 fw-bold">{{ $sourceTotal }}</td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Center inline detail --}}
                @if($centerDetail->isNotEmpty())
                <div id="centerDetailPanel" class="border-top" style="display:none;">
                    <div class="px-3 py-2 d-flex align-items-center gap-2" style="background:rgba(13,202,240,.08);">
                        <i class="bi bi-building text-info"></i>
                        <span class="fw-semibold text-info" style="font-size:12px;">Center-wise Breakdown</span>
                    </div>
                    @foreach($centerDetail as $centerName => $courseRows)
                    <div class="px-3 py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold" style="font-size:12px; color:#1e3a5f;">
                                <i class="bi bi-geo-alt me-1 text-info"></i>{{ $centerName }}
                            </span>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle fw-bold" style="font-size:10.5px;">{{ $courseRows->sum('cnt') }}</span>
                        </div>
                        @foreach($courseRows as $cr)
                        <div class="d-flex justify-content-between ps-3" style="font-size:11px;">
                            <span class="text-muted"><i class="bi bi-arrow-return-right me-1" style="font-size:9px;"></i>{{ $cr->course_name }}</span>
                            <span class="fw-semibold text-dark">{{ $cr->cnt }}</span>
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
    @php $staffTotal = $staffWise->sum('cnt'); @endphp
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px; overflow:hidden;">
            <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between" style="background:#1e3a5f; color:#fff;">
                <span class="fw-semibold" style="font-size:13px;"><i class="bi bi-person-badge me-1"></i> Admitted By</span>
                <span class="badge bg-white text-primary fw-bold" style="font-size:11px;">{{ $staffTotal }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th class="ps-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Staff / Admin</th>
                            <th class="text-end pe-3 py-2 text-muted" style="font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffWise as $sw)
                        @php
                            $pct = $staffTotal > 0 ? round($sw->cnt/$staffTotal*100) : 0;
                            [$swIcon, $swColor] = match(true) {
                                str_starts_with($sw->staff_name, 'Center:')  => ['bi-building',      'text-info'],
                                str_starts_with($sw->staff_name, 'Partner:') => ['bi-handshake',     'text-warning'],
                                $sw->staff_name === 'Admin / Direct'         => ['bi-shield-check',  'text-secondary'],
                                default                                       => ['bi-person-circle', 'text-primary'],
                            };
                        @endphp
                        <tr>
                            <td class="ps-3 py-2">
                                <div class="fw-semibold" style="font-size:12px;">
                                    <i class="bi {{ $swIcon }} me-1 {{ $swColor }}" style="font-size:10px;"></i>
                                    {{ $sw->staff_name }}
                                </div>
                                <div class="progress mt-1" style="height:3px; border-radius:2px; width:90%;">
                                    <div class="progress-bar bg-primary bg-opacity-50" style="width:{{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-end pe-3 fw-bold" style="font-size:13px; vertical-align:middle;">{{ $sw->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#1e3a5f; color:#fff;">
                        <tr>
                            <td class="ps-3 py-2 fw-semibold">Total</td>
                            <td class="text-end pe-3 py-2 fw-bold">{{ $staffTotal }}</td>
                        </tr>
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
            <div class="card-header py-2 bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small"><i class="bi bi-people-fill me-1 text-warning"></i> Category-wise</span>
                <span class="text-muted" style="font-size:10px;">Reservation categories</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:12px;">
                    <thead class="table-light">
                        <tr><th class="ps-3">Category</th><th class="text-end pe-3">Count</th></tr>
                    </thead>
                    <tbody>
                        @foreach($categoryWise as $c)
                        @php
                            $catKey = strtolower($c->category ?? '');
                            [$catLabel, $catBadge] = match($catKey) {
                                'gen'   => ['GEN — General',          'bg-primary bg-opacity-10 text-primary'],
                                'obc'   => ['OBC — Other Backward',   'bg-success bg-opacity-10 text-success'],
                                'sc'    => ['SC — Scheduled Caste',   'bg-info bg-opacity-10 text-info'],
                                'st'    => ['ST — Scheduled Tribe',   'bg-warning bg-opacity-10 text-warning'],
                                'ews'   => ['EWS — Eco. Weaker Sec.', 'bg-purple bg-opacity-10 text-purple'],
                                ''      => ['Not Set',                 'bg-secondary bg-opacity-10 text-secondary'],
                                default => [strtoupper($c->category),  'bg-secondary bg-opacity-10 text-secondary'],
                            };
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <span class="badge {{ $catBadge }} fw-normal" style="font-size:10.5px;">
                                    {{ $catKey ? strtoupper($catKey) : '—' }}
                                </span>
                                <span class="text-muted ms-1" style="font-size:10px;">{{ Str::after($catLabel, ' — ') }}</span>
                            </td>
                            <td class="text-end pe-3 fw-semibold">{{ $c->cnt }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark fw-semibold" style="font-size:12px;">
                        <tr><td class="ps-3">Total</td><td class="text-end pe-3">{{ $categoryWise->sum('cnt') }}</td></tr>
                    </tfoot>
                </table>
                @php $notSetCount = $categoryWise->whereIn('category', [null, ''])->sum('cnt'); @endphp
                @if($notSetCount > 0)
                <div class="px-3 py-2 border-top bg-warning bg-opacity-10" style="font-size:10.5px;">
                    <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                    <span class="text-warning fw-semibold">{{ $notSetCount }} student{{ $notSetCount > 1 ? 's' : '' }}</span>
                    <span class="text-muted"> — category not filled in admission form</span>
                </div>
                @endif
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
                        $srcShort = match($rSrc) {
                            'center'                     => 'Center',
                            'partner', 'channel_partner' => 'Partner',
                            default                      => 'Direct',
                        };
                        $srcName = ($rSrc === 'center' && ($centersMap[$s->admission_source_id] ?? null))
                            ? 'Center: ' . $centersMap[$s->admission_source_id]
                            : ((in_array($rSrc, ['partner','channel_partner']) && ($partnersMap[$s->admission_source_id] ?? null))
                                ? 'Partner: ' . $partnersMap[$s->admission_source_id]
                                : $srcShort);
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
                        <td class="fw-semibold" style="white-space:nowrap;">{{ $s->roll_no ?: '—' }}</td>
                        <td class="fw-semibold" style="white-space:nowrap;">{{ $s->enrollment_no ?: '—' }}</td>
                        <td class="fw-semibold" style="white-space:nowrap;">{{ $s->uin_no ?: '—' }}</td>
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
                        <td style="white-space:nowrap;">
                            <span class="badge {{ $admittedByBadge }} fw-semibold" style="font-size:10px; white-space:normal; max-width:150px; word-break:break-word; display:inline-block; text-align:left;">
                                {{ $admittedByLabel }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $srcBadge }} fw-semibold" style="font-size:10px; white-space:nowrap;">
                                {{ $srcShort }}
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
        const chev  = getEl('centerChevron');
        if (!panel) return;
        const open = panel.style.display === 'none';
        panel.style.display = open ? '' : 'none';
        if (chev) { chev.className = open ? 'bi bi-chevron-up ms-1 text-info' : 'bi bi-chevron-down ms-1 text-info'; }
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
