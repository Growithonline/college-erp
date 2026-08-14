@extends('institute.layout')
@section('title', isset($workOrder) ? 'Edit Work Order' : 'Add Work Order')
@section('breadcrumb', 'Finance / Wallet / ' . $vendor->name . ' / Work Orders / ' . (isset($workOrder) ? 'Edit' : 'Add'))

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.index') }}">Categories</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.index', $expenseCategory) }}">{{ $expenseCategory->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.index', [$expenseCategory, $sub]) }}">{{ $sub->name }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor]) }}">{{ $vendor->name }} — Work Orders</a></li>
            <li class="breadcrumb-item active">{{ isset($workOrder) ? 'Edit' : 'Add' }}</li>
        </ol>
    </nav>
    <h4 class="mb-0 fw-bold">
        <i class="bi bi-clipboard-plus me-2 text-primary"></i>
        {{ isset($workOrder) ? 'Edit Work Order' : 'Add Work Order — ' . $vendor->name }}
    </h4>
</div>

<div class="card border-0 shadow-sm" style="max-width: 600px">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($workOrder)
            ? route('finance.wallet.expense-categories.sub.vendors.work-orders.update', [$expenseCategory, $sub, $vendor, $workOrder])
            : route('finance.wallet.expense-categories.sub.vendors.work-orders.store', [$expenseCategory, $sub, $vendor]) }}">
            @csrf
            @if(isset($workOrder)) @method('PUT') @endif

            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $workOrder->title ?? '') }}"
                       placeholder="e.g. Building 1 Renovation - Phase 1">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $workOrder->description ?? '') }}</textarea>
            </div>

            @if(!isset($workOrder))
            <div class="mb-3">
                <label class="form-label fw-semibold">Initial Budget <span class="text-muted small">(optional)</span></label>
                <input type="number" step="0.01" min="0" name="initial_budget" class="form-control"
                       value="{{ old('initial_budget') }}" placeholder="e.g. 30000">
                <small class="text-muted">Credited to this work order's wallet. Does not affect the institute's main wallet.</small>
            </div>
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold small">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $workOrder->notes ?? '') }}</textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ isset($workOrder) ? 'Update' : 'Add Work Order' }}</button>
                <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$expenseCategory, $sub, $vendor]) }}"
                   class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
