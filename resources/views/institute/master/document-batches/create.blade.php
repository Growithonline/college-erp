@extends('institute.layout')
@section('title','Create Document Batch')
@section('breadcrumb','Master / Marksheet & Degree / Batches / Create')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Create Document Batch</h4>
    <a href="{{ route('master.document-batches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-medium">Academic Session <span class="text-danger">*</span></label>
                <select class="form-select" name="session_id" required>
                    <option value="">Select Session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string)$sessionId === (string)$session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Course <span class="text-danger">*</span></label>
                <select class="form-select" name="course_id" required>
                    <option value="">Select Course</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string)$courseId === (string)$course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Document Type <span class="text-danger">*</span></label>
                <select class="form-select" name="document_type" required>
                    @foreach(\App\Models\DocumentBatch::$documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ $documentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i> Load Eligible Students
                </button>
            </div>
        </form>
    </div>
</div>

@if($sessionId && $courseId)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">Eligible Students ({{ $students->count() }})</h6>
        <small class="text-muted">Final year, status = Passed Out</small>
    </div>
    <div class="card-body">
        @if($students->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-people opacity-25 fs-2 d-block mb-2"></i>
                No passed-out students found for this session &amp; course.
            </div>
        @else
        <form method="POST" action="{{ route('master.document-batches.store') }}">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
            <input type="hidden" name="course_id" value="{{ $courseId }}">
            <input type="hidden" name="document_type" value="{{ $documentType }}">

            <div class="mb-3">
                <label class="form-label fw-medium">Batch Label</label>
                <input type="text" class="form-control" name="batch_label" placeholder="e.g. Batch 1, Backlog Batch" style="max-width:300px;">
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" class="form-check-input" id="selectAll" checked>
                            </th>
                            <th>Name</th>
                            <th>Roll No</th>
                            <th>Enrollment No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td><input type="checkbox" class="form-check-input student-check" name="student_ids[]" value="{{ $student->id }}" checked></td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->roll_no ?? '-' }}</td>
                            <td>{{ $student->enrollment_no ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Create Batch
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
@endif

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
@endsection
