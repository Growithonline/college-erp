@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $indexRoute = $isStaff ? 'staff.admissions.approvals.index' : 'admissions.approvals.index';
    $showRoute = $isStaff ? 'staff.admissions.approvals.show' : 'admissions.approvals.show';
@endphp
@extends($layout)
@section('title', 'Admission Approvals')
@section('breadcrumb', 'Admissions / Approval Queue')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Admission Approval Queue</h5>
        <span class="text-muted" style="font-size:0.8rem;">Review and activate pending admissions.</span>
    </div>
    <a href="{{ route($isStaff ? 'staff.admissions.index' : 'admissions.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Admissions
    </a>
</div>

{{-- Stats --}}
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-left:3px solid #2563eb!important;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <div>
                    <div class="text-muted" style="font-size:0.75rem;">Total Admissions</div>
                    <div class="fw-bold fs-5 mb-0">{{ number_format($totalAdmissions) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-left:3px solid #f59e0b!important;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <div>
                    <div class="text-muted" style="font-size:0.75rem;">Pending Approval</div>
                    <div class="fw-bold fs-5 mb-0 text-warning">{{ number_format($pendingAdmissions) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm" style="border-left:3px solid #16a34a!important;">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
                <div>
                    <div class="text-muted" style="font-size:0.75rem;">Approved / Active</div>
                    <div class="fw-bold fs-5 mb-0 text-success">{{ number_format($approvedAdmissions) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
@php
    $filteredStreams = request()->filled('course_id')
        ? $streams->where('course_id', (int) request('course_id'))
        : $streams;
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 px-3">
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 align-items-end" id="filterForm">

            {{-- Row 1: Search | Session | Course Type | Course | Stream | Status --}}
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="form-control form-control-sm" placeholder="Name, UID, mobile, father name...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm auto-filter">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) request('session_id', $activeSession?->id) === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}{{ $session->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course Type</label>
                <select name="course_type_id" class="form-select form-select-sm auto-filter">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $type)
                        <option value="{{ $type->id }}" {{ request('course_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm" id="courseFilter">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Stream</label>
                <select name="stream_id" class="form-select form-select-sm auto-filter" id="streamFilter">
                    <option value="">All Streams</option>
                    @foreach($filteredStreams as $stream)
                        <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm auto-filter">
                    <option value="">All</option>
                    @foreach(['pending', 'waitlisted', 'active', 'inactive', 'detained', 'cancelled', 'passed_out', 'transferred'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Row 2: Source | Source Sub | Date From | Date To | Rows --}}
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Source</label>
                <select name="source" class="form-select form-select-sm auto-filter" id="sourceFilter">
                    <option value="">All Sources</option>
                    <option value="direct" {{ request('source') === 'direct' ? 'selected' : '' }}>Direct</option>
                    <option value="center" {{ request('source') === 'center' ? 'selected' : '' }}>Center</option>
                    <option value="channel_partner" {{ request('source') === 'channel_partner' ? 'selected' : '' }}>Channel Partner</option>
                    <option value="online" {{ request('source') === 'online' ? 'selected' : '' }}>Online</option>
                </select>
            </div>
            <div class="col-md-2" id="sourceSubWrap" style="{{ in_array(request('source'), ['direct','center','channel_partner']) ? '' : 'display:none' }}">
                <label class="form-label small fw-semibold mb-1">
                    @if(request('source') === 'direct') Admitted By
                    @elseif(request('source') === 'center') Center
                    @elseif(request('source') === 'channel_partner') Channel Partner
                    @else Sub-Source
                    @endif
                </label>
                <select name="source_sub" class="form-select form-select-sm auto-filter" id="sourceSubFilter">
                    <option value="">All</option>
                    @if(request('source') === 'direct')
                        <option value="admin" {{ request('source_sub') === 'admin' ? 'selected' : '' }}>Admin / Direct</option>
                        @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ (string)request('source_sub') === (string)$staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
                    @elseif(request('source') === 'center')
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}" {{ (string)request('source_sub') === (string)$center->id ? 'selected' : '' }}>{{ $center->name }}</option>
                        @endforeach
                    @elseif(request('source') === 'channel_partner')
                        @foreach($channelPartners as $partner)
                            <option value="{{ $partner->id }}" {{ (string)request('source_sub') === (string)$partner->id ? 'selected' : '' }}>{{ $partner->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="form-control form-control-sm auto-filter-date">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="form-control form-control-sm auto-filter-date">
            </div>
            <div class="col-md-1">
                <label class="form-label small fw-semibold mb-1">Rows</label>
                <select name="per_page" class="form-select form-select-sm auto-filter">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Action Buttons --}}
            <div class="col-12 d-flex gap-2 align-items-center flex-wrap border-top pt-2 mt-1">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route($indexRoute, array_merge(request()->query(), ['export' => 'csv'])) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="bi bi-filetype-csv me-1"></i>Export CSV
                    </a>
                    <a href="{{ route($indexRoute, array_merge(request()->query(), ['export' => 'pdf'])) }}"
                        target="_blank" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                    </a>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.82rem;">
            <thead style="background:#f1f5f9;">
                <tr class="text-muted" style="font-size:0.75rem;letter-spacing:0.03em;">
                    <th class="ps-3 fw-semibold">#</th>
                    <th class="fw-semibold">Student</th>
                    <th class="fw-semibold">Student ID</th>
                    <th class="fw-semibold">Father</th>
                    <th class="fw-semibold">Mother</th>
                    <th class="fw-semibold">Course</th>
                    <th class="fw-semibold">Admission Date</th>
                    <th class="fw-semibold">Admitted By</th>
                    <th class="fw-semibold">Source</th>
                    <th class="fw-semibold">Status</th>
                    <th class="fw-semibold">Approved By</th>
                    <th class="text-end pe-3 fw-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                    @php
                        $source  = $student->admission_source ?? 'direct';
                        $srcName = null;

                        // Source column — what was selected in the admission form
                        // Uses pre-loaded $centers / $channelPartners collections (no extra DB query)
                        if ($source === 'center') {
                            $srcName     = $centers->firstWhere('id', $student->admission_source_id)?->name ?? 'Center';
                            $sourceLabel = 'Center: ' . $srcName;
                        } elseif ($source === 'channel_partner') {
                            $srcName     = $channelPartners->firstWhere('id', $student->admission_source_id)?->name ?? 'Partner';
                            $sourceLabel = 'Partner: ' . $srcName;
                        } elseif ($source === 'online') {
                            $sourceLabel = 'Online';
                        } else {
                            $sourceLabel = 'Direct';
                        }

                        // Admitted By — who actually performed the admission (from admitted_by_type)
                        $admittedByType = $student->admitted_by_type ?? 'admin';
                        if ($admittedByType === 'staff') {
                            $admittedBy = 'Staff: ' . ($student->admittedBy?->name ?? 'Staff');
                        } elseif ($admittedByType === 'center') {
                            $centerName = $srcName ?? ($centers->firstWhere('id', $student->admission_source_id)?->name ?? 'Center');
                            $admittedBy = 'Center: ' . $centerName;
                        } elseif ($admittedByType === 'channel_partner') {
                            $partnerName = $srcName ?? ($channelPartners->firstWhere('id', $student->admission_source_id)?->name ?? 'Partner');
                            $admittedBy = 'Partner: ' . $partnerName;
                        } else {
                            $admittedBy = 'Admin';
                        }
                        $statusClass = match($student->status) {
                            'pending'    => 'bg-warning text-dark',
                            'waitlisted' => 'bg-info text-dark',
                            'active'     => 'bg-success',
                            default      => 'bg-secondary',
                        };
                    @endphp
                    <tr class="{{ in_array($student->status, ['pending', 'waitlisted']) ? 'table-warning bg-opacity-10' : '' }}">
                        <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>
                        <td>
                            <div class="fw-semibold">{{ $student->name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $student->mobile ?: '-' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-normal" style="font-size:0.72rem;">
                                {{ $student->student_uid }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $student->father_name ?: '-' }}</td>
                        <td class="text-muted">{{ $student->mother_name ?: '-' }}</td>
                        <td>
                            <div class="fw-semibold">{{ $student->stream?->course?->name ?? '-' }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $student->stream?->name ?? '-' }}</div>
                        </td>
                        <td class="text-muted">{{ $student->admission_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-muted">{{ $admittedBy }}</td>
                        <td class="text-muted">{{ $sourceLabel }}</td>
                        <td>
                            <span class="badge {{ $statusClass }}" style="font-size:0.72rem;">
                                {{ ucwords(str_replace('_', ' ', $student->status ?? 'pending')) }}
                            </span>
                        </td>
                        <td class="text-muted">
                            @if($student->approved_at)
                                <div>{{ $student->approved_by_name ?? ($student->approvedByStaff?->name ?? '-') }}</div>
                                <div style="font-size:0.72rem;">{{ $student->approved_at->format('d M Y, h:i A') }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route($showRoute, $student) }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:0.78rem;">
                                <i class="bi bi-shield-check me-1"></i>Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-1 opacity-25"></i>
                            <span style="font-size:0.85rem;">No admissions found in the approval queue.</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-top py-2">
        @include('institute.components.pagination', ['paginator' => $students, 'perPage' => $perPage])
    </div>
</div>
@push('scripts')
<script>
(function () {
    const form = document.getElementById('filterForm');

    // Auto-submit all .auto-filter selects on change
    form.querySelectorAll('select.auto-filter').forEach(function (sel) {
        sel.addEventListener('change', function () { form.submit(); });
    });

    // Date inputs: auto-submit on change
    form.querySelectorAll('input.auto-filter-date').forEach(function (inp) {
        inp.addEventListener('change', function () { form.submit(); });
    });

    // Course change: clear stream selection, then auto-submit
    document.getElementById('courseFilter').addEventListener('change', function () {
        document.getElementById('streamFilter').value = '';
        form.submit();
    });

    // Source change: show/hide sub-filter wrap, then auto-submit
    document.getElementById('sourceFilter').addEventListener('change', function () {
        const src = this.value;
        const wrap = document.getElementById('sourceSubWrap');
        // Hide sub-wrap since page will reload with updated server-rendered options
        wrap.style.display = 'none';
        form.submit();
    });
})();
</script>
@endpush
@endsection
