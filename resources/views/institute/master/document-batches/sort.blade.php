@extends('institute.layout')
@section('title','Sort & Verify')
@section('breadcrumb','Master / Marksheet & Degree / Batches / Sort & Verify')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Sort & Verify</h4>
        <small class="text-muted">
            {{ $documentBatch->course->name ?? '-' }}
            @if($documentBatch->courseStream) — {{ $documentBatch->courseStream->name }} @endif
            @if($documentBatch->coursePart) — {{ $documentBatch->coursePart->part_name }} @endif
            — {{ $documentBatch->session->name ?? '-' }}
            ({{ $documentBatch->document_type_label }}{{ $documentBatch->batch_label ? ', '.$documentBatch->batch_label : '' }})
        </small>
    </div>
    <a href="{{ route('master.document-batches.show', $documentBatch) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Physical bundle se milaan karke har student ka marksheet/degree mila ya nahi mark karein — isse baad me student aane par turant pata chal jayega.
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Found Status</th>
                    <th style="width:160px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $i => $row)
                <tr id="row-{{ $row->id }}">
                    <td class="text-muted small">{{ $i+1 }}</td>
                    <td>{{ $row->student->name ?? '-' }}</td>
                    <td>{{ $row->student->roll_no ?? '-' }}</td>
                    <td class="status-cell">
                        @if($row->is_found)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                Found <small class="text-muted">({{ $row->found_at->format('d M, h:i A') }})</small>
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Not Found</span>
                        @endif
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-sm {{ $row->is_found ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-found-btn"
                                data-url="{{ route('master.document-batches.students.found', [$documentBatch, $row]) }}">
                            {{ $row->is_found ? 'Mark Not Found' : 'Mark Found' }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.toggle-found-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const url = this.dataset.url;
        const row = this.closest('tr');
        const statusCell = row.querySelector('.status-cell');
        const button = this;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.found) {
                statusCell.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle">Found <small class="text-muted">(' + data.found_at + ')</small></span>';
                button.textContent = 'Mark Not Found';
                button.classList.remove('btn-outline-success');
                button.classList.add('btn-outline-danger');
            } else {
                statusCell.innerHTML = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Not Found</span>';
                button.textContent = 'Mark Found';
                button.classList.remove('btn-outline-danger');
                button.classList.add('btn-outline-success');
            }
        });
    });
});
</script>
@endpush
@endsection
