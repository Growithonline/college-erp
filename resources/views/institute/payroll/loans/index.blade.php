@extends($layout ?? 'institute.layout')

@section('title', 'Staff Loans & Advances')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Staff Loans & Advances</h1>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLoanModal">
                <i class="fas fa-plus me-1"></i> Add Loan/Advance
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-3">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="active"    @selected($status === 'active')>Active</option>
                <option value="completed" @selected($status === 'completed')>Completed</option>
                <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
                <option value="all"       @selected($status === 'all')>All</option>
            </select>
        </div>
        <div class="col-md-4">
            <select name="staff_id" class="form-control" onchange="this.form.submit()">
                <option value="">All Staff</option>
                @foreach($staffList as $s)
                    <option value="{{ $s->id }}" @selected((int)$staffId === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff</th>
                        <th>Type</th>
                        <th class="text-end">Principal</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end">Monthly EMI</th>
                        <th>Start</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td>{{ $loan->staffMember?->name ?? '—' }}</td>
                            <td><span class="badge {{ $loan->loan_type === 'loan' ? 'bg-primary' : 'bg-info text-dark' }}">{{ ucfirst($loan->loan_type) }}</span></td>
                            <td class="text-end">₹{{ number_format($loan->principal_amount, 2) }}</td>
                            <td class="text-end {{ $loan->outstanding_amount > 0 ? 'text-danger' : 'text-success' }}">
                                ₹{{ number_format($loan->outstanding_amount, 2) }}
                            </td>
                            <td class="text-end">₹{{ number_format($loan->monthly_deduction, 2) }}</td>
                            <td>{{ str_pad($loan->start_month, 2, '0', STR_PAD_LEFT) }}/{{ $loan->start_year }}</td>
                            <td><small class="text-muted">{{ $loan->purpose ?? '—' }}</small></td>
                            <td>
                                <span class="badge {{ match($loan->status) {
                                    'active'    => 'bg-success',
                                    'completed' => 'bg-secondary',
                                    'cancelled' => 'bg-danger',
                                    default     => 'bg-secondary'
                                } }}">{{ ucfirst($loan->status) }}</span>
                            </td>
                            <td>
                                @if($loan->status === 'active')
                                    <form method="POST" action="{{ route('finance.payroll.loans.cancel', $loan) }}"
                                          onsubmit="return confirm('Yeh loan cancel karein?')">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Koi loan/advance record nahi mila.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Loan Modal --}}
<div class="modal fade" id="addLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('finance.payroll.loans.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Loan / Advance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Staff Member <span class="text-danger">*</span></label>
                    <select name="staff_member_id" class="form-control" required>
                        <option value="">Select Staff</option>
                        @foreach($staffList as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->staff_category }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="loan_type" class="form-control" required>
                        <option value="advance">Salary Advance</option>
                        <option value="loan">Loan</option>
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Principal Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="principal_amount" class="form-control" min="1" step="0.01" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Monthly EMI (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="monthly_deduction" class="form-control" min="1" step="0.01" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Start Month <span class="text-danger">*</span></label>
                        <select name="start_month" class="form-control" required>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $m)
                                <option value="{{ $i + 1 }}" @selected(now()->month == $i + 1)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Start Year <span class="text-danger">*</span></label>
                        <input type="number" name="start_year" class="form-control" value="{{ now()->year }}" min="2020" max="2100" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purpose</label>
                    <input type="text" name="purpose" class="form-control" maxlength="255" placeholder="e.g., Medical emergency">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
