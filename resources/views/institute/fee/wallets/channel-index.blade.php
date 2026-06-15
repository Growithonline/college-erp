@extends('institute.layout')
@section('title', 'Channel Partner Fee Wallets')
@section('breadcrumb', 'Fee / Channel Wallets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Channel Partner Fee Wallets</h4>
        <small class="text-muted">Token-based fee collection control for channel partners</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('fee-wallets.centers') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-building me-1"></i> Center Wallets
        </a>
        <a href="{{ route('fee-wallets.extension-requests') }}" class="btn btn-outline-warning btn-sm position-relative">
            <i class="bi bi-inbox me-1"></i> Extension Requests
            @if($pendingCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;">{{ $pendingCount }}</span>
            @endif
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Channel Partner</th>
                    <th>Total Tokens</th>
                    <th>Used</th>
                    <th>Remaining</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partners as $i => $partner)
                    @php
                        $w = $partner->wallet;
                        $expired = $w && $w->isExpired();
                        $exhausted = $w && (float)$w->remaining_tokens <= 0;
                        $statusBadge = !$w ? 'secondary' : ($w->status === 'suspended' ? 'dark' : ($expired ? 'danger' : ($exhausted ? 'warning' : 'success')));
                        $statusText  = !$w ? 'No Wallet' : ($w->status === 'suspended' ? 'Suspended' : ($expired ? 'Expired' : ($exhausted ? 'Exhausted' : 'Active')));
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $partner->name }}</div>
                            <small class="text-muted">{{ $partner->mobile }}</small>
                        </td>
                        <td>{{ $w ? '₹' . number_format($w->total_tokens, 0) : '—' }}</td>
                        <td>{{ $w ? '₹' . number_format($w->used_tokens, 0) : '—' }}</td>
                        <td class="{{ $exhausted ? 'text-danger fw-semibold' : '' }}">
                            {{ $w ? '₹' . number_format($w->remaining_tokens, 0) : '—' }}
                        </td>
                        <td class="{{ $expired ? 'text-danger' : '' }}">
                            {{ $w?->expires_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td><span class="badge bg-{{ $statusBadge }}">{{ $statusText }}</span></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('fee-wallets.channel.create', $partner) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-{{ $w ? 'plus-circle' : 'wallet2' }}"></i>
                                    {{ $w ? 'Add Tokens' : 'Create Wallet' }}
                                </a>
                                @if($w)
                                    <a href="{{ route('fee-wallets.channel.transactions', $w) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-list-ul"></i> History
                                    </a>
                                    <button class="btn btn-sm btn-outline-info"
                                        data-bs-toggle="modal"
                                        data-bs-target="#extendModal{{ $w->id }}">
                                        <i class="bi bi-calendar-check"></i>
                                    </button>
                                    <form method="POST" action="{{ route('fee-wallets.channel.toggle', $w) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm {{ $w->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            onclick="return confirm('{{ $w->status === 'active' ? 'Suspend this wallet?' : 'Activate this wallet?' }}')">
                                            <i class="bi bi-{{ $w->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    @if($w)
                    <div class="modal fade" id="extendModal{{ $w->id }}" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header py-2">
                                    <h6 class="modal-title">Extend Expiry — {{ $partner->name }}</h6>
                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('fee-wallets.channel.extend', $w) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <label class="form-label small fw-semibold">New Expiry Date</label>
                                        <input type="date" name="expires_at" class="form-control form-control-sm"
                                            min="{{ now()->addDay()->toDateString() }}"
                                            value="{{ $w->expires_at?->toDateString() }}" required>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No channel partners found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
