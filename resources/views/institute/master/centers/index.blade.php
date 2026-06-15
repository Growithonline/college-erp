@extends('institute.layout')
@section('title','Centers')
@section('breadcrumb','Master / Centers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Centers</h4>
        <small class="text-muted">{{ $centers->count() }} center(s)</small>
    </div>
    <a href="{{ route('master.centers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Center
    </a>
</div>

@if($errors->has('delete'))
<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('delete') }}</div>
@endif

@if($centers->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-building" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Centers Yet</h5>
        <a href="{{ route('master.centers.create') }}" class="btn btn-primary mt-2">
            <i class="bi bi-plus-lg me-1"></i> Add First Center
        </a>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Center</th>
                    <th>Contact</th>
                    <th>City</th>
                    <th>Permissions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($centers as $i => $c)
                <tr>
                    <td class="text-muted small">{{ $i+1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $c->name }}</div>
                        <small class="text-muted">{{ $c->code }}</small>
                    </td>
                    <td class="small">
                        {{ $c->mobile ?? '—' }}
                        @if($c->email)
                        <br><span class="text-muted">{{ $c->email }}</span>
                        @endif
                    </td>
                    <td class="small">{{ $c->city ?? '—' }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge border {{ $c->can_add_admission ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-light text-muted' }}"
                                  style="font-size:10px;">
                                <i class="bi bi-person-plus me-1"></i>Admission
                            </span>
                            <span class="badge border {{ $c->can_view_students ? 'bg-success-subtle text-success border-success-subtle' : 'bg-light text-muted' }}"
                                  style="font-size:10px;">
                                <i class="bi bi-eye me-1"></i>View Students
                            </span>
                            <span class="badge border {{ $c->can_collect_fee ? 'bg-warning-subtle text-warning border-warning-subtle' : 'bg-light text-muted' }}"
                                  style="font-size:10px;">
                                <i class="bi bi-cash me-1"></i>Collect Fee
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge border {{ $c->status ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle' }}"
                              style="font-size:11px;">
                            {{ $c->status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('master.centers.edit', $c) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('master.centers.destroy', $c) }}"
                                  onsubmit="return confirm('Delete this center?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
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
