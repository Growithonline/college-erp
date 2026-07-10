{{-- Shared by the Change Route and Cancel Transport modals on the allocation Show page.
     Expects (from parent scope): $oldBalance, $suggestedCredit, $semesterEnd, $semesterMonths,
     $allocStart, $today. Expects (passed explicitly): $heading, $inputId. --}}
@if($oldBalance > 0)
<div class="card border border-success-subtle rounded mb-3 p-3" style="font-size:13px;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-semibold text-success" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em;">
            <i class="bi bi-receipt me-1"></i>{{ $heading }}
        </span>
        <span class="text-muted" style="font-size:11px;">Max: ₹{{ number_format($oldBalance, 2) }}</span>
    </div>
    <div class="row g-2 align-items-end">
        <div class="col-8">
            <label class="form-label text-muted mb-1" style="font-size:11px;">Credit Amount (₹) — unused portion to write off</label>
            <input type="number" step="0.01" min="0" max="{{ $oldBalance }}"
                   name="credit_amount" id="{{ $inputId }}"
                   class="form-control form-control-sm"
                   value="{{ number_format($suggestedCredit, 2, '.', '') }}"
                   placeholder="0.00">
        </div>
        <div class="col-4">
            <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                    onclick="document.getElementById('{{ $inputId }}').value = '{{ number_format($oldBalance, 2, '.', '') }}'">
                Full Credit
            </button>
        </div>
    </div>
    @if($suggestedCredit > 0 && $semesterEnd)
    <div class="text-muted mt-2" style="font-size:11px;">
        <i class="bi bi-info-circle me-1"></i>
        Suggested ₹{{ number_format($suggestedCredit, 2) }} = prorated unused days, based on a
        {{ $semesterMonths }}-month semester
        ({{ $allocStart->format('d M') }} → {{ $today->format('d M') }} used,
         {{ $today->format('d M') }} → {{ $semesterEnd->format('d M Y') }} remaining).
        You can adjust this amount.
    </div>
    @endif
    <div class="text-muted mt-1" style="font-size:11px;">
        <i class="bi bi-lightbulb me-1 text-warning"></i>
        Enter <strong>0</strong> to carry the full outstanding balance forward on the student's ledger.
    </div>
</div>
@endif
