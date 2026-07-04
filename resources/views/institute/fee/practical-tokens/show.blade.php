@php
    $feeLayout = auth()->guard('staff')->check() ? 'staff.layout' : 'institute.layout';
@endphp
@extends($feeLayout)
@section('title', 'Practical Token Entry')
@section('breadcrumb', 'Fee / Practical Tokens / Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">{{ $batch->title ?: 'Practical Token Batch #' . $batch->id }}</h4>
        <small class="text-muted">{{ $batch->course?->name }} / {{ $batch->subject?->name }} / Sem {{ $batch->semester }}</small>
    </div>
    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="border rounded bg-white p-3 shadow-sm">
            <div class="text-muted small">Token Amount</div>
            <div class="fs-5 fw-bold">{{ number_format((float) $batch->token_amount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded bg-white p-3 shadow-sm">
            <div class="text-muted small">Posted</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($postedAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded bg-white p-3 shadow-sm">
            <div class="text-muted small">Remaining</div>
            <div class="fs-5 fw-bold text-primary">{{ number_format($remainingAmount, 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded bg-white p-3 shadow-sm">
            <div class="text-muted small">Students</div>
            <div class="fs-5 fw-bold">{{ $students->count() }}</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route($routePrefix . '.entries.store', $batch) }}">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="table-responsive" style="max-height:70vh;">
            <table class="table table-bordered table-hover align-middle mb-0 small">
                <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Father Name</th>
                        <th>Course</th>
                        <th>Class / Stream</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Practical Fee Charged</th>
                        <th>Practical Fee Collect</th>
                        <th>Fine</th>
                        <th>Discount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $entry = $entries[$student->id] ?? null;
                            $alreadyPaid = in_array($student->id, $alreadyPaidStudentIds ?? [], true);
                            $inputDisabled = $entry || $alreadyPaid;
                            $collectValue = old('amounts.' . $student->id, $entry?->amount ?? ($alreadyPaid ? ($studentPracticalFees[$student->id] ?? 0) : 0));
                            $fineValue = old('fines.' . $student->id, $entry?->fine ?? ($alreadyPaid ? 0 : 0));
                            $discountValue = old('discounts.' . $student->id, $entry?->discount ?? ($alreadyPaid ? 0 : 0));
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $student->student_uid }}</td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->father_name ?: '-' }}</td>
                            <td>{{ $student->stream?->course?->name ?: '-' }}</td>
                            <td>{{ $student->stream?->name ?: '-' }}</td>
                            <td>{{ $student->coursePart?->year_label ?: ($batch->year_number . ' Year') }}</td>
                            <td>{{ $batch->semester }}</td>
                            <td class="text-end">
                                {{ number_format((float) ($studentPracticalFees[$student->id] ?? 0), 2) }}
                            </td>
                            <td>
                                <input type="number"
                                       name="amounts[{{ $student->id }}]"
                                       value="{{ $collectValue }}"
                                       min="0"
                                       max="{{ number_format((float) ($studentPracticalFees[$student->id] ?? 0), 2, '.', '') }}"
                                       step="0.01"
                                       class="form-control form-control-sm fee-collect"
                                       {{ $inputDisabled ? 'disabled' : '' }}
                                       onblur="clampEntryValue(this)">
                            </td>
                            <td>
                                <input type="number"
                                       name="fines[{{ $student->id }}]"
                                       value="{{ $fineValue }}"
                                       min="0"
                                       step="0.01"
                                       class="form-control form-control-sm fee-fine"
                                       {{ $inputDisabled ? 'disabled' : '' }}
                                       onblur="clampEntryValue(this)">
                            </td>
                            <td>
                                <input type="number"
                                       name="discounts[{{ $student->id }}]"
                                       value="{{ $discountValue }}"
                                       min="0"
                                       max="{{ number_format((float) ($studentPracticalFees[$student->id] ?? 0), 2, '.', '') }}"
                                       step="0.01"
                                       class="form-control form-control-sm fee-discount"
                                       {{ $inputDisabled ? 'disabled' : '' }}
                                       onblur="clampEntryValue(this)">
                            </td>
                            <td>
                                @if($entry)
                                    <span class="badge bg-success">Posted</span>
                                    <small class="d-block text-muted">Invoice #{{ $entry->invoice?->invoice_no }}</small>
                                @elseif($alreadyPaid)
                                    <span class="badge bg-warning text-dark">Already Paid</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-5">No students found for this course/subject/year/semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-column flex-md-row gap-2">
            <div>
                <span class="text-muted small">New Entry Total:</span>
                <span class="fw-bold" id="entryTotal">0.00</span>
            </div>
            <div>
                <span class="text-muted small">Breakdown:</span>
                <span class="fw-bold" id="entryBreakdown">Cash : 0.00 | Fine : 0.00 | Discount : 0.00</span>
            </div>
            <button class="btn btn-primary" {{ $students->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle me-1"></i> Submit Amounts
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function clampEntryValue(input) {
    if (!input) return;
    const min = parseFloat(input.min) || 0;
    const max = parseFloat(input.max);
    const rawValue = input.value.trim();
    const value = parseFloat(rawValue);

    if (rawValue === '') {
        input.value = '';
        updateEntryTotal();
        return;
    }

    if (Number.isNaN(value)) {
        input.value = min.toFixed(2);
        updateEntryTotal();
        return;
    }

    let clamped = value;
    if (clamped < min) {
        clamped = min;
    }
    if (!Number.isNaN(max) && clamped > max) {
        clamped = max;
    }

    if (clamped !== value || !rawValue.includes('.') || rawValue.match(/\.\d{3,}$/)) {
        input.value = clamped.toFixed(2);
    }

    updateEntryTotal();
}

function updateEntryTotal() {
    const collectTotal = Array.from(document.querySelectorAll('.fee-collect:not(:disabled)'))
        .reduce((sum, input) => sum + (parseFloat(input.value || 0) || 0), 0);
    const fineTotal = Array.from(document.querySelectorAll('.fee-fine:not(:disabled)'))
        .reduce((sum, input) => sum + (parseFloat(input.value || 0) || 0), 0);
    const discountTotal = Array.from(document.querySelectorAll('.fee-discount:not(:disabled)'))
        .reduce((sum, input) => sum + (parseFloat(input.value || 0) || 0), 0);
    const total = collectTotal + fineTotal - discountTotal;
    document.getElementById('entryTotal').textContent = total.toFixed(2);
    document.getElementById('entryBreakdown').textContent =
        'Cash : ' + collectTotal.toFixed(2) +
        ' | Fine : ' + fineTotal.toFixed(2) +
        ' | Discount : ' + discountTotal.toFixed(2);
}
document.querySelectorAll('.fee-collect, .fee-fine, .fee-discount').forEach(input => input.addEventListener('input', updateEntryTotal));
updateEntryTotal();
</script>
@endpush
