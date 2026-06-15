@extends('institute.layout')
@section('title', 'Manual Income')
@section('breadcrumb', 'Finance / Wallet / Manual Income')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-info"></i>Manual Income Entries</h4>
        <small class="text-muted">Admin ke manually add kiye gaye income entries</small>
    </div>
    <a href="{{ route('finance.wallet.manual-income.create') }}" class="btn btn-success btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Income
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="GET" class="mb-3">
    <div class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-semibold small">Session:</label>
        <select name="session_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <option value="">-- All Sessions --</option>
            @foreach($sessions as $s)
                <option value="{{ $s->id }}" {{ $sessionId == $s->id ? 'selected' : '' }}>
                    {{ $s->name }} {{ $s->is_active ? '(Active)' : '' }}
                </option>
            @endforeach
        </select>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Receipt No</th>
                    <th class="text-end text-success">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incomes as $income)
                <tr>
                    <td class="text-nowrap">{{ $income->date->format('d M Y') }}</td>
                    <td><span class="badge bg-info bg-opacity-10 text-info">{{ $income->category->name }}</span></td>
                    <td class="text-muted">{{ $income->description ?? '-' }}</td>
                    <td class="text-muted">{{ $income->receipt_no ?? '-' }}</td>
                    <td class="text-end fw-semibold text-success">Rs {{ number_format($income->amount, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Koi manual income nahi hai. <a href="{{ route('finance.wallet.manual-income.create') }}">Abhi add karo.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($incomes->hasPages())
    <div class="card-footer bg-white">{{ $incomes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
