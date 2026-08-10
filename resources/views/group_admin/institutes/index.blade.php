@extends('group_admin.layout')
@section('title', 'Institutes')
@section('breadcrumb')
    <li class="breadcrumb-item active">Institutes</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if($group)
            @if($remaining === null)
                <span class="badge bg-primary-subtle text-primary">Unlimited institute slots</span>
            @else
                <span class="badge bg-primary-subtle text-primary">{{ $remaining }} of {{ $group->institute_quota }} institute slot(s) remaining</span>
            @endif
        @endif
    </div>
    @if($groupAdmin->can_create_institutes)
        @if($remaining === 0)
            <span class="text-muted small">Institute quota reached — contact the platform administrator.</span>
        @else
            <a href="{{ route('group_admin.institutes.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Institute
            </a>
        @endif
    @endif
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('group_admin.institutes.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, short name, or institute ID..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-search me-1"></i>Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('group_admin.institutes.index') }}" class="btn btn-outline-secondary btn-sm text-nowrap">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">Institutes in {{ $groupAdmin->group->name ?? 'this group' }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Institute</th>
                    <th>Short Name</th>
                    <th>Institute ID</th>
                    <th>Owner Name</th>
                    <th>Login Email</th>
                    <th class="text-end">Students</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($institutes as $institute)
                    <tr>
                        <td>{{ $institute->name }}</td>
                        <td>{{ $institute->short_name }}</td>
                        <td style="font-family:monospace;">{{ $institute->institute_uid }}</td>
                        <td>{{ $institute->owner_name }}</td>
                        <td>{{ $institute->owner_email }}</td>
                        <td class="text-end">{{ $institute->students_count }}</td>
                        <td>
                            @if($institute->status === 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('group_admin.institutes.show', $institute->id) }}"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('group_admin.institutes.edit', $institute->id) }}"
                                   class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('group_admin.institutes.toggle', $institute->id) }}"
                                      onsubmit="return confirm('{{ $institute->status === 'active' ? 'Deactivate this institute?' : 'Activate this institute?' }}');">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm py-0 px-2 {{ $institute->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            title="{{ $institute->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $institute->status === 'active' ? 'slash-circle' : 'check-circle' }}"></i>
                                    </button>
                                </form>
                                @if($groupAdmin->can_reset_institute_password)
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        data-bs-toggle="modal" data-bs-target="#resetPwdModal{{ $institute->id }}">
                                    <i class="bi bi-key"></i>
                                </button>
                                @endif
                            </div>

                            @if($groupAdmin->can_reset_institute_password)
                            <div class="modal fade" id="resetPwdModal{{ $institute->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('group_admin.institutes.reset-password', $institute->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h6 class="modal-title">Reset Password — {{ $institute->name }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">New Password</label>
                                                    <input type="password" name="password" class="form-control form-control-sm" minlength="8" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Confirm Password</label>
                                                    <input type="password" name="password_confirmation" class="form-control form-control-sm" minlength="8" required>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="notify_email" id="notifyEmail{{ $institute->id }}" value="1" checked>
                                                    <label class="form-check-label small" for="notifyEmail{{ $institute->id }}">
                                                        Send new password to owner's email ({{ $institute->owner_email }})
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-danger">Reset Password</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No institutes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($institutes->hasPages())
    <div class="p-3 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">Showing {{ $institutes->firstItem() }}–{{ $institutes->lastItem() }} of {{ $institutes->total() }} institute(s)</small>
        {{ $institutes->links() }}
    </div>
    @endif
</div>

@endsection
