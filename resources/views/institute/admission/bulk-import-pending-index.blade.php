@extends('institute.layout')
@section('title', 'Bulk Import Pending Review')
@section('breadcrumb', 'Admissions / Bulk Import / Pending Review')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-hourglass-split text-warning me-2"></i> Bulk Import Pending Review</h4>
        <small class="text-muted">Students imported from Excel, waiting for their fee history to be reviewed and approved.</small>
    </div>
    <a href="{{ route('admissions.bulk-import.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Bulk Import
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
        <form method="GET" action="{{ route('admissions.bulk-import.pending.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                       placeholder="Name, mobile, or Student UID">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Search
                </button>
            </div>
            @if(request('search'))
            <div class="col-md-2">
                <a href="{{ route('admissions.bulk-import.pending.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small">
            <i class="bi bi-people me-1 text-primary"></i>
            Pending Fee-History Review ({{ $students->total() }})
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Student UID</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Stream</th>
                    <th class="text-center">Semester</th>
                    <th>Will become</th>
                    <th>Session</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td class="ps-3 text-muted">{{ $student->student_uid }}</td>
                    <td class="fw-semibold">{{ $student->name }}</td>
                    <td>{{ $student->mobile }}</td>
                    <td class="text-muted small">{{ $student->stream?->course?->name ?? '—' }}</td>
                    <td class="text-muted small">{{ $student->stream?->name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary border" style="font-size:10px;">
                            S{{ $student->current_semester }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal" style="font-size:10px;">
                            {{ ucfirst(str_replace('_', ' ', $student->bulk_import_target_status ?? 'active')) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $student->session?->name ?? '—' }}</td>
                    <td class="text-end pe-3">
                        <div class="btn-group">
                            <a href="{{ route('admissions.bulk-import.pending.show', $student->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i> Review
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#rejectModal"
                                    data-reject-url="{{ route('admissions.bulk-import.pending.reject', $student->id) }}"
                                    data-student-name="{{ $student->name }}"
                                    title="Reject this student">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-check2-circle fs-2 d-block mb-2 text-success"></i>
                        No bulk-imported students are waiting for review right now.
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

{{-- Shared Reject Modal — reused for every row; JS below points its form at the
     clicked row's own reject URL before the modal opens. --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rejectForm" action="">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                            <i class="bi bi-x-circle text-danger fs-5"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0" id="rejectModalLabel">Reject <span id="rejectStudentName"></span></h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted small">
                        Use this for a row that should not have been imported — a duplicate, wrong data, etc.
                        The student is marked <strong>Cancelled</strong> and removed from this list.
                        This does not delete any record; it stays visible for audit purposes.
                    </p>
                    <label class="form-label fw-semibold small">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required minlength="3" maxlength="500"
                              placeholder="e.g. Duplicate of student ABC/STU/2026/000123"></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-x-circle me-1"></i> Reject Student
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('rejectModal')?.addEventListener('show.bs.modal', function (event) {
    const trigger = event.relatedTarget;
    if (!trigger) return;
    document.getElementById('rejectForm').action = trigger.dataset.rejectUrl;
    document.getElementById('rejectStudentName').textContent = trigger.dataset.studentName || '';
});
</script>
@endpush
