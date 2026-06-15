@extends('institute.layout')
@section('title', 'Pending Expense Approvals')
@section('breadcrumb', 'Finance / Expenses / Pending Approvals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Expense Approvals</h4>
        <small class="text-muted">
            Total pending: <strong class="text-warning">Rs {{ number_format($totalPendingAmount, 2) }}</strong>
        </small>
    </div>
    <a href="{{ route('finance.expenses.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Expense Book
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
                    <h5 class="modal-title">Expense Reject Karo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required
                                  placeholder="Kyu reject kar rahe ho..."></textarea>
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
                    <th>Date</th>
                    <th>Category</th>
                    <th>GL Head</th>
                    <th>Description</th>
                    <th>Mode</th>
                    <th class="text-end text-danger">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pending as $expense)
                <tr>
                    <td class="text-nowrap">{{ $expense->expense_date->format('d M Y') }}</td>
                    <td>
                        @if($expense->categoryL1)
                            <div class="fw-semibold">{{ $expense->categoryL1->name }}</div>
                            @if($expense->categoryL2)
                                <small class="text-muted">→ {{ $expense->categoryL2->name }}</small>
                            @endif
                            @if($expense->vendor)
                                <br><small class="text-info">{{ $expense->vendor->name }}</small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $expense->expenseAccount?->name ?? '—' }}</td>
                    <td>
                        {{ Str::limit($expense->description, 60) }}
                        @if($expense->vendor_name)
                            <br><small class="text-muted">{{ $expense->vendor_name }}</small>
                        @endif
                    </td>
                    <td><span class="badge bg-secondary">{{ strtoupper($expense->payment_mode) }}</span></td>
                    <td class="text-end fw-bold text-danger">Rs {{ number_format($expense->amount, 2) }}</td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('finance.wallet.expense-approvals.approve', $expense) }}"
                              class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-success" title="Approve">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                        </form>
                        <button class="btn btn-sm btn-danger" title="Reject"
                                onclick="openReject({{ $expense->id }})">
                            <i class="bi bi-x-lg"></i> Reject
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                        Koi pending expense nahi hai.
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
    const base = "{{ url('finance/wallet/expense-approvals') }}";
    document.getElementById('rejectForm').action = `${base}/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
