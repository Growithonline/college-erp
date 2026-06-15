@extends('institute.layout')
@section('title', 'Cheque Tracking')
@section('breadcrumb', 'Finance / Wallet / Cheque Tracking')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-card-checklist me-2 text-warning"></i>Cheque Tracking</h4>
        <small class="text-muted">Cheque aur DD payments ka status manage karo</small>
    </div>
    <a href="{{ route('finance.wallet.ledger') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Ledger
    </a>
</div>

{{-- Status Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3 {{ $status === 'pending' ? 'border-warning border-2' : '' }}" style="border-left: 4px solid #ffc107 !important">
                <div class="text-muted small">Pending</div>
                <div class="fw-bold fs-4 text-warning">{{ $counts['pending'] }}</div>
                <div class="text-muted" style="font-size:11px">Awaiting clearance</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ request()->fullUrlWithQuery(['status' => 'cleared']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3" style="border-left: 4px solid #198754 !important">
                <div class="text-muted small">Cleared</div>
                <div class="fw-bold fs-4 text-success">{{ $counts['cleared'] }}</div>
                <div class="text-muted" style="font-size:11px">Successfully cleared</div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ request()->fullUrlWithQuery(['status' => 'bounced']) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-3" style="border-left: 4px solid #dc3545 !important">
                <div class="text-muted small">Bounced</div>
                <div class="fw-bold fs-4 text-danger">{{ $counts['bounced'] }}</div>
                <div class="text-muted" style="font-size:11px">Cheques bounced</div>
            </div>
        </a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="all"     {{ $status === 'all'     ? 'selected' : '' }}>All Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cleared" {{ $status === 'cleared' ? 'selected' : '' }}>Cleared</option>
                    <option value="bounced" {{ $status === 'bounced' ? 'selected' : '' }}>Bounced</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Cheque Date From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Cheque no / Bank / Student" value="{{ $search }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
            </div>
        </div>
    </div>
</form>

{{-- Cheque List --}}
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Cheque No.</th>
                    <th>Student</th>
                    <th>Invoice</th>
                    <th>Drawee Bank</th>
                    <th>Cheque Date</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Status</th>
                    <th>Clearance / Bounce Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cheques as $ch)
                <tr class="{{ $ch->isBounced() ? 'table-danger' : ($ch->isCleared() ? '' : '') }}">
                    <td class="text-muted">{{ ($cheques->currentPage()-1)*$cheques->perPage() + $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $ch->cheque_no }}</td>
                    <td>{{ $ch->invoice?->student?->name ?? '-' }}</td>
                    <td class="text-muted small">{{ $ch->invoice?->invoice_no ?? '-' }}</td>
                    <td>{{ $ch->drawee_bank ?? '-' }}</td>
                    <td class="text-nowrap">{{ $ch->cheque_date?->format('d-m-Y') ?? '-' }}</td>
                    <td class="text-end fw-semibold">₹{{ number_format($ch->amount, 2) }}</td>
                    <td class="text-center">
                        @if($ch->isPending())
                            <span class="badge bg-warning text-dark">Pending</span>
                        @elseif($ch->isCleared())
                            <span class="badge bg-success">Cleared</span>
                        @else
                            <span class="badge bg-danger">Bounced</span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        @if($ch->isCleared())
                            {{ $ch->clearance_date?->format('d-m-Y') ?? '-' }}
                        @elseif($ch->isBounced())
                            <span class="text-danger">{{ $ch->bounce_reason }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($ch->isPending())
                        <button class="btn btn-sm btn-outline-success py-0 px-2"
                                data-bs-toggle="modal" data-bs-target="#statusModal{{ $ch->id }}"
                                data-action="cleared">
                            <i class="bi bi-check2-circle me-1"></i>Clear
                        </button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                                data-bs-toggle="modal" data-bs-target="#statusModal{{ $ch->id }}"
                                data-action="bounced">
                            <i class="bi bi-x-circle me-1"></i>Bounce
                        </button>

                        {{-- Status Update Modal --}}
                        <div class="modal fade" id="statusModal{{ $ch->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST"
                                      action="{{ route('finance.wallet.cheques.update-status', $ch) }}">
                                    @csrf @method('PATCH')
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Cheque Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="small text-muted mb-3">
                                                Cheque: <strong>{{ $ch->cheque_no }}</strong> |
                                                Amount: <strong>₹{{ number_format($ch->amount, 2) }}</strong>
                                            </p>
                                            <input type="hidden" name="status" id="statusInput{{ $ch->id }}">

                                            <div id="clearedFields{{ $ch->id }}">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Clearance Date</label>
                                                    <input type="date" name="clearance_date"
                                                           class="form-control form-control-sm"
                                                           value="{{ now()->toDateString() }}">
                                                </div>
                                            </div>
                                            <div id="bouncedFields{{ $ch->id }}" style="display:none">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Bounce Reason</label>
                                                    <input type="text" name="bounce_reason"
                                                           class="form-control form-control-sm"
                                                           placeholder="e.g. Insufficient funds">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Remarks (Optional)</label>
                                                <input type="text" name="remarks"
                                                       class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-sm"
                                                    id="submitBtn{{ $ch->id }}">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <script>
                        document.getElementById('statusModal{{ $ch->id }}').addEventListener('show.bs.modal', function(e) {
                            const action = e.relatedTarget.getAttribute('data-action');
                            document.getElementById('statusInput{{ $ch->id }}').value = action;
                            document.getElementById('clearedFields{{ $ch->id }}').style.display = action === 'cleared' ? '' : 'none';
                            document.getElementById('bouncedFields{{ $ch->id }}').style.display = action === 'bounced' ? '' : 'none';
                            document.getElementById('submitBtn{{ $ch->id }}').textContent = action === 'cleared' ? 'Mark Cleared' : 'Mark Bounced';
                        });
                        </script>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Koi cheque nahi mila.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cheques->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <small class="text-muted">Showing {{ $cheques->firstItem() }}–{{ $cheques->lastItem() }} of {{ $cheques->total() }}</small>
        {{ $cheques->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
