@extends('institute.layout')
@section('title', 'Online Admissions')
@section('breadcrumb', 'Admissions / Online Admissions')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-globe text-info me-2"></i> Online Admissions
        </h4>
        <small class="text-muted">Students who registered via the online admission form</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="bi bi-people text-info fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Online</div>
                        <div class="fw-bold fs-5">{{ number_format($students->total()) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Name / Mobile / UID / Father" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $sess)
                        <option value="{{ $sess->id }}" {{ $sess->id == $sessionId ? 'selected' : '' }}>
                            {{ $sess->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Course Type</label>
                <select name="course_type_id" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    @foreach($courseTypes as $type)
                        <option value="{{ $type->id }}" {{ request('course_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }}
                            @if($course->duration)
                                ({{ $course->duration }} {{ $course->duration_type ?? 'yr' }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['pending', 'active', 'inactive', 'cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Rows</label>
                <select name="per_page" class="form-select form-select-sm">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admissions.online') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>UID</th>
                        <th>Name</th>
                        <th>Father Name</th>
                        <th>Mother Name</th>
                        <th>Mobile</th>
                        <th>Course / Stream</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Admission Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                    <tr>
                        <td class="ps-3 text-muted">{{ $students->firstItem() + $i }}</td>
                        <td><code class="small">{{ $student->student_uid ?? '-' }}</code></td>
                        <td class="fw-semibold">{{ $student->name }}</td>
                        <td class="small">{{ $student->father_name ?: '-' }}</td>
                        <td class="small">{{ $student->mother_name ?: '-' }}</td>
                        <td>{{ $student->mobile }}</td>
                        <td>
                            <div class="small">{{ $student->stream?->course?->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $student->stream?->name ?? '-' }}</div>
                        </td>
                        <td class="text-center">{{ $student->current_semester ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $student->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ ucfirst($student->status ?? 'active') }}
                            </span>
                        </td>
                        <td>{{ $student->admission_date?->format('d M Y') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admissions.show', $student) }}"
                               class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5 text-muted">
                            <i class="bi bi-globe fs-3 d-block mb-2 opacity-25"></i>
                            <div class="fw-semibold">No online admissions found</div>
                            <small>Students who registered via the online form will appear here</small>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($students->hasPages())
<div class="mt-3 d-flex justify-content-between align-items-center">
    <small class="text-muted">Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}</small>
    {{ $students->withQueryString()->links('vendor.pagination.bootstrap-5') }}
</div>
@endif

@endsection
