@extends('institute.layout')
@section('title', 'Vendors')
@section('breadcrumb', 'Finance / Wallet / Vendors')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace me-2 text-primary"></i>Vendors</h4>
        <small class="text-muted">Sabhi vendors — category, sub-category aur seedha unka work-order wallet</small>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Vendor Name</th>
                    <th>Category (L1)</th>
                    <th>Sub-Category (L2)</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $vendor->name }}</td>
                    <td class="text-muted">{{ $vendor->subCategory->category->name }}</td>
                    <td class="text-muted">{{ $vendor->subCategory->name }}</td>
                    <td class="text-muted">
                        {{ $vendor->contact_name ?? '-' }}
                        @if($vendor->contact_phone)
                            <div class="small">{{ $vendor->contact_phone }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $vendor->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finance.wallet.expense-categories.sub.vendors.work-orders.index', [$vendor->subCategory->category, $vendor->subCategory, $vendor]) }}"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-wallet2 me-1"></i> Open Wallet
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No vendors yet. Add vendors from Expense Categories → Sub-Category → Vendors.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
