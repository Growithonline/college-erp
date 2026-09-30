@php
    $isStaff = auth()->guard('staff')->check();
    $layout = $isStaff ? 'staff.layout' : 'institute.layout';
    $showRoute = $isStaff ? 'staff.fee.previous-dues.show' : 'fee.previous-dues.show';
    $indexRoute = $isStaff ? 'staff.fee.previous-dues.index' : 'fee.previous-dues.index';
@endphp
@extends($layout)
@section('title', 'Collect Previous Dues')
@section('breadcrumb', 'Fee / Previous Dues')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> Collect Previous Dues</h4>
        <small class="text-muted">Non-active students (Passed Out, Detained, Transferred, Cancelled) with an outstanding fee balance.</small>
    </div>
    <a href="{{ route($isStaff ? 'staff.fee.create' : 'fee.create') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Collect Fee
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error:</strong> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($indexRoute) }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ (string) request('session_id') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                       placeholder="Name, mobile, Student UID, Enrollment No, Roll No, UIN">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </div>
            @if(request('search') || request('session_id'))
            <div class="col-md-2">
                <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small">
            <i class="bi bi-people me-1 text-primary"></i>
            Non-Active Students ({{ $students->total() }})
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:12px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Session</th>
                    <th>Student UID</th>
                    <th>Enrollment No</th>
                    <th>Roll No</th>
                    <th>UIN No</th>
                    <th>Name</th>
                    <th>Father Name</th>
                    <th>Mother Name</th>
                    <th>Mobile</th>
                    <th>DOB</th>
                    <th>Course</th>
                    <th class="text-center">Semester</th>
                    <th>Status</th>
                    <th class="text-end">Due Amount</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="text-muted">{{ $student->session?->name ?? '—' }}</td>
                    <td class="text-muted">{{ $student->student_uid }}</td>
                    <td class="text-muted">{{ $student->enrollment_no ?? '—' }}</td>
                    <td class="text-muted">{{ $student->roll_no ?? '—' }}</td>
                    <td class="text-muted">{{ $student->uin_no ?? '—' }}</td>
                    <td class="fw-semibold">{{ $student->name }}</td>
                    <td class="text-muted">{{ $student->father_name ?? '—' }}</td>
                    <td class="text-muted">{{ $student->mother_name ?? '—' }}</td>
                    <td class="text-muted">{{ $student->mobile }}</td>
                    <td class="text-muted">{{ $student->dob?->format('d M Y') ?? '—' }}</td>
                    <td class="text-muted">{{ $student->stream?->course?->name ?? '—' }} — {{ $student->stream?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">
                            S{{ $student->current_semester }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                            {{ ucfirst(str_replace('_', ' ', $student->status)) }}
                        </span>
                    </td>
                    <td class="text-end fw-semibold {{ $student->computed_due > 0 ? 'text-danger' : 'text-success' }}">
                        ₹{{ number_format($student->computed_due, 2) }}
                    </td>
                    <td class="text-end pe-3">
                        @if($student->computed_due > 0)
                            <a href="{{ route($showRoute, $student->id) }}" class="btn btn-sm btn-success">
                                <i class="bi bi-cash-coin me-1"></i> Collect Due
                            </a>
                        @else
                            <span class="badge bg-success bg-opacity-10 text-success">
                                <i class="bi bi-check-circle me-1"></i> No Due
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="15" class="text-center text-muted py-5">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                        No non-active students found for these filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2">
        {{ $students->links() }}
    </div>
    @endif
</div>

@endsection
