@extends('institute.layout')
@section('title', 'Vendors - ' . $sub->name)
@section('breadcrumb', 'Finance / Wallet / ' . $expenseCategory->name . ' / ' . $sub->name . ' / Vendors')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.index') }}">Categories</a></li>
                <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a></li>
                <li class="breadcrumb-item active">{{ $sub->name }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-person-workspace me-2 text-primary"></i>
            {{ $expenseCategory->name }} / {{ $sub->name }} — Vendors
        </h4>
    </div>
    <a href="{{ route('finance.wallet.expense-categories.sub.vendors.create', [$expenseCategory, $sub]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Vendor
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Vendor Name</th>
                    <th>GST No</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $vendor->name }}</td>
                    <td class="text-muted">{{ $vendor->gst_no ?? '-' }}</td>
                    <td class="text-muted">{{ $vendor->contact_name ?? '-' }}</td>
                    <td class="text-muted">{{ $vendor->contact_phone ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.edit', [$expenseCategory, $sub, $vendor]) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('finance.wallet.expense-categories.sub.vendors.destroy', [$expenseCategory, $sub, $vendor]) }}"
                              class="d-inline" onsubmit="return confirm('Delete karo?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        Koi vendor nahi hai.
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.create', [$expenseCategory, $sub]) }}">Pehla vendor add karo.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
