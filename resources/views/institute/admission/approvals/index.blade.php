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
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 align-items-end" id="filterForm">
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, UID, mobile, father name...">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) request('session_id', $activeSession?->id) === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}{{ $session->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(['pending', 'active', 'inactive', 'detained', 'cancelled', 'passed_out', 'transferred'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Course Type</label>
                <select name="course_type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $type)
                        <option value="{{ $type->id }}" {{ request('course_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-1">
                <label class="form-label small mb-1">Rows</label>
                <select name="per_page" class="form-select form-select-sm">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2 align-items-center flex-wrap">
                <button type="submit" class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                <div class="ms-auto d-flex gap-1">
                    <a href="{{ route($indexRoute, array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-filetype-csv me-1"></i>Export CSV
                    </a>
                    <a href="{{ route($indexRoute, array_merge(request()->query(), ['export' => 'pdf'])) }}" target="_blank" class="btn btn-outline-danger btn-sm">
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
                    <th class="fw-semibold">Status</th>
                    <th class="fw-semibold">Approved By</th>
                    <th class="text-end pe-3 fw-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                    @php
                        $source = $student->admission_source ?? 'direct';
                        $admittedBy = $student->admittedBy?->name ? 'Staff: '.$student->admittedBy->name : 'Admin / Direct';
                        if ($source === 'center') $admittedBy = 'Center';
                        elseif ($source === 'channel_partner') $admittedBy = 'Channel Partner';
                        $statusClass = match($student->status) {
                            'pending' => 'bg-warning text-dark',
                            'active'  => 'bg-success',
                            default   => 'bg-secondary',
                        };
                    @endphp
                    <tr class="{{ $student->status === 'pending' ? 'table-warning bg-opacity-10' : '' }}">
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
@endsection
