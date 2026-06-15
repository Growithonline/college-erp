@extends($libraryLayout)
@section('title', 'Library Reports')
@section('breadcrumb', 'Library / Reports')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Library Reports</h4>
        <small class="text-muted">Overdue, fine collection, inventory status aur movement history ek jagah.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                <a href="{{ route($libraryRoutePrefix . '.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Available</small><h4 class="mb-0 text-success">{{ $inventory['available'] }}</h4></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Issued</small><h4 class="mb-0 text-primary">{{ $inventory['issued'] }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Lost</small><h4 class="mb-0 text-danger">{{ $inventory['lost'] }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Damaged</small><h4 class="mb-0 text-warning">{{ $inventory['damaged'] }}</h4></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small class="text-muted">Fine Collected</small><h4 class="mb-0">Rs {{ number_format($totalFineCollected, 2) }}</h4></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Overdue Books</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Member</th><th>Book</th><th>Due Date</th></tr>
                    </thead>
                    <tbody>
                    @forelse($overdues as $transaction)
                        <tr>
                            <td>{{ $transaction->member->name ?? '-' }}</td>
                            <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                            <td class="text-danger">{{ optional($transaction->due_on)->format('d-m-Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Koi overdue issue nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Fine Collection</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Member</th><th>Book</th><th>Paid</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    @forelse($fineCollections as $payment)
                        <tr>
                            <td>{{ $payment->member->name ?? '-' }}</td>
                            <td>{{ $payment->transaction->copy->book->title ?? '-' }}</td>
                            <td>Rs {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ optional($payment->payment_date)->format('d-m-Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Is range me koi fine collection nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Issued Today</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Member</th><th>Book</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($issuedToday as $transaction)
                        <tr>
                            <td>{{ $transaction->member->name ?? '-' }}</td>
                            <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                            <td>{{ optional($transaction->issued_on)->format('d-m-Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Aaj koi issue nahi hua.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Returned Today</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Member</th><th>Book</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($returnedToday as $transaction)
                        <tr>
                            <td>{{ $transaction->member->name ?? '-' }}</td>
                            <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                            <td>{{ ucfirst($transaction->current_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Aaj koi return nahi hua.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom"><span class="fw-semibold">Issue History</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Member</th><th>Book</th><th>Status</th><th>Issue</th><th>Return</th><th>Fine</th></tr>
            </thead>
            <tbody>
            @forelse($memberHistory as $transaction)
                <tr>
                    <td>{{ $transaction->member->name ?? '-' }}</td>
                    <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                    <td>{{ ucfirst($transaction->current_status) }}</td>
                    <td>{{ optional($transaction->issued_on)->format('d-m-Y') }}</td>
                    <td>{{ optional($transaction->returned_on)->format('d-m-Y') ?: '-' }}</td>
                    <td>Rs {{ number_format((float) $transaction->fine_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Is range me koi transaction nahi hai.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Lost / Damaged</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Member</th><th>Book</th><th>Status</th><th>Fine</th></tr></thead>
                    <tbody>
                    @forelse($lostDamaged as $transaction)
                        <tr>
                            <td>{{ $transaction->member->name ?? '-' }}</td>
                            <td>{{ $transaction->copy->book->title ?? '-' }}</td>
                            <td>{{ ucfirst($transaction->current_status) }}</td>
                            <td>Rs {{ number_format((float) $transaction->fine_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Koi lost/damaged record nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Course-wise Usage</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Course</th><th>Issues</th></tr></thead>
                    <tbody>
                    @forelse($courseUsage as $row)
                        <tr>
                            <td>{{ $row['course'] }}</td>
                            <td>{{ $row['issues'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-4">No usage in selected range.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom"><span class="fw-semibold">Stock Verification Snapshot</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Accession</th><th>Book</th><th>Rack</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse($stockVerification as $copy)
                <tr>
                    <td>{{ $copy->accession_no }}</td>
                    <td>{{ $copy->book->title ?? '-' }}</td>
                    <td>{{ $copy->rack->display_name ?? '-' }}</td>
                    <td>{{ ucfirst($copy->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No stock data available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
