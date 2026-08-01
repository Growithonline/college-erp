@extends('institute.layout')
@section('title','Document Batches')
@section('breadcrumb','Master / Marksheet & Degree / Batches')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Marksheet & Degree Batches</h4>
        <small class="text-muted">{{ $batches->total() }} batch(es)</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.document-fee-settings.edit') }}" class="btn btn-outline-secondary">
            <i class="bi bi-currency-rupee me-1"></i> Fee Settings
        </a>
        <a href="{{ route('master.document-distribution.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i> Distribution
        </a>
        <a href="{{ route('master.document-batches.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Batch
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-medium">Session</label>
                <select class="form-select" name="session_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string)request('session_id') === (string)$session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Course</label>
                <select class="form-select" name="course_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string)request('course_id') === (string)$course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Document Type</label>
                <select class="form-select" name="document_type" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Types</option>
                    @foreach(\App\Models\DocumentBatch::$documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('document_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Status</label>
                <select class="form-select" name="status" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Status</option>
                    @foreach(\App\Models\DocumentBatch::$statuses as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($batches->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-file-earmark-text" style="font-size:3rem;color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Batches Yet</h5>
            <a href="{{ route('master.document-batches.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Create First Batch
            </a>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Session</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Semester / Batch Label</th>
                        <th>Total</th>
                        <th>Found</th>
                        <th>Distributed</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                    <tr>
                        <td>{{ $batch->session->name ?? '-' }}</td>
                        <td>{{ $batch->course->name ?? '-' }}</td>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $batch->document_type_label }}</span></td>
                        <td>
                            {{ $batch->coursePart->part_name ?? '' }}
                            @if($batch->coursePart && $batch->batch_label) &middot; @endif
                            {{ $batch->batch_label ?? '' }}
                            @if(!$batch->coursePart && !$batch->batch_label) - @endif
                        </td>
                        <td>{{ $batch->students_count }}</td>
                        <td>{{ $batch->found_count }}</td>
                        <td>{{ $batch->distributed_count }}</td>
                        <td>
                            <span class="badge {{ match($batch->status) { 'received' => 'bg-success', 'dispatched' => 'bg-warning text-dark', default => 'bg-secondary' } }}">
                                {{ $batch->status_label }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('master.document-batches.show', $batch) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $batches->links() }}</div>
@endif
@endsection
