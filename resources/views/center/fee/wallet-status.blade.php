@extends('center.layout')
@section('title', 'My Wallet Status')
@section('breadcrumb', 'Fee / Wallet Status')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">My Fee Wallet</h4>
        <small class="text-muted">Token balance & extension requests</small>
    </div>
    @if($wallet && !$wallet->getBlockStatus()['blocked'])
    <a href="{{ route('center.fee.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Collect Fee
    </a>
    @endif
</div>

@if(session('extension_request_sent'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-1"></i> {{ session('extension_request_sent') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Wallet Status Card --}}
@if($wallet)
    @php
        $status = $wallet->getBlockStatus();
        $expired   = $wallet->isExpired();
        $exhausted = (float)$wallet->remaining_tokens <= 0;
        $cardColor = $status['blocked'] ? ($expired ? '#fee2e2' : '#fef9c3') : '#f0fdf4';
        $borderColor = $status['blocked'] ? ($expired ? '#ef4444' : '#f59e0b') : '#22c55e';
    @endphp
    <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid {{ $borderColor }}!important; background:{{ $cardColor }};">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted mb-1">Total Allocated</div>
                    <div class="fw-bold fs-5 text-primary">₹{{ number_format($wallet->total_tokens, 0) }}</div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted mb-1">Used</div>
                    <div class="fw-bold fs-5 text-warning">₹{{ number_format($wallet->used_tokens, 0) }}</div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted mb-1">Remaining</div>
                    <div class="fw-bold fs-5 {{ $exhausted ? 'text-danger' : 'text-success' }}">
                        ₹{{ number_format($wallet->remaining_tokens, 0) }}
                    </div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="small text-muted mb-1">Valid Until</div>
                    <div class="fw-bold {{ $expired ? 'text-danger' : 'text-dark' }}">
                        {{ $wallet->expires_at?->format('d M Y') ?? '—' }}
                        @if($expired) <span class="badge bg-danger">Expired</span> @endif
                    </div>
                </div>
            </div>

            @if($status['blocked'])
            <hr class="my-3">
            <div class="alert alert-{{ $expired ? 'danger' : 'warning' }} mb-0 py-2 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>{{ $status['reason'] }}</strong>
            </div>
            @endif
        </div>
    </div>

    {{-- Request Extension Form --}}
    @if($status['blocked'])
    <div class="card border-0 shadow-sm mb-4" style="max-width:520px;">
        <div class="card-header bg-white fw-semibold py-2 small">
            <i class="bi bi-send me-1 text-primary"></i>
            Request Admin to {{ $expired || $wallet->status === 'suspended' ? 'Reopen Portal' : 'Add More Tokens' }}
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('center.fee.wallet.request-extension') }}">
                @csrf
                <input type="hidden" name="request_type"
                    value="{{ ($expired || $wallet->status === 'suspended') ? 'expiry_extension' : 'token_topup' }}">

                @if($expired || $wallet->status === 'suspended')
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Request Days</label>
                    <input type="number" name="requested_days" class="form-control form-control-sm"
                        placeholder="e.g. 30" min="1" max="365">
                    <div class="form-text">Approximate number of extra days you need.</div>
                </div>
                @else
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Request Token Amount (₹)</label>
                    <input type="number" name="requested_amount" class="form-control form-control-sm"
                        placeholder="e.g. 50000" min="1">
                </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Reason / Message to Admin</label>
                    <textarea name="reason" class="form-control form-control-sm" rows="3"
                        placeholder="Briefly explain your request..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-send me-1"></i> Send Request
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Transaction History --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small"><i class="bi bi-list-ul me-1"></i>Token Transaction History</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
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
                            <td class="text-muted">{{ $tx->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-white py-2">{{ $transactions->links() }}</div>
        @endif
    </div>

@else
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-wallet2 fs-1 mb-3 d-block opacity-50"></i>
            <div>No wallet has been assigned to your account yet.</div>
            <div class="small mt-1">Please contact admin to set up fee collection tokens.</div>
        </div>
    </div>
@endif

{{-- Extension Request History --}}
@if($extensionRequests->count() > 0)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <span class="fw-semibold small"><i class="bi bi-inbox me-1"></i>My Extension Requests</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Request</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Admin Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($extensionRequests as $req)
                    <tr>
                        <td>{{ $req->created_at->format('d M Y') }}</td>
                        <td>
                            @if($req->request_type === 'expiry_extension')
                                <span class="badge bg-secondary">Expiry Extension</span>
                            @else
                                <span class="badge bg-secondary">Token Top-up</span>
                            @endif
                        </td>
                        <td>
                            @if($req->request_type === 'expiry_extension')
                                {{ $req->requested_days ? '+' . $req->requested_days . ' days' : '—' }}
                            @else
                                {{ $req->requested_amount ? '₹' . number_format($req->requested_amount, 0) : '—' }}
                            @endif
                        </td>
                        <td style="max-width:160px;">{{ Str::limit($req->reason, 60) }}</td>
                        <td>
                            @if($req->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($req->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $req->admin_note ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
