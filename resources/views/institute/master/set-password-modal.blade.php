{{--
    Set Login Password Modal
    Usage: Include in center/staff/partner list pages
    Variables: $user (model), $type (center|staff|partner), $routePrefix
--}}

@php
    $routes = [
        'center'  => 'master.center.set-password',
        'staff'   => 'master.staff.set-password',
        'partner' => 'master.partner.set-password',
    ];
    $routeName = $routes[$type] ?? null;
    $hasPassword = !empty($user->password);
@endphp

<div class="modal fade" id="pwdModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-3 border-bottom" style="background:#1e293b;">
                <h6 class="modal-title text-white mb-0">
                    <i class="bi bi-key me-2"></i>Set Login Password
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-center">
                    <div class="fw-semibold">{{ $user->name }}</div>
                    <div class="small text-muted">{{ $user->email }}</div>
                    @if($hasPassword)
                        <span class="badge bg-success mt-1">
                            <i class="bi bi-check-circle me-1"></i>Password already set
                        </span>
                    @else
                        <span class="badge bg-warning text-dark mt-1">
                            <i class="bi bi-exclamation-circle me-1"></i>No password set yet
                        </span>
                    @endif
                </div>

                @if($routeName)
                <form method="POST" action="{{ route($routeName, $user) }}" id="pwdForm{{ $user->id }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="password"
                               class="form-control form-control-sm"
                               placeholder="Min 6 characters" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                               class="form-control form-control-sm"
                               placeholder="Repeat password" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-check-lg me-1"></i>
                            {{ $hasPassword ? 'Update Password' : 'Set Password' }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>