@extends('super_admin.layout')
@section('title', $group->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.groups.index') }}" class="text-decoration-none">Groups / Trusts</a></li>
    <li class="breadcrumb-item active">{{ $group->name }}</li>
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('super_admin.groups.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
    <h5 class="mb-0 fw-bold">{{ $group->name }}</h5>
    @if($group->status)
        <span class="badge bg-success-subtle text-success">Active</span>
    @else
        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
    @endif
    <div class="ms-auto">
        <form method="POST" action="{{ route('super_admin.groups.toggle', $group->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-sm btn-outline-{{ $group->status ? 'danger' : 'success' }}">
                <i class="bi bi-{{ $group->status ? 'slash-circle' : 'check-circle' }} me-1"></i>
                {{ $group->status ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>

<div class="row g-3">
    {{-- Institutes in this group --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-building text-primary me-2"></i>Institutes ({{ $group->institutes_count }})</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tbody>
                        @forelse($institutes as $institute)
                        <tr>
                            <td>
                                <a href="{{ route('super_admin.institutes.show', $institute->id) }}" class="text-decoration-none">
                                    {{ $institute->name }}
                                </a>
                                <div class="text-muted" style="font-size:11px;">{{ $institute->short_name }} &bull; {{ $institute->students_count }} students</div>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('super_admin.institutes.assign-group', $institute->id) }}">
                                    @csrf
                                    <input type="hidden" name="group_id" value="">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove from group">
                                        <i class="bi bi-x-lg" style="font-size:11px;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="text-muted text-center py-3">No institutes assigned yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if($unassignedInstitutes->isNotEmpty())
                <form method="POST" action="{{ route('super_admin.groups.institutes.store', $group->id) }}" class="d-flex gap-2">
                    @csrf
                    <select name="institute_id" class="form-select form-select-sm" required>
                        <option value="">Add an institute to this group...</option>
                        @foreach($unassignedInstitutes as $institute)
                            <option value="{{ $institute->id }}">{{ $institute->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </form>
                @else
                <p class="text-muted small mb-0">No unassigned institutes available.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Group Admins --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0 pt-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-badge text-success me-2"></i>Group Admins</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tbody>
                        @forelse($groupAdmins as $admin)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $admin->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $admin->email }}</div>
                                <form method="POST" action="{{ route('super_admin.groups.admins.toggle-reset-permission', [$group->id, $admin->id]) }}" class="mt-1">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $admin->can_reset_institute_password ? 'warning' : 'secondary' }} py-0 px-2" style="font-size:10px;">
                                        <i class="bi bi-key"></i>
                                        {{ $admin->can_reset_institute_password ? 'Can reset passwords' : 'Cannot reset passwords' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                @if($admin->status)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('super_admin.groups.admins.toggle-status', [$group->id, $admin->id]) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $admin->status ? 'danger' : 'success' }} py-0 px-2">
                                        <i class="bi bi-{{ $admin->status ? 'slash-circle' : 'check-circle' }}" style="font-size:11px;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center py-3">No group admins yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <hr>

                <form method="POST" action="{{ route('super_admin.groups.admins.store', $group->id) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Name</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Add Group Admin
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
