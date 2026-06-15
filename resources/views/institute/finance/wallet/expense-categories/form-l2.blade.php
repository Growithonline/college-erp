@extends('institute.layout')
@section('title', isset($sub) ? 'Edit Sub-Category' : 'New Sub-Category')
@section('breadcrumb', 'Finance / Wallet / ' . $expenseCategory->name . ' / ' . (isset($sub) ? 'Edit' : 'New Sub-Category'))

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item">
                <a href="{{ route('finance.wallet.expense-categories.index') }}">Expense Categories</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a>
            </li>
            <li class="breadcrumb-item active">{{ isset($sub) ? 'Edit' : 'New' }}</li>
        </ol>
    </nav>
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-folder-plus me-2 text-warning"></i>
        {{ isset($sub) ? 'Edit Sub-Category' : 'New Sub-Category under ' . $expenseCategory->name }}
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width: 500px">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($sub)
            ? route('finance.wallet.expense-categories.sub.update', [$expenseCategory, $sub])
            : route('finance.wallet.expense-categories.sub.store', $expenseCategory) }}">
            @csrf
            @if(isset($sub)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Sub-Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $sub->name ?? '') }}"
                       placeholder="e.g. Electricity, Plumbing, Books, Software">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <input type="text" name="description" class="form-control"
                       value="{{ old('description', $sub->description ?? '') }}" placeholder="Optional">
            </div>

            @if(isset($sub))
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                           {{ old('is_active', $sub->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>
            @endif

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-warning">{{ isset($sub) ? 'Update' : 'Create' }}</button>
                <a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
