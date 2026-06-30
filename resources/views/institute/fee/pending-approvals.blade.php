@extends('staff.layout')
@section('title', 'Pending Fee Approvals')
@section('breadcrumb', 'Fee / Pending Approvals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Fee Approvals</h4>
        <small class="text-muted">
            Custom fee collections above the staff member's limit — nothing is charged until approved.
            Total pending: <strong class="text-warning">₹{{ number_format($totalPendingAmount, 2) }}</strong>
        </small>
    </div>
    <a href="{{ route('staff.fee.create') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Collect Fee
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="rejectForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Fee Collection Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required
                                  placeholder="Why is this being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Submitted</th>
                    <th>Student</th>
                    <th>Items</th>
                    <th>Submitted By</th>
                    <th class="text-end text-danger">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $invoice)
                @php
                    $items = collect($invoice->pending_settlement_data['valid_items'] ?? []);
                    $amount = (float) ($invoice->pending_settlement_data['paid_amount'] ?? 0);
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $invoice->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $invoice->student?->name ?? '—' }}</div>
                        @if($invoice->student?->roll_no)
                            <small class="text-muted">{{ $invoice->student->roll_no }}</small>
                        @endif
                    </td>
                    <td>
                        @forelse($items as $item)
                            <div>
                                {{ $item['fee_name'] ?? 'Fee' }}
                                <span class="text-muted">— ₹{{ number_format((float) ($item['amount'] ?? 0), 2) }}</span>
                                @if(!empty($item['is_custom']))
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:10px;">Custom</span>
                                @endif
                            </div>
                        @empty
                            <span class="text-muted">—</span>
                        @endforelse
                    </td>
                    <td class="text-muted">{{ $invoice->collectedByStaff?->name ?? $invoice->collected_by ?? '—' }}</td>
                    <td class="text-end fw-bold text-danger">₹{{ number_format($amount, 2) }}</td>
                    <td class="text-center text-nowrap">
                        <form method="POST" action="{{ route('staff.fee.approvals.approve', $invoice) }}"
                              class="d-inline"
                              onsubmit="return confirm('Approve this ₹{{ number_format($amount, 2) }} collection? This will credit the wallet and income records.');">
                            @csrf
                            <button class="btn btn-sm btn-success" title="Approve">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button class="btn btn-sm btn-danger" title="Reject"
                                onclick="openReject({{ $invoice->id }})">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                        No pending fee approvals.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pending->hasPages())
    <div class="card-footer bg-white">{{ $pending->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function openReject(id) {
    const base = "{{ url('staff/fee/approvals') }}";
    document.getElementById('rejectForm').action = `${base}/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
