@extends('institute.layout')
@section('title','Marksheet & Degree Distribution')
@section('breadcrumb','Master / Marksheet & Degree / Distribution')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Marksheet & Degree Distribution</h4>
        <small class="text-muted">{{ $rows->total() }} record(s)</small>
    </div>
    <a href="{{ route('master.document-batches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Batches
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-medium">Session</label>
                <select class="form-select" name="session_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string)$sessionId === (string)$session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Document Type</label>
                <select class="form-select" name="document_type" onchange="document.getElementById('filterForm').submit()">
                    @foreach(\App\Models\DocumentBatch::$documentTypes as $key => $label)
                        <option value="{{ $key }}" {{ $documentType === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Course Type</label>
                <select class="form-select" name="course_type_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Course Types</option>
                    @foreach($courseTypes as $type)
                        <option value="{{ $type->id }}" {{ (string)$courseTypeId === (string)$type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Course</label>
                <select class="form-select" name="course_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ (string)$courseId === (string)$course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Stream</label>
                <select class="form-select" name="course_stream_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Streams</option>
                    @foreach($streams as $stream)
                        <option value="{{ $stream->id }}" {{ (string)$streamId === (string)$stream->id ? 'selected' : '' }}>{{ $stream->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Semester / Year</label>
                <select class="form-select" name="course_part_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Semesters</option>
                    @foreach($courseParts as $part)
                        <option value="{{ $part->id }}" {{ (string)$coursePartId === (string)$part->id ? 'selected' : '' }}>{{ $part->part_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Distribution Status</label>
                <select class="form-select" name="distribution_status" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All</option>
                    <option value="pending" {{ request('distribution_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="distributed" {{ request('distribution_status') === 'distributed' ? 'selected' : '' }}>Distributed</option>
                </select>
            </div>
            @if(request()->anyFilled(['session_id','course_type_id','course_id','course_stream_id','course_part_id','distribution_status']) || $documentType !== 'marksheet')
            <div class="col-12">
                <a href="{{ route('master.document-distribution.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Only students whose document has been marked "Found" in the Sort &amp; Verify step appear here.
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="font-size:12px;">
            <thead style="background:#1e3a5f; color:#fff;">
                <tr>
                    <th class="ps-2" style="min-width:110px; white-space:nowrap;">Student UID</th>
                    <th style="min-width:150px;">Name</th>
                    <th style="min-width:180px;">Batch</th>
                    <th style="min-width:85px; white-space:nowrap;">Found On</th>
                    <th style="min-width:110px; white-space:nowrap;">Due</th>
                    <th style="min-width:130px; white-space:nowrap;">Status</th>
                    <th style="min-width:100px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="ps-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px; font-weight:600;">
                            {{ $row->student->student_uid ?? '—' }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">{{ $row->student->name ?? '-' }}</div>
                        <div class="text-muted" style="font-size:10.5px;">{{ $row->student->roll_no ?? '' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold" style="font-size:12px; line-height:1.3;">
                            {{ $row->batch->course->name ?? '-' }}
                            @if($row->batch->courseStream) — {{ $row->batch->courseStream->name }} @endif
                        </div>
                        <div class="text-muted" style="font-size:10.5px;">
                            {{ $row->batch->coursePart->part_name ?? '' }}
                            @if($row->batch->coursePart && $row->batch->batch_label) &middot; @endif
                            {{ $row->batch->batch_label ?? '' }}
                        </div>
                    </td>
                    <td class="text-muted fw-semibold" style="white-space:nowrap;">{{ $row->found_at?->format('d M Y') }}</td>
                    <td>
                        @if($row->due > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle" style="font-size:10px; font-weight:600;">₹{{ number_format($row->due, 2) }}</span>
                            <a href="{{ route('fee.create', ['student_id' => $row->student_id]) }}" target="_blank" class="d-block" style="font-size:10.5px;">Clear Due</a>
                        @else
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle" style="font-size:10px; font-weight:600;">No Due</span>
                        @endif
                    </td>
                    <td>
                        @if($row->is_distributed)
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle" style="font-size:10px; font-weight:600;">Distributed</span>
                            <div class="text-muted" style="font-size:10.5px;">{{ $row->distributed_at->format('d M Y') }} — {{ $row->received_by_name }}</div>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle" style="font-size:10px; font-weight:600;">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if(!$row->is_distributed)
                        <button type="button" class="btn btn-sm btn-primary distribute-btn"
                                data-url="{{ route('master.document-distribution.distribute', $row) }}"
                                data-student="{{ $row->student->name ?? '' }}"
                                data-fee="{{ $row->defaultFeeAmount ?? '' }}"
                                style="padding:3px 10px; font-size:11px;">
                            Distribute
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rows->links() }}</div>

{{-- Distribute Modal --}}
<div class="modal fade" id="distributeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="distributeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Distribute to <span id="distributeStudentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Distributed Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="distributed_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Received By <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="received_by_name" placeholder="Student / relative name" required>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12 form-check">
                            <input type="checkbox" class="form-check-input" id="chargeFeeToggle">
                            <label class="form-check-label fw-medium" for="chargeFeeToggle">Charge marksheet/degree fee?</label>
                            <div class="form-text">
                                Amount auto-fills from <a href="{{ route('master.document-fee-settings.edit') }}" target="_blank">Fee Settings</a> if configured for this course — editable below.
                            </div>
                        </div>
                        <div id="feeFieldsWrap" class="col-12 row g-3 d-none">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Fee Amount</label>
                                <input type="number" class="form-control" name="fee_amount" min="0" step="0.01" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Income Category</label>
                                <select class="form-select" name="income_category_id" disabled>
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (string) $defaultCategoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const distributeModalEl = document.getElementById('distributeModal');
const distributeModal   = new bootstrap.Modal(distributeModalEl);
const distributeForm    = document.getElementById('distributeForm');
const chargeFeeToggle   = document.getElementById('chargeFeeToggle');
const feeFieldsWrap     = document.getElementById('feeFieldsWrap');
const feeAmountInput    = feeFieldsWrap.querySelector('[name="fee_amount"]');
const feeInputs         = feeFieldsWrap.querySelectorAll('input, select');
let currentDefaultFee   = '';

function setFeeFieldsVisible(visible) {
    feeFieldsWrap.classList.toggle('d-none', !visible);
    feeInputs.forEach(el => el.disabled = !visible);
    if (visible && !feeAmountInput.value && currentDefaultFee) {
        feeAmountInput.value = currentDefaultFee;
    }
}

chargeFeeToggle.addEventListener('change', function () {
    setFeeFieldsVisible(this.checked);
});

document.querySelectorAll('.distribute-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        distributeForm.reset();
        chargeFeeToggle.checked = false;
        currentDefaultFee = this.dataset.fee || '';
        setFeeFieldsVisible(false);
        distributeForm.action = this.dataset.url;
        document.getElementById('distributeStudentName').textContent = this.dataset.student;
        distributeModal.show();
    });
});
</script>
@endpush
@endsection
