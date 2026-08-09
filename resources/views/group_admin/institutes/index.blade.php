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
                    <th class="text-end">Students</th>
                    @if($groupAdmin->can_reset_institute_password)
                        <th class="text-end">Action</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($institutes as $institute)
                    <tr>
                        <td>{{ $institute->name }}</td>
                        <td>{{ $institute->short_name }}</td>
                        <td class="text-end">{{ $institute->students_count }}</td>
                        @if($groupAdmin->can_reset_institute_password)
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                    data-bs-toggle="modal" data-bs-target="#resetPwdModal{{ $institute->id }}">
                                <i class="bi bi-key me-1"></i> Reset Password
                            </button>

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
                        </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No institutes assigned to this group yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
