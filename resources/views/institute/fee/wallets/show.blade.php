@extends('institute.layout')
@section('title', 'Wallet Transactions')
@section('breadcrumb', 'Fee / ' . ($type === 'center' ? 'Center' : 'Channel') . ' Wallets / Transactions')

@section('content')
@php
    $entity = $type === 'center' ? $wallet->center : $wallet->channelPartner;
    $backRoute = $type === 'center' ? 'fee-wallets.centers' : 'fee-wallets.channels';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Transaction History</h4>
        <small class="text-muted">{{ $entity?->name }}</small>
    </div>
    <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Total Tokens</div>
                <div class="fw-bold text-primary">₹{{ number_format($wallet->total_tokens, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Used</div>
                <div class="fw-bold text-warning">₹{{ number_format($wallet->used_tokens, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Remaining</div>
                <div class="fw-bold {{ (float)$wallet->remaining_tokens <= 0 ? 'text-danger' : 'text-success' }}">
                    ₹{{ number_format($wallet->remaining_tokens, 0) }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="small text-muted">Expiry</div>
                <div class="fw-bold {{ $wallet->isExpired() ? 'text-danger' : '' }}">
                    {{ $wallet->expires_at?->format('d M Y') ?? '—' }}
                    @if($wallet->isExpired()) <span class="badge bg-danger">Expired</span> @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                    <th>Invoice</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $i => $tx)
                    <tr>
                        <td>{{ $transactions->firstItem() + $i }}</td>
                        <td>{{ $tx->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                {{ ucfirst($tx->type) }}
                            </span>
                        </td>
                        <td class="{{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }} fw-semibold">
                            {{ $tx->type === 'credit' ? '+' : '-' }}₹{{ number_format($tx->amount, 0) }}
                        </td>
                        <td>₹{{ number_format($tx->balance_after, 0) }}</td>
                        <td>
                            @if($tx->invoice)
                                <span class="text-muted">{{ $tx->invoice->invoice_no }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $tx->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-white py-2">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
