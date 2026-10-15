@extends('institute.layout')
@section('title', 'Review Fee History')
@section('breadcrumb', 'Admissions / Bulk Import / Pending Review / ' . $student->name)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-clipboard-check text-primary me-2"></i> Review Fee History</h4>
        <small class="text-muted">{{ $student->name }} — {{ $student->student_uid }}</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle me-1"></i> Reject
        </button>
        <a href="{{ route('admissions.bulk-import.pending.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Error:</strong> {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Student summary --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Course / Stream</div>
                <div class="fw-semibold">{{ $student->stream?->course?->name ?? '—' }} — {{ $student->stream?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Current Semester</div>
                <div class="fw-semibold">Semester {{ $student->current_semester }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Session</div>
                <div class="fw-semibold">{{ $student->session?->name ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Mobile</div>
                <div class="fw-semibold">{{ $student->mobile }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Will become, once approved</div>
                <div class="fw-semibold">
                    <span class="badge bg-success bg-opacity-10 text-success">
                        {{ ucfirst(str_replace('_', ' ', $student->bulk_import_target_status ?? 'active')) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admissions.bulk-import.pending.approve', $student->id) }}" id="approveForm">
    @csrf

    @if(count($periods) === 0)
        {{-- Nothing to review: e.g. a fresh Semester 1 import --}}
        <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #16a34a !important;">
            <div class="card-body py-4 text-center">
                <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                <div class="fw-semibold">This student has no earlier semesters to review.</div>
                <small class="text-muted">Semester {{ $student->current_semester }} is their first semester in this system — its own fee was already charged when the file was imported.</small>
            </div>
        </div>
    @else
        <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <strong>Suggested Total</strong> is a reference only, calculated from your institute's CURRENT fee rules —
                the actual fee structure at the time may have been different, and this does not include subject-specific
                fees for past semesters (this file does not capture which subjects the student took back then). You may
                adjust <strong>Total Fee</strong> for your own reference, but it is not used in any calculation below.
                <br class="d-none d-md-block">
                Enter only the <strong>Previous Due Amount</strong> — whatever the student still genuinely owes for that
                semester. Whatever was already paid before joining this system is never reconstructed as a receipt here;
                only the outstanding amount is carried forward as a due, collectible today exactly like any other fee due.
                Leave it at 0 if the semester was fully paid.
                <br class="d-none d-md-block">
                Whenever a semester has any due at all, pick the <strong>Session</strong> it actually belongs to
                (e.g. Semester 1-2 of a student now in Semester 4 usually belongs to an EARLIER session than their
                current one) — the system has guessed one where it could, always double-check it. This is what that
                session's own books reflect this amount against, instead of the student's current session.
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">
                    <i class="bi bi-cash-coin me-1 text-primary"></i>
                    Fee History — {{ count($periods) }} semester(s) to review
                </span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="markAllNoDueBtn">
                    <i class="bi bi-check2-all me-1"></i> Mark All No Due
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Period</th>
                            <th class="text-end">Suggested Total (reference)</th>
                            <th class="text-end" style="width:140px;">Total Fee (reference)</th>
                            <th class="text-end" style="width:150px;">Previous Due Amount</th>
                            <th style="width:190px;">Session</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                        @php $n = $period['period_number']; @endphp
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $period['label'] }}</td>
                            <td class="text-end text-muted">₹{{ number_format($period['suggested_total'], 2) }}</td>
                            <td class="text-end">
                                <input type="number" step="0.01" min="0"
                                       name="periods[{{ $n }}][total_fee]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ old("periods.$n.total_fee", $period['suggested_total']) }}">
                            </td>
                            <td class="text-end">
                                <input type="number" step="0.01" min="0"
                                       name="periods[{{ $n }}][due_amount]"
                                       class="form-control form-control-sm text-end period-due-amount"
                                       data-period="{{ $n }}"
                                       value="{{ old("periods.$n.due_amount", 0) }}">
                            </td>
                            <td>
                                @if($sessions->isEmpty())
                                    <div class="text-danger small">
                                        No sessions exist yet.
                                        <a href="{{ route('master.sessions.create') }}" target="_blank" class="d-block">
                                            <i class="bi bi-plus-circle me-1"></i>Create one
                                        </a>
                                    </div>
                                @else
                                    <select name="periods[{{ $n }}][session_id]" class="form-select form-select-sm">
                                        <option value="">— Select session —</option>
                                        @foreach($sessions as $s)
                                            <option value="{{ $s->id }}"
                                                {{ old("periods.$n.session_id", $period['suggested_session_id']) == $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if(!$period['suggested_session_id'])
                                        <div class="small text-warning mt-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Not auto-detected —
                                            <a href="{{ route('master.sessions.create') }}" target="_blank">create it</a> if missing.
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td class="ps-3 fw-bold" colspan="3">Total Due (all semesters)</td>
                            <td class="text-end fw-bold text-danger" id="totalDueDisplay">₹0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admissions.bulk-import.pending.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn-success px-4" id="approveBtn">
            <i class="bi bi-check-circle me-1"></i> Approve &amp; Activate Student
        </button>
    </div>
</form>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admissions.bulk-import.pending.reject', $student->id) }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 p-2">
                            <i class="bi bi-x-circle text-danger fs-5"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0" id="rejectModalLabel">Reject This Student</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted small">
                        Use this for a row that should not have been imported — a duplicate, wrong data, etc.
                        The student is marked <strong>Cancelled</strong> and removed from this review list.
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
function fmt(n) {
    return '₹' + (isNaN(n) ? 0 : n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcTotal() {
    let sum = 0;
    document.querySelectorAll('.period-due-amount').forEach((el) => {
        sum += parseFloat(el.value) || 0;
    });
    const totalEl = document.getElementById('totalDueDisplay');
    if (totalEl) totalEl.textContent = fmt(sum);
}

document.querySelectorAll('.period-due-amount').forEach((input) => {
    input.addEventListener('input', recalcTotal);
});

document.getElementById('markAllNoDueBtn')?.addEventListener('click', function () {
    document.querySelectorAll('.period-due-amount').forEach((input) => {
        input.value = 0;
    });
    recalcTotal();
});

recalcTotal();

document.getElementById('approveForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Approving...';
});
</script>
@endpush
