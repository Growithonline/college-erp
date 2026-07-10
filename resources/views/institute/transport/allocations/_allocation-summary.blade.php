{{-- Shared by the Change Route and Cancel Transport modals on the allocation Show page.
     Expects: $allocation, $oldCharged, $oldPaid, $oldBalance --}}
<div class="card border-0 bg-light rounded mb-3 p-3" style="font-size:13px;">
    <div class="fw-semibold text-muted mb-2" style="font-size:11px; letter-spacing:.05em; text-transform:uppercase;">Current Allocation</div>
    <div class="row g-2">
        <div class="col-4">
            <div class="text-muted">Route</div>
            <div class="fw-semibold">{{ $allocation->route?->name ?? '—' }}</div>
        </div>
        <div class="col-4">
            <div class="text-muted">Fee Charged</div>
            <div class="fw-semibold">₹{{ number_format($oldCharged, 2) }}</div>
        </div>
        <div class="col-4">
            <div class="text-muted">Paid / Outstanding</div>
            <div>
                <span class="text-success fw-semibold">₹{{ number_format($oldPaid, 2) }}</span>
                <span class="text-muted mx-1">/</span>
                <span class="text-danger fw-semibold">₹{{ number_format($oldBalance, 2) }}</span>
            </div>
        </div>
    </div>
</div>
