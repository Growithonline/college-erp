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
        <form method="GET" class="row g-3" id="batchFilterForm">
            <div class="col-md-4">
                <label class="form-label fw-medium">Academic Session <span class="text-danger">*</span></label>
                <select class="form-select" name="session_id" required onchange="document.getElementById('batchFilterForm').submit()">
                    <option value="">Select Session</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string)$sessionId === (string)$session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Document Type <span class="text-danger">*</span></label>
                <select class="form-select" name="document_type" required onchange="document.getElementById('batchFilterForm').submit()">
                    @foreach(\App\Models\DocumentBatch::$documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ $documentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Course Type <span class="text-danger">*</span></label>
                <select class="form-select" name="course_type_id" required onchange="document.getElementById('batchFilterForm').submit()">
                    <option value="">Select Course Type</option>
                    @foreach($courseTypes as $type)
                        <option value="{{ $type->id }}" {{ (string)$courseTypeId === (string)$type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Course <span class="text-danger">*</span></label>
                <select class="form-select" name="course_id" required onchange="document.getElementById('batchFilterForm').submit()" {{ $courseTypeId ? '' : 'disabled' }}>
                    <option value="">{{ $courseTypeId ? 'Select Course' : 'Select Course Type first' }}</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string)$courseId === (string)$course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-medium">Stream <span class="text-danger">*</span></label>
                <select class="form-select" name="course_stream_id" required onchange="document.getElementById('batchFilterForm').submit()" {{ $courseId ? '' : 'disabled' }}>
                    <option value="">{{ $courseId ? 'Select Stream' : 'Select Course first' }}</option>
                    @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" {{ (string)$streamId === (string)$stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Different streams' documents can arrive at different times.</small>
            </div>
            @if($documentType === 'marksheet')
            <div class="col-md-4">
                <label class="form-label fw-medium">Semester / Year <span class="text-danger">*</span></label>
                <select class="form-select" name="course_part_id" required onchange="document.getElementById('batchFilterForm').submit()" {{ $courseId ? '' : 'disabled' }}>
                    <option value="">{{ $courseId ? 'Select Semester' : 'Select Course first' }}</option>
                    @foreach($courseParts as $part)
                        <option value="{{ $part->id }}" {{ (string)$coursePartId === (string)$part->id ? 'selected' : '' }}>{{ $part->part_name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Marksheet is per-semester exam, not whole-course completion.</small>
            </div>
            @endif
            <div class="col-12">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i> Load Eligible Students
                </button>
            </div>
        </form>
    </div>
</div>

@if($sessionId && $courseId && $streamId && ($documentType === 'degree' || $coursePartId))
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">Eligible Students ({{ $students->count() }})</h6>
        <small class="text-muted">
            {{ $documentType === 'degree' ? 'Course complete, status = Passed Out' : 'Enrolled in this semester + stream during this session (any current status)' }}
        </small>
    </div>
    <div class="card-body">
        @if($students->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-people opacity-25 fs-2 d-block mb-2"></i>
                No eligible students found for this selection.
            </div>
        @else
        <form method="POST" action="{{ route('master.document-batches.store') }}">
            @csrf
            <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
            <input type="hidden" name="course_id" value="{{ $courseId }}">
            <input type="hidden" name="course_stream_id" value="{{ $streamId }}">
            <input type="hidden" name="document_type" value="{{ $documentType }}">
            @if($documentType === 'marksheet')
            <input type="hidden" name="course_part_id" value="{{ $coursePartId }}">
            @endif

            <div class="mb-3">
                <label class="form-label fw-medium">Batch Label</label>
                <input type="text" class="form-control" name="batch_label" placeholder="e.g. Batch 1, Backlog Batch" style="max-width:300px;">
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size:12px;">
                    <thead style="background:#1e3a5f; color:#fff;">
                        <tr>
                            <th class="ps-2" style="width:30px;">
                                <input type="checkbox" class="form-check-input" id="selectAll" checked>
                            </th>
                            <th style="min-width:70px; white-space:nowrap;">Session</th>
                            <th style="min-width:110px; white-space:nowrap;">Student UID</th>
                            <th style="min-width:150px; white-space:nowrap;">Name</th>
                            <th style="min-width:70px; white-space:nowrap;">Roll No</th>
                            <th style="min-width:90px; white-space:nowrap;">Enroll No</th>
                            <th style="min-width:110px; white-space:nowrap;">Father Name</th>
                            <th style="min-width:110px; white-space:nowrap;">Mother Name</th>
                            <th style="min-width:90px; white-space:nowrap;">Due</th>
                            <th style="min-width:220px;">Subjects</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td class="ps-2"><input type="checkbox" class="form-check-input student-check" name="student_ids[]" value="{{ $student->id }}" checked></td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" style="font-size:10px; font-weight:600; white-space:nowrap;">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $student->session?->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px; font-weight:600;">
                                    {{ $student->student_uid ?? '—' }}
                                </span>
                            </td>
                            <td class="fw-semibold">{{ $student->name }}</td>
                            <td class="text-muted fw-semibold">{{ $student->roll_no ?? '—' }}</td>
                            <td class="text-muted fw-semibold">{{ $student->enrollment_no ?? '—' }}</td>
                            <td class="fw-semibold" style="white-space:nowrap;">{{ $student->father_name ?: '—' }}</td>
                            <td class="fw-semibold" style="white-space:nowrap;">{{ $student->mother_name ?: '—' }}</td>
                            <td>
                                @if($student->totalDue > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle" style="font-size:10px; font-weight:600;">
                                        ₹{{ number_format($student->totalDue, 2) }}
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size:10px; font-weight:600;">
                                        No Due
                                    </span>
                                @endif
                            </td>
                            <td>
                                @forelse($student->subjects as $subject)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle me-1 mb-1" style="font-size:10px; font-weight:500;">
                                        {{ $subject->name }}
                                    </span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
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
