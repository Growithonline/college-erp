@extends('center.layout')
@section('title','My Students')
@section('breadcrumb','Students')
@section('content')

@php
    $shownSession = $allowedSessions->firstWhere('id', $sessionId);
    $exportBase   = array_filter(request()->only(['session_id','search','course_id','status','from_date','to_date']));
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">My Students</h4>
        <small class="text-muted">
            Session: <span class="fw-semibold text-primary">{{ $shownSession?->name ?? $activeSession?->name ?? 'All' }}</span>
            — {{ $students->total() }} student(s)
        </small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        {{-- Export --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-download me-1"></i> Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('center.students.export', array_merge($exportBase, ['format'=>'csv'])) }}">
                        <i class="bi bi-filetype-csv me-2 text-success"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('center.students.export', array_merge($exportBase, ['format'=>'excel'])) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> Excel (.xlsx)
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('center.students.export', array_merge($exportBase, ['format'=>'pdf'])) }}" target="_blank">
                        <i class="bi bi-filetype-pdf me-2 text-danger"></i> PDF
                    </a>
                </li>
            </ul>
        </div>

        @if($authUser->canManageAdmissions())
        <a href="{{ route('center.admissions.quick-create') }}" class="btn btn-warning btn-sm fw-semibold">
            <i class="bi bi-lightning me-1"></i> Quick Register
        </a>
        <a href="{{ route('center.admissions.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Full Form
        </a>
        @endif
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
                @if($allowedSessions->count() > 1)
                <div class="col-md-1" style="min-width:120px;">
                    <label class="form-label form-label-sm mb-1 text-muted" style="font-size:11px;">Session</label>
                    <select name="session_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($allowedSessions as $sess)
                        <option value="{{ $sess->id }}" {{ (int)request('session_id') === $sess->id ? 'selected' : '' }}>
                            {{ $sess->name }}{{ $sess->is_active ? ' ★' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                    @if($sessionId)
                        <input type="hidden" name="session_id" value="{{ $sessionId }}">
                    @endif
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
                <div class="col-md-1" style="min-width:110px;">
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
                    <a href="{{ route('center.students.index', $sessionId ? ['session_id' => $sessionId] : []) }}"
                       class="btn btn-outline-secondary btn-sm">Clear</a>
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
                    <th class="ps-3" style="width:36px;">#</th>
                    <th style="min-width:160px;">Student</th>
                    <th style="min-width:115px;">Father Name</th>
                    <th style="min-width:115px;">Mother Name</th>
                    <th style="min-width:110px;">Student ID</th>
                    <th style="min-width:140px;">Course / Stream</th>
                    <th style="min-width:90px;">Year / Sem</th>
                    <th style="min-width:75px;">Session</th>
                    <th style="min-width:100px;">Admitted By</th>
                    <th style="min-width:80px;">Status</th>
                    <th style="min-width:90px;">Adm. Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                @php
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
                                        justify-content-center flex-shrink-0 fw-semibold"
                                 style="width:32px;height:32px;font-size:12px;color:#0d6efd;">
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
                            {{ $student->session?->name ?? '—' }}
                        </span>
                    </td>
                    <td class="small">
                        @if($student->admittedBy?->name)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle"
                                  style="font-size:10.5px;">
                                <i class="bi bi-person-badge me-1"></i>{{ $student->admittedBy->name }}
                            </span>
                        @elseif($student->admission_source === 'center')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"
                                  style="font-size:10.5px;">
                                <i class="bi bi-building me-1"></i>{{ \App\Models\Center::find($student->admission_source_id)?->name ?? 'Center' }}
                            </span>
                        @elseif($student->admission_source === 'channel_partner')
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle"
                                  style="font-size:10.5px;">
                                <i class="bi bi-person-workspace me-1"></i>{{ \App\Models\ChannelPartner::find($student->admission_source_id)?->name ?? 'Partner' }}
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
                        <a href="{{ route('center.students.show', $student) }}"
                           class="btn btn-sm btn-outline-primary" title="Profile" style="padding:3px 8px;">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if($authUser->canCollectFee())
                        <a href="{{ route('center.fee.create', ['student_id' => $student->id]) }}"
                           class="btn btn-sm btn-outline-success ms-1" title="Collect Fee" style="padding:3px 8px;">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-5">
                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                        Koi student nahi mila
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $students->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection
