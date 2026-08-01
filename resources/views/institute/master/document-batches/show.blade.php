@extends('institute.layout')
@section('title','Batch Detail')
@section('breadcrumb','Master / Marksheet & Degree / Batches / Detail')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            {{ $documentBatch->course->name ?? '-' }}
            @if($documentBatch->courseStream) — {{ $documentBatch->courseStream->name }} @endif
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

@php
    $statusColor = match($documentBatch->status) {
        'received'   => ['bg' => 'success', 'icon' => 'bi-box-seam-fill'],
        'dispatched' => ['bg' => 'warning', 'icon' => 'bi-truck'],
        default      => ['bg' => 'secondary', 'icon' => 'bi-hourglass-split'],
    };
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1">{{ $totalCount }}</div>
                    <small class="text-muted">Total Students</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="bi bi-check2-circle fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-success">{{ $foundCount }}</div>
                    <small class="text-muted">Found</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="bi bi-send-check-fill fs-5"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-info">{{ $distributedCount }}</div>
                    <small class="text-muted">Distributed</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-{{ $statusColor['bg'] }} bg-opacity-10 text-{{ $statusColor['bg'] }} d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i class="bi {{ $statusColor['icon'] }} fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold lh-1" style="font-size:15px;">{{ $documentBatch->status_label }}</div>
                    <small class="text-muted">Batch Status</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-send text-primary"></i>
                <h6 class="fw-semibold mb-0">University Dispatch</h6>
            </div>
            <div class="card-body">
                @if($documentBatch->dispatch_date)
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Dispatched</span>
                        <span class="fw-semibold">{{ $documentBatch->dispatch_date->format('d M Y') }}</span>
                    </div>
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
            <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-primary"></i>
                <h6 class="fw-semibold mb-0">Received at Institute</h6>
            </div>
            <div class="card-body">
                @if($documentBatch->received_date)
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Received</span>
                        <span class="fw-semibold">{{ $documentBatch->received_date->format('d M Y') }}</span>
                    </div>
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
    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-semibold mb-0">Students in this Batch</h6>
        <small class="text-muted">{{ $documentBatch->students->count() }} student(s)</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size:12px;">
            <thead style="background:#1e3a5f; color:#fff;">
                <tr>
                    <th class="ps-2" style="min-width:70px; white-space:nowrap;">Session</th>
                    <th style="min-width:110px; white-space:nowrap;">Student UID</th>
                    <th style="min-width:150px; white-space:nowrap;">Name</th>
                    <th style="min-width:70px; white-space:nowrap;">Roll No</th>
                    <th style="min-width:90px; white-space:nowrap;">Enroll No</th>
                    <th style="min-width:110px; white-space:nowrap;">Father Name</th>
                    <th style="min-width:110px; white-space:nowrap;">Mother Name</th>
                    <th style="min-width:80px; white-space:nowrap;">Found</th>
                    <th style="min-width:90px; white-space:nowrap;">Distributed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documentBatch->students as $row)
                <tr>
                    <td class="ps-2">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" style="font-size:10px; font-weight:600; white-space:nowrap;">
                            <i class="bi bi-calendar3 me-1"></i>{{ $row->student->session?->name ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px; font-weight:600;">
                            {{ $row->student->student_uid ?? '—' }}
                        </span>
                    </td>
                    <td class="fw-semibold">{{ $row->student->name ?? '-' }}</td>
                    <td class="text-muted fw-semibold">{{ $row->student->roll_no ?? '—' }}</td>
                    <td class="text-muted fw-semibold">{{ $row->student->enrollment_no ?? '—' }}</td>
                    <td class="fw-semibold" style="white-space:nowrap;">{{ $row->student->father_name ?: '—' }}</td>
                    <td class="fw-semibold" style="white-space:nowrap;">{{ $row->student->mother_name ?: '—' }}</td>
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
                <tr><td colspan="9" class="text-center text-muted py-4">No students in this batch.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
