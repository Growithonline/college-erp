@extends('institute.layout')
@section('title', 'Expense Categories')
@section('breadcrumb', 'Finance / Wallet / Expense Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2 text-warning"></i>Expense Categories (L1)</h4>
        <small class="text-muted">Top-level categories: Maintenance, IT, Salary, etc.</small>
    </div>
    <a href="{{ route('finance.wallet.expense-categories.create') }}" class="btn btn-warning btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Category
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
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Sub-Categories</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td class="text-muted small">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $cat->name }}</td>
                    <td class="text-muted small">{{ $cat->description ?? '-' }}</td>
                    <td>
                        <a href="{{ route('finance.wallet.expense-categories.sub.index', $cat) }}"
                           class="badge bg-warning bg-opacity-20 text-dark text-decoration-none">
                            {{ $cat->sub_categories_count }} sub-categories
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </td>
                    <td>
                        <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finance.wallet.expense-categories.edit', $cat) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('finance.wallet.expense-categories.destroy', $cat) }}"
                              class="d-inline" onsubmit="return confirm('Delete karo?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Koi category nahi hai. <a href="{{ route('finance.wallet.expense-categories.create') }}">Pehli category banao.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
