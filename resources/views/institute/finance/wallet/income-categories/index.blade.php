@extends('institute.layout')
@section('title', 'Income Categories')
@section('breadcrumb', 'Finance / Wallet / Income Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-info"></i>Manual Income Categories</h4>
        <small class="text-muted">Admin-defined income categories for manual entries</small>
    </div>
    <a href="{{ route('finance.wallet.income-categories.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Category
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Description</th>
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
                        <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $cat->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finance.wallet.income-categories.edit', $cat) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('finance.wallet.income-categories.destroy', $cat) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete karo?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        Koi category nahi hai. <a href="{{ route('finance.wallet.income-categories.create') }}">Pehli category banao.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
