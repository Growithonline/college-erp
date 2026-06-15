@extends('staff.layout')
@section('title', 'Staff Members')
@section('breadcrumb', 'Staff Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Staff Members</h4>
        <small class="text-muted">Institute ke sab staff members</small>
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Name / Mobile search..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('staff.staff-manage.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($members->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 d-block mb-2"></i>
                Koi staff member nahi mila.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $i => $member)
                    <tr>
                        <td class="text-muted small">{{ $members->firstItem() + $i }}</td>
                        <td class="fw-semibold">{{ $member->name }}</td>
                        <td><small>{{ $member->role?->name ?? '—' }}</small></td>
                        <td><small>{{ $member->mobile }}</small></td>
                        <td><small>{{ $member->email ?? '—' }}</small></td>
                        <td>
                            @if($member->status)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('staff.staff-manage.show', $member) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@if($members->hasPages())
<div class="mt-3">{{ $members->links() }}</div>
@endif
@endsection
