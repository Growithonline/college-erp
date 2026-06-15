@extends($layout ?? 'institute.layout')

@section('title', 'Payroll Summary')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Payroll Summary Report</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" class="d-flex gap-2">
                <select name="year" class="form-control" style="max-width: 120px;">
                    @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                    @endfor
                </select>
                <select name="month" class="form-control" style="max-width: 120px;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($month == $m)>{{ sprintf('%02d', $m) }}</option>
                    @endfor
                </select>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Staff</h6>
                    <h3 class="text-primary">{{ $summary['total_records'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Draft</h6>
                    <h3 class="text-secondary">{{ $summary['draft_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Approved</h6>
                    <h3 class="text-info">{{ $summary['approved_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="card-title text-muted">Paid</h6>
                    <h3 class="text-success">{{ $summary['paid_count'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Net Payable</h6>
                    <h2 class="text-success mb-0">Rs {{ number_format($summary['total_net_payable'], 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Financial Breakdown</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total Basic Salary</span>
                        <strong>Rs {{ number_format($summary['total_basic'], 2) }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total Allowances</span>
                        <strong class="text-success">+ Rs {{ number_format($summary['total_allowances'], 2) }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Total Deductions</span>
                        <strong class="text-danger">- Rs {{ number_format($summary['total_deductions'], 2) }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between bg-light">
                        <span><strong>Net Payable</strong></span>
                        <strong class="text-success">Rs {{ number_format($summary['total_net_payable'], 2) }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Amount Paid</span>
                        <strong class="text-primary">Rs {{ number_format($summary['total_paid'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Status Summary</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between">
                        <span><span class="badge bg-secondary me-2"></span> Draft</span>
                        <strong>{{ $summary['draft_count'] }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><span class="badge bg-info me-2"></span> Approved</span>
                        <strong>{{ $summary['approved_count'] }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><span class="badge bg-success me-2"></span> Paid</span>
                        <strong>{{ $summary['paid_count'] }}</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><span class="badge bg-danger me-2"></span> Reversed</span>
                        <strong>{{ $summary['reversed_count'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Payroll Details - {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 20%">Staff Name / Category</th>
                        <th style="width: 15%" class="text-end">Basic</th>
                        <th style="width: 12%" class="text-end">Allowances</th>
                        <th style="width: 12%" class="text-end">Deductions</th>
                        <th style="width: 15%" class="text-end">Net</th>
                        <th style="width: 12%" class="text-center">Status</th>
                        <th style="width: 14%">Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['records'] as $record)
                        <tr>
                            <td>
                                <strong>{{ $record->staffMember?->name ?? '—' }}</strong><br>
                                <small class="text-muted">{{ $record->staffMember?->staff_category ?? '—' }}</small>
                            </td>
                            <td class="text-end">Rs {{ number_format($record->basic_salary, 2) }}</td>
                            <td class="text-end">Rs {{ number_format($record->allowances, 2) }}</td>
                            <td class="text-end">Rs {{ number_format($record->deductions, 2) }}</td>
                            <td class="text-end fw-semibold">Rs {{ number_format($record->net_payable, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $record->status === 'draft' ? 'secondary' : ($record->status === 'approved' ? 'info' : ($record->status === 'paid' ? 'success' : 'danger')) }}">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>
                            <td>{{ $record->payment_date?->format('d-m-Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No payroll records found for this period
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-end">Rs {{ number_format($summary['total_basic'], 2) }}</td>
                        <td class="text-end">Rs {{ number_format($summary['total_allowances'], 2) }}</td>
                        <td class="text-end">Rs {{ number_format($summary['total_deductions'], 2) }}</td>
                        <td class="text-end">Rs {{ number_format($summary['total_net_payable'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, form { display: none; }
        .card { border: 1px solid #ddd; }
    }
</style>
@endsection
