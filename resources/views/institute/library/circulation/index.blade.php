@extends($libraryLayout)
@section('title', 'Library Circulation')
@section('breadcrumb', 'Library / Issue Return')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Issue / Return / Renew</h4>
        <small class="text-muted">Live desk workflow yahin handle hoga.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.members.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-vcard me-1"></i>Members</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Issued Today</small><h4 class="mb-0">{{ $stats['issued_today'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Returned Today</small><h4 class="mb-0">{{ $stats['returned_today'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Overdue</small><h4 class="mb-0 text-warning">{{ $stats['overdue'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Pending Fine</small><h4 class="mb-0 text-danger">Rs {{ number_format($stats['fine_due'], 2) }}</h4></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Desk Search</span></div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Member Search</label>
                        <input type="text" name="member_search" value="{{ $memberSearch }}" class="form-control" placeholder="Code / name / mobile">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Book Copy Search</label>
                        <input type="text" name="copy_search" value="{{ $copySearch }}" class="form-control" placeholder="Accession / barcode / title">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search me-1"></i>Find</button>
                        <a href="{{ route($libraryRoutePrefix . '.circulation.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </form>

                <form method="POST" action="{{ route($libraryRoutePrefix . '.circulation.issue') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Member</label>
                        <select name="library_member_id" class="form-select" required>
                            <option value="">Select member</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->name }} ({{ $member->activeTransactions->count() }} active)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Book Copy</label>
                        <select name="library_book_copy_id" class="form-select" required>
                            <option value="">Select copy</option>
                            @foreach($copies as $copy)
                                <option value="{{ $copy->id }}">{{ $copy->accession_no }} - {{ $copy->book->title ?? '-' }} ({{ ucfirst($copy->status) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Issue Date</label>
                            <input type="date" name="issued_on" value="{{ now()->toDateString() }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-send-check me-1"></i>Issue Book</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Search Result Snapshot</span></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted fw-semibold mb-2">Members</div>
                    @forelse($members as $member)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $member->name }} <span class="badge bg-light text-dark border">{{ $member->member_code }}</span></div>
                            <small class="text-muted">{{ ucfirst($member->member_type) }} | Rule: {{ $member->ruleSet->name ?? 'No rule' }}</small>
                        </div>
                    @empty
                        <div class="text-muted small">Search karke matching members dekho.</div>
                    @endforelse
                </div>
                <div>
                    <div class="small text-muted fw-semibold mb-2">Book Copies</div>
                    @forelse($copies as $copy)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $copy->book->title ?? '-' }}</div>
                            <small class="text-muted">{{ $copy->accession_no }} | {{ $copy->rack->display_name ?? 'No rack' }} | {{ $copy->vendor->name ?? 'No vendor' }} | {{ ucfirst($copy->status) }}</small>
                        </div>
                    @empty
                        <div class="text-muted small">Search karke matching copies dekho.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom"><span class="fw-semibold">Active Issues</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Book Copy</th>
                    <th>Issue / Due</th>
                    <th>Renew</th>
                    <th>Return</th>
                </tr>
            </thead>
            <tbody>
            @forelse($activeTransactions as $transaction)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $transaction->member->name ?? '-' }}</div>
                        <small class="text-muted">{{ $transaction->member->member_code ?? '-' }}</small>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $transaction->copy->book->title ?? '-' }}</div>
                        <small class="text-muted">{{ $transaction->copy->accession_no ?? '-' }}</small>
                    </td>
                    <td>
                        <small class="d-block">Issue: {{ optional($transaction->issued_on)->format('d-m-Y') }}</small>
                        <small class="d-block {{ $transaction->is_overdue ? 'text-danger' : 'text-muted' }}">Due: {{ optional($transaction->due_on)->format('d-m-Y') }}</small>
                    </td>
                    <td>
                        <form method="POST" action="{{ route($libraryRoutePrefix . '.circulation.renew', $transaction) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm">Renew</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="{{ route($libraryRoutePrefix . '.circulation.return', $transaction) }}" class="d-flex gap-2">
                            @csrf
                            <input type="date" name="returned_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm" style="min-width:140px;">
                            <select name="return_mode" class="form-select form-select-sm" style="min-width:130px;">
                                <option value="returned">Returned</option>
                                <option value="damaged">Damaged</option>
                                <option value="lost">Lost</option>
                            </select>
                            <input type="number" step="0.01" name="penalty_amount" class="form-control form-control-sm" placeholder="Penalty" style="min-width:110px;">
                            <button type="submit" class="btn btn-success btn-sm">Return</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5">Koi active issue nahi hai.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($activeTransactions->hasPages())
        <div class="card-footer bg-white">{{ $activeTransactions->links() }}</div>
    @endif
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom"><span class="fw-semibold">Pending Fine Collection</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Fine</th>
                    <th>Pay</th>
                </tr>
            </thead>
            <tbody>
            @forelse($fineTransactions as $transaction)
                @php $pendingFine = max(0, (float) $transaction->fine_amount - (float) $transaction->fine_paid); @endphp
                <tr>
                    <td>{{ $transaction->member->name ?? '-' }}</td>
                    <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                    <td>
                        <div>Total: Rs {{ number_format((float) $transaction->fine_amount, 2) }}</div>
                        <small class="text-danger">Pending: Rs {{ number_format($pendingFine, 2) }}</small>
                    </td>
                    <td>
                        <a href="{{ route($libraryRoutePrefix . '.fines.show', $transaction->member) }}"
                           class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-cash-coin me-1"></i>Pay Fine
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Abhi koi pending fine nahi hai.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
