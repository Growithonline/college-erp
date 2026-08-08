@extends('super_admin.layout')
@section('title', 'Groups / Trusts')
@section('breadcrumb')
    <li class="breadcrumb-item active">Groups / Trusts</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3 text-primary me-2"></i> Groups / Trusts</h5>
    <a href="{{ route('super_admin.groups.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Group
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Name</th>
                        <th class="text-center">Institutes</th>
                        <th class="text-center">Group Admins</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $i => $group)
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $group->name }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">{{ $group->institutes_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $group->group_admins_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($group->status)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <a href="{{ route('super_admin.groups.show', $group->id) }}"
                               class="btn btn-sm btn-outline-primary py-0 px-2" title="View">
                                <i class="bi bi-eye" style="font-size:11px;"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-diagram-3 fs-2 d-block mb-2 opacity-25"></i>
                            No groups yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
