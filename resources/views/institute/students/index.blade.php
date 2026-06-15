@extends('institute.layout')
@php $quickOnly = $quickOnly ?? false; @endphp
@section('title', $quickOnly ? 'Quick Admissions' : 'All Students')
@section('breadcrumb', $quickOnly ? 'Students / Quick Admissions' : 'Students / All Students')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            @if($quickOnly)
                <i class="bi bi-lightning-fill text-warning me-1"></i> Quick Admissions
            @else
                All Students
            @endif
        </h4>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $sessions->firstWhere('id', $sessionId)?->name ?? 'All Sessions' }}</span>
            — {{ $students->total() }} student(s)
        </small>
    </div>
    <div class="d-flex gap-2">
        @if($quickOnly)
            <a href="{{ route('admissions.quick-create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Quick Admission
            </a>
        @endif
        <a href="{{ route('students.search') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i> Search
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" id="filterForm">
            <div class="row g-2 align-items-end">

                {{-- Search --}}
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Name, Father, Mother, Mobile, ID..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Session --}}
                <div class="col-md-1" style="min-width:110px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($sessions as $sess)
                            <option value="{{ $sess->id }}" {{ request('session_id') == $sess->id ? 'selected' : '' }}>
                                {{ $sess->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Course Type --}}
                @if($courseTypes->isNotEmpty())
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course Type</label>
                    <select name="course_type_id" class="form-select form-select-sm" id="filterCourseType">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>
                                {{ $ct->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Course --}}
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course</label>
                    <select name="course_id" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-md-1" style="min-width:105px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="detained" {{ request('status') === 'detained' ? 'selected' : '' }}>Detained</option>
                        <option value="cancelled"{{ request('status') === 'cancelled'? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                {{-- Date Range --}}
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm"
                           value="{{ request('from_date') }}" style="width:130px;">
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm"
                           value="{{ request('to_date') }}" style="width:130px;">
                </div>

                {{-- Buttons --}}
                <div class="col-auto d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ $quickOnly ? route('students.quick') : route('students.index') }}"
                       class="btn btn-outline-secondary btn-sm">
                        Clear
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:12.5px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:40px;">#</th>
                    <th style="min-width:170px;">Student</th>
                    <th style="min-width:120px;">Father Name</th>
                    <th style="min-width:120px;">Mother Name</th>
                    <th style="min-width:110px;">Student ID</th>
                    <th style="min-width:140px;">Course / Stream</th>
                    <th style="min-width:90px;">Year / Sem</th>
                    <th style="min-width:75px;">Session</th>
                    <th style="min-width:110px;">Guide</th>
                    <th style="min-width:100px;">Admitted By</th>
                    <th style="min-width:80px;">Status</th>
                    <th style="min-width:90px;">Adm. Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                @php
                    // Guide = admission source
                    $guide = match($student->admission_source) {
                        'center'  => ($centersMap[$student->admission_source_id] ?? null)
                                        ? 'Center: ' . $centersMap[$student->admission_source_id]
                                        : 'Center',
                        'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                        ? 'Partner: ' . $partnersMap[$student->admission_source_id]
                                        : 'Partner',
                        default   => 'Direct',
                    };
                    $statusColor = match($student->status) {
                        'active'    => 'bg-success-subtle text-success border-success-subtle',
                        'pending'   => 'bg-warning-subtle text-warning border-warning-subtle',
                        'inactive'  => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                        'detained'  => 'bg-danger-subtle text-danger border-danger-subtle',
                        'cancelled' => 'bg-dark-subtle text-dark border-dark-subtle',
                        default     => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                    };
                @endphp
                <tr>
                    <td class="ps-3 text-muted small">{{ $students->firstItem() + $i }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center
                                        justify-content-center flex-shrink-0"
                                 style="width:32px;height:32px;font-size:12px;font-weight:600;color:#0d6efd;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size:12.5px;">{{ $student->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $student->mobile }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $student->father_name ?: '—' }}</td>
                    <td class="small">{{ $student->mother_name ?: '—' }}</td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"
                              style="font-size:10.5px;">
                            {{ $student->student_uid }}
                        </span>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $student->stream->course->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $student->stream->name ?? '—' }}</div>
                    </td>
                    <td class="small text-muted">
                        {{ $student->coursePart?->year_label ?? '—' }}
                        @if($student->current_semester)
                            <span class="badge bg-primary bg-opacity-10 text-primary border ms-1"
                                  style="font-size:10px;">S{{ $student->current_semester }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border"
                              style="font-size:10.5px;">
                            {{ $student->session->name ?? '—' }}
                        </span>
                    </td>
                    <td class="small">
                        <span class="{{ $student->admission_source === 'center' ? 'text-info' : ($student->admission_source === 'partner' || $student->admission_source === 'channel_partner' ? 'text-purple' : 'text-muted') }}">
                            {{ $guide }}
                        </span>
                    </td>
                    <td class="small">
                        @if($student->admittedBy)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle"
                                  style="font-size:10.5px;">
                                <i class="bi bi-person-badge me-1"></i>{{ $student->admittedBy->name }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge border {{ $statusColor }}" style="font-size:10.5px;">
                            {{ ucfirst($student->status ?? 'pending') }}
                        </span>
                    </td>
                    <td class="small text-muted" style="white-space:nowrap;">
                        {{ $student->admission_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admissions.show', $student->id) }}"
                               class="btn btn-sm btn-outline-primary" title="Profile" style="padding:3px 7px;">
                                <i class="bi bi-person"></i>
                            </a>
                            <a href="{{ route('fee.wallet.student', $student->id) }}"
                               class="btn btn-sm btn-outline-info" title="Wallet" style="padding:3px 7px;">
                                <i class="bi bi-wallet2"></i>
                            </a>
                            <a href="{{ route('fee.student-history', $student->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="Fee History" style="padding:3px 7px;">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="{{ route('fee.create', ['student_id' => $student->id]) }}"
                               class="btn btn-sm btn-outline-success" title="Collect Fee" style="padding:3px 7px;">
                                <i class="bi bi-plus-circle"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                        No students found matching your filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $students->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection
