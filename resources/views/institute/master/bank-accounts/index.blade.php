@extends('institute.layout')
@section('title', 'Bank Accounts')
@section('breadcrumb', 'Master / Bank Accounts')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-bank me-2 text-primary"></i>Bank Accounts</h4>
        <small class="text-muted">Manage bank accounts for fee collection</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.bank-accounts.permissions') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-shield-check me-1"></i> Payment Permissions
        </a>
        <a href="{{ route('master.bank-accounts.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Bank Account
        </a>
    </div>
</div>

@if($accounts->isEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-bank fs-1 text-muted d-block mb-3 opacity-50"></i>
        <p class="text-muted mb-3">No bank accounts have been added yet.</p>
        <a href="{{ route('master.bank-accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add First Bank Account
        </a>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($accounts as $acc)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-bank2 text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-6">
                                {{ $acc->display_label ?: $acc->bank_name }}
                                @if(!$acc->is_active)
                                    <span class="badge bg-danger ms-1" style="font-size:9px;">Inactive</span>
                                @endif
                            </div>
                            <div class="text-muted small">{{ $acc->bank_name }}</div>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('master.bank-accounts.edit', $acc) }}"
                           class="btn btn-outline-primary btn-sm py-0 px-2">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('master.bank-accounts.toggle', $acc) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm py-0 px-2 {{ $acc->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                <i class="bi bi-{{ $acc->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('master.bank-accounts.destroy', $acc) }}"
                              onsubmit="return confirm('Delete this bank account?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm py-0 px-2">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-2 small">
                    <div class="col-6">
                        <span class="text-muted">Account Name</span>
                        <div class="fw-semibold">{{ $acc->account_name }}</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted">Account No.</span>
                        <div class="fw-semibold">{{ $acc->account_no }}</div>
                    </div>
                    @if($acc->ifsc_code)
                    <div class="col-6">
                        <span class="text-muted">IFSC</span>
                        <div class="fw-semibold">{{ $acc->ifsc_code }}</div>
                    </div>
                    @endif
                    @if($acc->branch)
                    <div class="col-6">
                        <span class="text-muted">Branch</span>
                        <div class="fw-semibold">{{ $acc->branch }}</div>
                    </div>
                    @endif
                    @if($acc->upi_id)
                    <div class="col-12">
                        <span class="text-muted">UPI ID</span>
                        <div class="fw-semibold text-primary">{{ $acc->upi_id }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection