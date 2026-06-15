@extends($libraryLayout)
@section('title', 'Library Dashboard')
@section('breadcrumb', 'Library / Dashboard')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Library Dashboard</h4>
        <small class="text-muted">Books, members, issue-return aur overdue ka quick control center.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($libraryRoutePrefix . '.circulation.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left-right me-1"></i>Issue / Return</a>
        <a href="{{ route($libraryRoutePrefix . '.books.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Book</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Book Titles</small><h3 class="mb-0">{{ $stats['titles'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Total Copies</small><h3 class="mb-0">{{ $stats['copies'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Available Copies</small><h3 class="mb-0 text-success">{{ $stats['available'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Issued Copies</small><h3 class="mb-0 text-primary">{{ $stats['issued'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Library Members</small><h3 class="mb-0">{{ $stats['members'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Blocked Members</small><h3 class="mb-0 text-danger">{{ $stats['blocked_members'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Overdue Issues</small><h3 class="mb-0 text-warning">{{ $stats['overdue'] }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Pending Fine</small><h3 class="mb-0 text-danger">Rs {{ number_format($stats['fine_due'], 2) }}</h3></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Transactions</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Status</th>
                            <th>Dates</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $transaction->member->name ?? '-' }}</div>
                                <small class="text-muted">{{ $transaction->member->member_code ?? '-' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $transaction->copy->book->title ?? '-' }}</div>
                                <small class="text-muted">{{ $transaction->copy->accession_no ?? '-' }}</small>
                            </td>
                            <td><span class="badge {{ $transaction->current_status === 'issued' ? 'bg-primary' : 'bg-success' }}">{{ ucfirst($transaction->current_status) }}</span></td>
                            <td>
                                <small class="d-block">Issue: {{ optional($transaction->issued_on)->format('d-m-Y') }}</small>
                                <small class="d-block text-muted">Due: {{ optional($transaction->due_on)->format('d-m-Y') }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Abhi koi transaction nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Overdue Watchlist</span></div>
            <div class="list-group list-group-flush">
                @forelse($overdueTransactions as $transaction)
                    <div class="list-group-item">
                        <div class="fw-semibold">{{ $transaction->member->name ?? '-' }}</div>
                        <div class="small">{{ $transaction->copy->book->title ?? '-' }}</div>
                        <small class="text-danger">Due {{ optional($transaction->due_on)->format('d-m-Y') }}</small>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Koi overdue issue nahi hai.</div>
                @endforelse
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold"><i class="bi bi-pie-chart me-2 text-success"></i>Top Categories</span></div>
            <div class="list-group list-group-flush">
                @forelse($categoryStats as $category)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ $category->name }}</span>
                        <span class="badge bg-secondary-subtle text-secondary border">{{ $category->books_count }}</span>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Abhi categories configured nahi hain.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
