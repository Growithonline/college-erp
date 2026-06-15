@extends('institute.layout')
@section('title', 'Wallet Extension Requests')
@section('breadcrumb', 'Fee / Wallet Extension Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Extension Requests</h4>
        <small class="text-muted">Requests from centers and channel partners</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('fee-wallets.centers') }}" class="btn btn-outline-secondary btn-sm">Center Wallets</a>
        <a href="{{ route('fee-wallets.channels') }}" class="btn btn-outline-secondary btn-sm">Channel Wallets</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
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
                    <th>From</th>
                    <th>Type</th>
                    <th>Request</th>
                    <th>Reason</th>
                    <th>Requested On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $i => $req)
                    <tr class="{{ $req->status === 'pending' ? 'table-warning' : '' }}">
                        <td>{{ $requests->firstItem() + $i }}</td>
                        <td>
                            <span class="badge bg-{{ $req->entity_type === 'center' ? 'primary' : 'info' }} me-1">
                                {{ ucfirst($req->entity_type) }}
                            </span>
                            {{ $req->entity_name }}
                        </td>
                        <td>
                            @if($req->request_type === 'expiry_extension')
                                <span class="badge bg-secondary">Expiry Extension</span>
                                @if($req->requested_days) <small class="text-muted">{{ $req->requested_days }} days</small> @endif
                            @else
                                <span class="badge bg-secondary">Token Top-up</span>
                                @if($req->requested_amount) <small class="text-muted">₹{{ number_format($req->requested_amount, 0) }}</small> @endif
                            @endif
                        </td>
                        <td>
                            @if($req->request_type === 'expiry_extension')
                                +{{ $req->requested_days ?? '?' }} days
                            @else
                                ₹{{ number_format($req->requested_amount ?? 0, 0) }}
                            @endif
                        </td>
                        <td style="max-width:200px;">{{ Str::limit($req->reason, 80) }}</td>
                        <td>{{ $req->created_at->format('d M Y H:i') }}</td>
                        <td>
                            @if($req->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($req->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                                @if($req->admin_note) <br><small class="text-muted">{{ $req->admin_note }}</small> @endif
                            @else
                                <span class="badge bg-danger">Rejected</span>
                                @if($req->admin_note) <br><small class="text-muted">{{ $req->admin_note }}</small> @endif
                            @endif
                        </td>
                        <td>
                            @if($req->status === 'pending')
                                <button class="btn btn-sm btn-success mb-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#approveModal{{ $req->id }}">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal{{ $req->id }}">
                                    <i class="bi bi-x-lg"></i> Reject
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Approve Modal --}}
                    @if($req->status === 'pending')
                    <div class="modal fade" id="approveModal{{ $req->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header py-2">
                                    <h6 class="modal-title">Approve Request — {{ $req->entity_name }}</h6>
                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('fee-wallets.extension-requests.approve', $req) }}">
                                    @csrf
                                    <div class="modal-body">
                                        @if($req->request_type === 'expiry_extension')
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">New Expiry Date</label>
                                                <input type="date" name="new_expires_at" class="form-control form-control-sm"
                                                    min="{{ now()->addDay()->toDateString() }}"
                                                    value="{{ $req->requested_days ? now()->addDays($req->requested_days)->toDateString() : now()->addMonth()->toDateString() }}"
                                                    required>
                                            </div>
                                        @else
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Approved Token Amount (₹)</label>
                                                <input type="number" name="approved_amount" class="form-control form-control-sm"
                                                    value="{{ $req->requested_amount }}" min="1" required>
                                            </div>
                                        @endif
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Admin Note (optional)</label>
                                            <textarea name="admin_note" class="form-control form-control-sm" rows="2"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button class="btn btn-success btn-sm">Approve & Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Reject Modal --}}
                    <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1">
                        <div class="modal-dialog modal-sm">
                            <div class="modal-content">
                                <div class="modal-header py-2">
                                    <h6 class="modal-title">Reject Request</h6>
                                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('fee-wallets.extension-requests.reject', $req) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <label class="form-label small fw-semibold">Reason (optional)</label>
                                        <textarea name="admin_note" class="form-control form-control-sm" rows="2"></textarea>
                                    </div>
                                    <div class="modal-footer py-2">
                                        <button class="btn btn-danger btn-sm">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No extension requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white py-2">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
