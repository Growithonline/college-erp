@extends('institute.layout')
@section('title','Fee Types')
@section('breadcrumb','Master / Fee Structure / Fee Types')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-0 fw-bold">Fee Types</h4><small class="text-muted">{{ $feeTypes->count() }} fee types configured</small></div>
    <a href="{{ route('master.fee-types.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Fee Type</a>
</div>
@if($feeTypes->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-tags" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Fee Types Yet</h5>
        <a href="{{ route('master.fee-types.create') }}" class="btn btn-primary mt-2"><i class="bi bi-plus-lg me-1"></i>Add First Fee Type</a>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Name</th><th>Category</th><th>Description</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($feeTypes as $i => $ft)
                <tr>
                    <td class="text-muted small">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $ft->name }}</td>
                    <td><span class="badge bg-info-subtle text-info border border-info-subtle">{{ $categories[$ft->category] ?? $ft->category }}</span></td>
                    <td class="text-muted small">{{ $ft->description ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('master.fee-types.toggle', $ft) }}">@csrf
                            <button class="btn btn-sm {{ $ft->is_active ? 'btn-success' : 'btn-secondary' }}">
                                <i class="bi bi-{{ $ft->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                {{ $ft->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('master.fee-types.edit', $ft) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            @if(!$ft->is_system)
                            <form method="POST" action="{{ route('master.fee-types.destroy', $ft) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
