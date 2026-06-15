@extends('institute.layout')
@section('title', $expenseCategory->name . ' - Sub Categories')
@section('breadcrumb', 'Finance / Wallet / ' . $expenseCategory->name . ' / Sub-Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item">
                    <a href="{{ route('finance.wallet.expense-categories.index') }}">Expense Categories</a>
                </li>
                <li class="breadcrumb-item active">{{ $expenseCategory->name }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-diagram-3 me-2 text-warning"></i>
            {{ $expenseCategory->name }} — Sub Categories (L2)
        </h4>
    </div>
    <a href="{{ route('finance.wallet.expense-categories.sub.create', $expenseCategory) }}" class="btn btn-warning btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Sub-Category
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Sub-Category Name</th>
                    <th>Description</th>
                    <th>Vendors</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subCategories as $sub)
                <tr>
                    <td class="text-muted small">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $sub->name }}</td>
                    <td class="text-muted small">{{ $sub->description ?? '-' }}</td>
                    <td>
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}"
                           class="badge bg-primary bg-opacity-10 text-dark text-decoration-none">
                            {{ $sub->vendors_count }} vendors
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </td>
                    <td>
                        <span class="badge {{ $sub->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $sub->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finance.wallet.expense-categories.sub.edit', [$expenseCategory, $sub]) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('finance.wallet.expense-categories.sub.destroy', [$expenseCategory, $sub]) }}"
                              class="d-inline" onsubmit="return confirm('Delete karo?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Koi sub-category nahi hai.
                        <a href="{{ route('finance.wallet.expense-categories.sub.create', $expenseCategory) }}">Pehli banao.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
