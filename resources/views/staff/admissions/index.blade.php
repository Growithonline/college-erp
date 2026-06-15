@extends('staff.layout')
@section('title','My Students')
@section('breadcrumb','My Students')
@section('content')

@php
    $canCollectFee     = $authUser->canCollectFee();
    $canViewFeeHistory = $authUser->canViewFeeHistory();
    $canViewFeeWallet  = $authUser->canViewFeeWallet();
    $canEditStudent    = $authUser->hasPermission('student_edit') || $authUser->hasPermission('admission_edit');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">My Students</h4>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $activeSession?->name ?? 'No Active Session' }}</span>
            — {{ number_format($students->total()) }} student(s)
        </small>
    </div>
    @if($authUser->hasPermission('admission_add'))
    <div class="d-flex gap-2">
        <a href="{{ route('staff.admissions.quick-create') }}" class="btn btn-warning btn-sm fw-semibold">
            <i class="bi bi-lightning-fill me-1"></i>Quick Register
        </a>
        <a href="{{ route('staff.admissions.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i>Full Form
        </a>
    </div>
    @endif
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Name, Mobile, Student ID..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ request('session_id', $activeSession?->id) == $sess->id ? 'selected' : '' }}>
                            {{ $sess->name }}{{ $sess->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="course_stream_id" class="form-select form-select-sm">
                    <option value="">All Courses / Streams</option>
                    @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" {{ request('course_stream_id') == $stream->id ? 'selected' : '' }}>
                            {{ $stream->course->name ?? '' }} — {{ $stream->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm px-3">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('staff.admissions.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>

            {{-- Column Toggle --}}
            <div class="col-auto ms-auto">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                        <i class="bi bi-layout-three-columns me-1"></i>Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width:210px;"
                        onclick="event.stopPropagation()">
                        @foreach([
                            'col_name'       => 'Student Name',
                            'col_uid'        => 'Student ID',
                            'col_father'     => 'Father / Mother Name',
                            'col_course'     => 'Course / Stream',
                            'col_year'       => 'Year / Part',
                            'col_rollno'     => 'Roll No.',
                            'col_enrollment' => 'Enrollment No. (UIN)',
                            'col_mobile'     => 'Mobile',
                            'col_admdate'    => 'Admission Date',
                            'col_session'    => 'Session',
                            'col_source'     => 'Source',
                            'col_formstatus' => 'Form Status',
                            'col_status'     => 'Status',
                        ] as $col => $label)
                        <li>
                            <label class="dropdown-item d-flex align-items-center gap-2 small py-1" style="cursor:pointer;">
                                <input type="checkbox" class="col-toggle" data-col="{{ $col }}"
                                       onchange="toggleCol('{{ $col }}', this.checked)"
                                       {{ in_array($col, ['col_name','col_uid','col_father','col_course','col_rollno','col_enrollment','col_mobile','col_admdate','col_status']) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th class="col_name">Student</th>
                    <th class="col_uid">Student ID</th>
                    <th class="col_father">Father / Mother</th>
                    <th class="col_course">Course / Stream</th>
                    <th class="col_year" style="display:none;">Year / Part</th>
                    <th class="col_rollno">Roll No.</th>
                    <th class="col_enrollment">Enrollment No.</th>
                    <th class="col_mobile">Mobile</th>
                    <th class="col_admdate">Adm. Date</th>
                    <th class="col_session" style="display:none;">Session</th>
                    <th class="col_source" style="display:none;">Source</th>
                    <th class="col_formstatus" style="display:none;">Form Status</th>
                    <th class="col_status">Status</th>
                    <th class="text-center pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                @php
                    $isComplete = !empty($student->name)
                        && !empty($student->mobile)
                        && !empty($student->father_name)
                        && !empty($student->dob)
                        && !empty($student->gender)
                        && !empty($student->category)
                        && !empty($student->course_stream_id)
                        && !empty($student->admission_date);

                    $src = $student->admission_source ?? 'direct';
                    $srcColors = ['direct' => 'success', 'center' => 'info', 'channel_partner' => 'warning'];
                    $srcColor  = $srcColors[$src] ?? 'secondary';
                @endphp
                <tr class="{{ !$isComplete ? 'table-warning bg-opacity-25' : '' }}">
                    <td class="ps-3 text-muted small">{{ $students->firstItem() + $i }}</td>
                    <td class="col_name">
                        <div class="fw-semibold">{{ $student->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $student->mobile }}</div>
                    </td>
                    <td class="col_uid">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                            {{ $student->student_uid }}
                        </span>
                    </td>
                    <td class="col_father small">
                        <div>{{ $student->father_name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $student->mother_name ?? '' }}</div>
                    </td>
                    <td class="col_course small">
                        <div class="fw-semibold">{{ $student->stream->course->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $student->stream->name ?? '—' }}</div>
                    </td>
                    <td class="col_year small text-muted" style="display:none;">
                        {{ $student->coursePart?->year_label ?? '—' }}
                        @if($student->current_semester)
                            <span class="badge bg-primary bg-opacity-10 text-primary border ms-1" style="font-size:10px;">
                                S{{ $student->current_semester }}
                            </span>
                        @endif
                    </td>
                    <td class="col_rollno small text-muted">
                        {{ $student->roll_no ?? '—' }}
                    </td>
                    <td class="col_enrollment small text-muted">
                        {{ $student->enrollment_no ?? '—' }}
                    </td>
                    <td class="col_mobile small">{{ $student->mobile }}</td>
                    <td class="col_admdate small text-muted">
                        {{ $student->admission_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="col_session small text-muted" style="display:none;">
                        {{ $student->session->name ?? '—' }}
                    </td>
                    <td class="col_source" style="display:none;">
                        <span class="badge bg-{{ $srcColor }} bg-opacity-10 text-{{ $srcColor }} border">
                            {{ ucwords(str_replace('_', ' ', $src)) }}
                        </span>
                    </td>
                    <td class="col_formstatus" style="display:none;">
                        @if($isComplete)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                                <i class="bi bi-check-circle me-1"></i>Complete
                            </span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle">
                                <i class="bi bi-exclamation-circle me-1"></i>Incomplete
                            </span>
                        @endif
                    </td>
                    <td class="col_status">
                        @if(($student->status ?? 'active') === 'active')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Active</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ ucfirst($student->status) }}</span>
                        @endif
                    </td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('staff.admissions.show', $student->id) }}"
                               class="btn btn-outline-primary btn-sm" title="View Profile">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($canEditStudent)
                            <a href="{{ route('staff.admissions.edit', $student->id) }}"
                               class="btn btn-outline-warning btn-sm"
                               title="{{ $isComplete ? 'Edit' : 'Fill Incomplete Form' }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if($canViewFeeHistory)
                            <a href="{{ route('staff.fee.student-history', $student->id) }}"
                               class="btn btn-outline-secondary btn-sm" title="Fee History">
                                <i class="bi bi-clock-history"></i>
                            </a>
                            @endif
                            @if($canViewFeeWallet)
                            <a href="{{ route('staff.fee.wallet.student', $student->id) }}"
                               class="btn btn-outline-info btn-sm" title="Wallet">
                                <i class="bi bi-wallet2"></i>
                            </a>
                            @endif
                            @if($canCollectFee)
                            <a href="{{ route('staff.fee.create', ['student_id' => $student->id]) }}"
                               class="btn btn-outline-success btn-sm" title="Collect Fee">
                                <i class="bi bi-cash"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center py-5 text-muted">
                        <i class="bi bi-person-plus fs-2 d-block mb-2 opacity-25"></i>
                        No students found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="card-footer bg-white border-top py-2 px-3">
        @if($students->total() > 0)
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted">
                Showing
                <span class="fw-semibold">{{ $students->firstItem() }}</span>–<span class="fw-semibold">{{ $students->lastItem() }}</span>
                of <span class="fw-semibold">{{ number_format($students->total()) }}</span> students
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2 small">
                    <span class="text-muted">Show</span>
                    <select class="form-select form-select-sm py-0" style="width:70px;"
                            onchange="window.location.href=updateQueryParam('per_page', this.value)">
                        @foreach([10, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    <span class="text-muted">per page</span>
                </div>
                @if($students->hasPages())
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $students->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $students->previousPageUrl() }}&per_page={{ $perPage }}">
                                <i class="bi bi-chevron-left" style="font-size:10px;"></i>
                            </a>
                        </li>
                        @foreach($students->getUrlRange(max(1, $students->currentPage()-2), min($students->lastPage(), $students->currentPage()+2)) as $page => $url)
                        <li class="page-item {{ $page == $students->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}&per_page={{ $perPage }}">{{ $page }}</a>
                        </li>
                        @endforeach
                        <li class="page-item {{ !$students->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $students->nextPageUrl() }}&per_page={{ $perPage }}">
                                <i class="bi bi-chevron-right" style="font-size:10px;"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                @endif
            </div>
        </div>
        @else
        <div class="small text-muted">No records found</div>
        @endif
    </div>
</div>

<script>
function updateQueryParam(key, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    url.searchParams.set('page', '1');
    return url.toString();
}

// Column toggle — restore from localStorage on load
document.addEventListener('DOMContentLoaded', function () {
    const saved = JSON.parse(localStorage.getItem('staffAdmColPrefs') || '{}');
    document.querySelectorAll('.col-toggle').forEach(cb => {
        const col = cb.dataset.col;
        if (saved[col] !== undefined) {
            cb.checked = saved[col];
            toggleCol(col, saved[col], false);
        } else {
            toggleCol(col, cb.checked, false);
        }
    });
});

function toggleCol(col, visible, save = true) {
    document.querySelectorAll('.' + col).forEach(el => {
        el.style.display = visible ? '' : 'none';
    });
    if (save) {
        const saved = JSON.parse(localStorage.getItem('staffAdmColPrefs') || '{}');
        saved[col] = visible;
        localStorage.setItem('staffAdmColPrefs', JSON.stringify(saved));
    }
}
</script>
@endsection
