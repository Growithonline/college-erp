@extends('institute.layout')
@php $quickOnly = $quickOnly ?? false; @endphp
@section('title', $quickOnly ? 'Quick Admissions' : 'All Students')
@section('breadcrumb', $quickOnly ? 'Students / Quick Admissions' : 'Students / All Students')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h5 class="mb-0 fw-bold">
            @if($quickOnly)
                <i class="bi bi-lightning-fill text-warning me-1"></i> Quick Admissions
            @else
                All Students
            @endif
        </h5>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $sessions->firstWhere('id', $sessionId)?->name ?? 'All Sessions' }}</span>
            &mdash; {{ $students->total() }} student(s)
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        @if($quickOnly)
            <a href="{{ route('admissions.quick-create') }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i> New Quick Admission
            </a>
        @endif

        {{-- Export Dropdown --}}
        <div class="dropdown">
            <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item"
                       href="{{ route('students.export', array_merge(request()->query(), ['export' => 'pdf', 'quick_only' => $quickOnly ? '1' : '0'])) }}"
                       target="_blank">
                        <i class="bi bi-file-pdf me-2 text-danger"></i> PDF (Print)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item"
                       href="{{ route('students.export', array_merge(request()->query(), ['export' => 'excel', 'quick_only' => $quickOnly ? '1' : '0'])) }}">
                        <i class="bi bi-file-excel me-2 text-success"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item"
                       href="{{ route('students.export', array_merge(request()->query(), ['export' => 'csv', 'quick_only' => $quickOnly ? '1' : '0'])) }}">
                        <i class="bi bi-filetype-csv me-2 text-secondary"></i> CSV
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ route('students.search') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-search me-1"></i> Search
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-2">
    <div class="card-body py-2 px-3">
        <form method="GET" id="filterForm">
            <div class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                               placeholder="Name, Father, Mother, Mobile, ID..."
                               value="{{ request('search') }}">
                    </div>
                </div>

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

                @if($courseTypes->isNotEmpty())
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Course Type</label>
                    <select name="course_type_id" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($courseTypes as $ct)
                            <option value="{{ $ct->id }}" {{ request('course_type_id') == $ct->id ? 'selected' : '' }}>
                                {{ $ct->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

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

                <div class="col-md-1" style="min-width:105px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
                        <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                        <option value="inactive"  {{ request('status') === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                        <option value="detained"  {{ request('status') === 'detained'  ? 'selected' : '' }}>Detained</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

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
        <table class="table table-hover table-sm align-middle mb-0" style="font-size:12px;">
            <thead style="background:#1e3a5f; color:#fff;">
                <tr>
                    <th class="ps-2" style="width:30px; white-space:nowrap;">#</th>
                    <th style="min-width:60px; white-space:nowrap;">Session</th>
                    <th style="min-width:110px; white-space:nowrap;">Student ID</th>
                    <th style="min-width:145px; white-space:nowrap;">Student Name</th>
                    <th style="min-width:100px; white-space:nowrap;">Father Name</th>
                    <th style="min-width:100px; white-space:nowrap;">Mother Name</th>
                    <th style="min-width:65px; white-space:nowrap;">Roll No</th>
                    <th style="min-width:75px; white-space:nowrap;">Enroll No</th>
                    <th style="min-width:65px; white-space:nowrap;">UIN No</th>
                    <th style="min-width:120px; white-space:nowrap;">Course</th>
                    <th style="min-width:65px; white-space:nowrap;">Year/Sem</th>
                    <th style="min-width:90px; white-space:nowrap;">Admitted By</th>
                    <th style="min-width:85px; white-space:nowrap;">Source</th>
                    <th style="min-width:80px; white-space:nowrap;">Adm. Date</th>
                    <th style="min-width:68px; white-space:nowrap;">Status</th>
                    <th style="min-width:108px; white-space:nowrap;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                @php
                    $source = match($student->admission_source) {
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
                    <td class="ps-2 text-muted">{{ $students->firstItem() + $i }}</td>

                    <td class="text-muted" style="white-space:nowrap;">
                        {{ $student->session?->name ?? '—' }}
                    </td>

                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"
                              style="font-size:10px; font-weight:600;">
                            {{ $student->student_uid }}
                        </span>
                    </td>

                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">{{ $student->name }}</div>
                        <div class="text-muted" style="font-size:10.5px;">{{ $student->mobile }}</div>
                    </td>

                    <td style="white-space:nowrap;">{{ $student->father_name ?: '—' }}</td>
                    <td style="white-space:nowrap;">{{ $student->mother_name ?: '—' }}</td>
                    <td class="text-muted">{{ $student->roll_no ?: '—' }}</td>
                    <td class="text-muted">{{ $student->enrollment_no ?: '—' }}</td>
                    <td class="text-muted">{{ $student->uin_no ?: '—' }}</td>

                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">{{ $student->stream?->course?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:10.5px;">{{ $student->stream?->name ?? '—' }}</div>
                    </td>

                    <td style="white-space:nowrap;">
                        {{ $student->coursePart?->year_label ?? '—' }}
                        @if($student->current_semester)
                            <span class="badge bg-primary bg-opacity-10 text-primary border ms-1"
                                  style="font-size:9px;">S{{ $student->current_semester }}</span>
                        @endif
                    </td>

                    <td>
                        @if($student->admittedBy)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle"
                                  style="font-size:10px;">
                                <i class="bi bi-person-badge me-1"></i>{{ $student->admittedBy->name }}
                            </span>
                        @else
                            <span class="text-muted" style="font-size:11px;">Admin</span>
                        @endif
                    </td>

                    <td class="text-muted">{{ $source }}</td>

                    <td class="text-muted" style="white-space:nowrap;">
                        {{ $student->admission_date?->format('d M Y') ?? '—' }}
                    </td>

                    <td>
                        <span class="badge border {{ $statusColor }}" style="font-size:10px;">
                            {{ ucfirst($student->status ?? 'pending') }}
                        </span>
                    </td>

                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admissions.show', $student->id) }}"
                               class="btn btn-sm btn-outline-primary" title="Profile"
                               style="padding:2px 6px; font-size:11px;">
                                <i class="bi bi-person"></i>
                            </a>
                            <a href="{{ route('fee.wallet.student', $student->id) }}"
                               class="btn btn-sm btn-outline-info" title="Wallet"
                               style="padding:2px 6px; font-size:11px;">
                                <i class="bi bi-wallet2"></i>
                            </a>
                            <a href="{{ route('fee.student-history', $student->id) }}"
                               class="btn btn-sm btn-outline-secondary" title="Fee History"
                               style="padding:2px 6px; font-size:11px;">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="{{ route('fee.create', ['student_id' => $student->id]) }}"
                               class="btn btn-sm btn-outline-success" title="Collect Fee"
                               style="padding:2px 6px; font-size:11px;">
                                <i class="bi bi-plus-circle"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="16" class="text-center text-muted py-5">
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
