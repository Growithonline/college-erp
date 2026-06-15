@extends('partner.layout')
@section('title','My Students')
@section('breadcrumb','Students')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">My Students</h4>
        <small class="text-muted">Aapke through admitted students</small>
    </div>
    @if($authUser->canManageAdmissions())
    <a href="{{ route('partner.admissions.quick-create') }}" class="btn btn-warning btn-sm fw-semibold">
        <i class="bi bi-lightning me-1"></i> Quick Register
    </a>
    @endif
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-7">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Name, Mobile, Student ID...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Mobile</th>
                    <th>Admission Date</th>
                    <th class="text-center">Status</th>
                    <th class="text-center pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $i => $student)
                <tr>
                    <td class="ps-3 text-muted small">{{ $students->firstItem() + $i }}</td>
                    <td>
                        <div class="fw-semibold small">{{ $student->name }}</div>
                        <div class="text-muted" style="font-size:11px;">{{ $student->student_uid }}</div>
                    </td>
                    <td class="small">{{ $student->stream->course->name ?? '-' }}</td>
                    <td class="small">{{ $student->mobile }}</td>
                    <td class="small text-muted">{{ $student->admission_date?->format('d M Y') ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $student->status === 'pending' ? 'bg-warning text-dark' : (($student->status ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary') }}">
                            {{ ucfirst($student->status ?? 'active') }}
                        </span>
                    </td>
                    <td class="text-center pe-3">
                        <a href="{{ route('partner.students.show', $student) }}"
                           class="btn btn-outline-primary btn-sm py-0 px-2">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox d-block fs-3 mb-2"></i>Koi student nahi mila
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white border-top py-2 px-3">{{ $students->links() }}</div>
    @endif
</div>
@endsection
