@extends('institute.layout')
@section('title', 'Subject Fee Structure')
@section('breadcrumb', 'Master / Fee Structure / Subject Fees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-book me-2 text-primary"></i>Subject Fee Structure</h4>
        <small class="text-muted">Set subject fees by Course, Year, and Semester</small>
    </div>
    <a href="{{ route('master.fee-structure.course-fees') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-currency-rupee me-1"></i> Course Fees
    </a>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" id="filterForm" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Course</label>
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Year</label>
                <select name="course_part" class="form-select form-select-sm">
                    <option value="">All Years</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" {{ request('course_part') == $i ? 'selected' : '' }}>
                            Year {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Semester</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" {{ request('semester') == 1 ? 'selected' : '' }}>Sem 1</option>
                    <option value="2" {{ request('semester') == 2 ? 'selected' : '' }}>Sem 2</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel me-1"></i> Load
                </button>
            </div>
        </form>
    </div>
</div>

@if($selectedCourse && request('course_part') && request('semester') !== null)

{{-- BULK ENTRY TABLE --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header py-2" style="background:#1e293b; color:white;">
        <span class="fw-bold small">
            <i class="bi bi-table me-2"></i>
            Subject Fees — {{ $selectedCourse->name }},
            Year {{ request('course_part') }}, Sem {{ request('semester') }}
        </span>
    </div>
    <form method="POST" action="{{ route('master.fee-structure.subject-fees.bulk') }}">
        @csrf
        <input type="hidden" name="academic_session_id" value="{{ $sessionId }}">
        <input type="hidden" name="course_id" value="{{ $selectedCourse->id }}">
        <input type="hidden" name="course_part" value="{{ request('course_part') }}">
        <input type="hidden" name="semester" value="{{ request('semester') }}">

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light" style="font-size:12px;">
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Type</th>
                        <th>Has Practical</th>
                        <th style="width:160px;">Subject Fee ₹</th>
                        <th style="width:160px;">Practical Fee ₹</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody style="font-size:13px;">
                    @php
                        $existingRules = $rules->keyBy('subject_id');
                    @endphp
                    @foreach($subjects as $i => $subject)
                    @php $existing = $existingRules[$subject->id] ?? null; @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">
                            {{ $subject->name }}
                            @if($subject->code)
                                <span class="text-muted small">({{ $subject->code }})</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $subject->subject_type == 'major' ? 'bg-primary' : 'bg-secondary' }}">
                                {{ ucfirst($subject->subject_type ?? 'minor') }}
                            </span>
                        </td>
                        <td>
                            @if($subject->has_practical)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            <input type="hidden" name="fees[{{ $i }}][subject_id]" value="{{ $subject->id }}">
                            <input type="number"
                                   name="fees[{{ $i }}][subject_fee]"
                                   class="form-control form-control-sm subject-fee"
                                   data-row="{{ $i }}"
                                   value="{{ $existing?->subject_fee ?? 0 }}"
                                   min="0" step="0.01"
                                   oninput="updateTotal({{ $i }})">
                        </td>
                        <td>
                            <input type="number"
                                   name="fees[{{ $i }}][practical_fee]"
                                   class="form-control form-control-sm practical-fee {{ !$subject->has_practical ? 'bg-light' : '' }}"
                                   data-row="{{ $i }}"
                                   value="{{ $existing?->practical_fee ?? 0 }}"
                                   min="0" step="0.01"
                                   {{ !$subject->has_practical ? 'readonly' : '' }}
                                   oninput="updateTotal({{ $i }})">
                        </td>
                        <td class="text-end fw-semibold text-success" id="total-{{ $i }}">
                            ₹ {{ number_format(($existing?->subject_fee ?? 0) + ($existing?->practical_fee ?? 0), 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                        <td class="text-end fw-bold text-success" id="grand-total">
                            ₹ {{ number_format($rules->sum(fn($r) => $r->subject_fee + $r->practical_fee), 0) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-lg me-1"></i> Save All Subject Fees
            </button>
        </div>
    </form>
</div>

@elseif($selectedCourse)
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Course selected — now select <b>Year</b> and <b>Semester</b> to view fees.
</div>
@else
<div class="text-center text-muted py-5">
    <i class="bi bi-arrow-up-circle fs-2 d-block mb-2"></i>
    Select Course + Year + Semester to load subjects
</div>
@endif

@endsection

@push('scripts')
<script>
function updateTotal(row) {
    const sf = parseFloat(document.querySelector(`input[name="fees[${row}][subject_fee]"]`).value) || 0;
    const pf = parseFloat(document.querySelector(`input[name="fees[${row}][practical_fee]"]`).value) || 0;
    document.getElementById(`total-${row}`).textContent = '₹ ' + (sf + pf).toLocaleString('en-IN');

    // Update grand total
    let grand = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        grand += parseFloat(el.textContent.replace('₹ ', '').replace(/,/g, '')) || 0;
    });
    document.getElementById('grand-total').textContent = '₹ ' + grand.toLocaleString('en-IN');
}
</script>
@endpush
