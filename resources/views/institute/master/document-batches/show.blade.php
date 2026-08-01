@extends('institute.layout')
@section('title','Batch Detail')
@section('breadcrumb','Master / Marksheet & Degree / Batches / Detail')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            {{ $documentBatch->course->name ?? '-' }}
            @if($documentBatch->coursePart) — {{ $documentBatch->coursePart->part_name }} @endif
            — {{ $documentBatch->session->name ?? '-' }}
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">{{ $documentBatch->document_type_label }}</span>
        </h4>
        <small class="text-muted">{{ $documentBatch->batch_label ?? 'No batch label' }}</small>
    </div>
    <a href="{{ route('master.document-batches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-4 fw-bold">{{ $totalCount }}</div>
            <small class="text-muted">Total Students</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-4 fw-bold text-success">{{ $foundCount }}</div>
            <small class="text-muted">Found</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-4 fw-bold text-primary">{{ $distributedCount }}</div>
            <small class="text-muted">Distributed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <span class="badge {{ match($documentBatch->status) { 'received' => 'bg-success', 'dispatched' => 'bg-warning text-dark', default => 'bg-secondary' } }} fs-6">
                {{ $documentBatch->status_label }}
            </span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h6 class="fw-semibold mb-0"><i class="bi bi-send me-1"></i> University Dispatch</h6>
            </div>
            <div class="card-body">
                @if($documentBatch->dispatch_date)
                    <p class="mb-1"><strong>Dispatched on:</strong> {{ $documentBatch->dispatch_date->format('d M Y') }}</p>
                    <p class="text-muted small mb-0">{{ $documentBatch->dispatch_remarks ?? 'No remarks' }}</p>
                @else
                <form method="POST" action="{{ route('master.document-batches.dispatch', $documentBatch) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label fw-medium">Dispatch Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="dispatch_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-medium">Remarks</label>
                        <input type="text" class="form-control" name="dispatch_remarks">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Mark Dispatched</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h6 class="fw-semibold mb-0"><i class="bi bi-box-seam me-1"></i> Received at Institute</h6>
            </div>
            <div class="card-body">
                @if($documentBatch->received_date)
                    <p class="mb-1"><strong>Received on:</strong> {{ $documentBatch->received_date->format('d M Y') }}</p>
                    <p class="text-muted small mb-2">Package count: {{ $documentBatch->received_count ?? '-' }}</p>
                    <a href="{{ route('master.document-batches.sort', $documentBatch) }}" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-check2-square me-1"></i> Sort & Verify Students
                    </a>
                @else
                <form method="POST" action="{{ route('master.document-batches.receive', $documentBatch) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label fw-medium">Received Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="received_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-medium">Package Count</label>
                        <input type="number" class="form-control" name="received_count" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Mark Received</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h6 class="fw-semibold mb-0">Students in this Batch</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Found</th>
                    <th>Distributed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documentBatch->students as $row)
                <tr>
                    <td>{{ $row->student->name ?? '-' }}</td>
                    <td>{{ $row->student->roll_no ?? '-' }}</td>
                    <td>
                        @if($row->is_found)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Found</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if($row->is_distributed)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Distributed</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No students in this batch.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
