@extends('institute.layout')
@section('title', 'Library Staff')
@section('breadcrumb', 'Library Management / Staff')
@section('content')

<style>
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.lock-badge    { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.active-badge  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.inactive-badge{ background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-workspace me-2 text-primary"></i>Library Staff</h4>
        <small class="text-muted">{{ $staff->count() }} member(s) registered</small>
    </div>
    <a href="{{ route('library.staff.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Library Staff
    </a>
</div>

@if($staff->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-person-workspace" style="font-size:3rem;color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Library Staff Yet</h5>
            <p class="text-muted small">Add library staff members to manage library operations.</p>
            <a href="{{ route('library.staff.create') }}" class="btn btn-primary mt-1">
                <i class="bi bi-plus-lg me-1"></i> Add First Staff
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
                        <th>Staff Member</th>
                        <th>Designation</th>
                        <th>Shift</th>
                        <th>Permissions</th>
                        <th>Dual Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $i => $member)
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($member->photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->photo) }}"
                                         alt="{{ $member->name }}"
                                         class="rounded-circle flex-shrink-0"
                                         style="width:38px;height:38px;object-fit:cover;border:2px solid #e2e8f0;">
                                @else
                                    <div class="rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="width:38px;height:38px;font-size:14px;background:#0ea5e9;">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $member->name }}</div>
                                    <div class="text-muted small">{{ $member->email }}</div>
                                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $member->phone }}</div>
                                    <code class="small text-secondary">{{ $member->employee_id }}</code>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$member->designation] ?? $member->designation }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                <i class="bi bi-clock me-1"></i>{{ \App\Models\LibraryStaff::SHIFT_LABELS[$member->shift] ?? $member->shift }}
                            </span>
                        </td>
                        <td>
                            @php $preset = $member->permissionRecord?->preset ?? 'custom'; @endphp
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                {{ \App\Models\LibraryStaff::PRESET_LABELS[$preset] ?? 'Custom' }}
                            </span>
                            <div class="text-muted small mt-1">{{ count($member->permissionRecord?->permissions ?? []) }} permission(s)</div>
                        </td>
                        <td>
                            @if($member->isDualRole())
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-person-badge me-1"></i>Also: Staff
                                </span>
                                <div class="text-muted small">{{ $member->staffMember?->name ?? '—' }}</div>
                            @else
                                <span class="text-muted small">Library only</span>
                            @endif
                        </td>
                        <td>
                            @if($member->isLocked())
                                <span class="status-badge lock-badge">
                                    <i class="bi bi-lock-fill"></i> Locked
                                </span>
                                <div class="text-muted small mt-1">Until {{ $member->locked_until->format('h:i A') }}</div>
                                <form method="POST" action="{{ route('library.staff.reset-lock', $member) }}" class="mt-1 confirm-form">
                                    @csrf
                                    <button type="button" class="btn btn-xs btn-outline-warning confirm-btn"
                                            style="font-size:11px;padding:1px 8px;"
                                            data-icon="bi-unlock"
                                            data-icon-color="text-warning"
                                            data-title="Unlock Account"
                                            data-message="Unlock the account of <strong>{{ e($member->name) }}</strong>? They will be able to log in again."
                                            data-confirm-label="Unlock"
                                            data-confirm-class="btn-warning">
                                        <i class="bi bi-unlock me-1"></i>Unlock
                                    </button>
                                </form>
                            @elseif($member->status)
                                <span class="status-badge active-badge">
                                    <i class="bi bi-check-circle-fill"></i> Active
                                </span>
                            @else
                                <span class="status-badge inactive-badge">
                                    <i class="bi bi-x-circle"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">

                                {{-- Edit --}}
                                <a href="{{ route('library.staff.edit', $member) }}"
                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Toggle status --}}
                                <form method="POST" action="{{ route('library.staff.toggle', $member) }}" class="confirm-form">
                                    @csrf
                                    <button type="button"
                                            class="btn btn-sm confirm-btn {{ $member->status ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $member->status ? 'Deactivate' : 'Activate' }}"
                                            data-icon="{{ $member->status ? 'bi-pause-circle' : 'bi-play-circle' }}"
                                            data-icon-color="{{ $member->status ? 'text-warning' : 'text-success' }}"
                                            data-title="{{ $member->status ? 'Deactivate Staff' : 'Activate Staff' }}"
                                            data-message="{{ $member->status
                                                ? 'Deactivate <strong>' . e($member->name) . '</strong>? They will not be able to log in.'
                                                : 'Activate <strong>' . e($member->name) . '</strong>? They will be able to log in.' }}"
                                            data-confirm-label="{{ $member->status ? 'Deactivate' : 'Activate' }}"
                                            data-confirm-class="{{ $member->status ? 'btn-warning' : 'btn-success' }}">
                                        <i class="bi bi-{{ $member->status ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                </form>

                                {{-- Resend credentials --}}
                                <form method="POST" action="{{ route('library.staff.resend-credentials', $member) }}" class="confirm-form">
                                    @csrf
                                    <button type="button"
                                            class="btn btn-outline-info btn-sm confirm-btn"
                                            title="Resend Credentials"
                                            data-icon="bi-envelope-arrow-up"
                                            data-icon-color="text-info"
                                            data-title="Resend Login Credentials"
                                            data-message="Generate a new password and send login credentials to <strong>{{ e($member->email) }}</strong>?"
                                            data-confirm-label="Send"
                                            data-confirm-class="btn-info">
                                        <i class="bi bi-envelope-arrow-up"></i>
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('library.staff.destroy', $member) }}" class="confirm-form">
                                    @csrf @method('DELETE')
                                    <button type="button"
                                            class="btn btn-outline-danger btn-sm confirm-btn"
                                            title="Delete"
                                            data-icon="bi-trash"
                                            data-icon-color="text-danger"
                                            data-title="Delete Staff Member"
                                            data-message="Permanently delete <strong>{{ e($member->name) }}</strong>? This action cannot be undone."
                                            data-confirm-label="Delete"
                                            data-confirm-class="btn-danger">
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

{{-- Confirmation modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden;">
            <div class="modal-body p-4 text-center">
                <div id="confirmIcon"
                     class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width:60px;height:60px;font-size:26px;">
                </div>
                <h5 class="fw-bold mb-2" id="confirmTitle"></h5>
                <p class="text-muted mb-0" id="confirmMessage" style="font-size:14px;line-height:1.6;"></p>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2 justify-content-center">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal"
                        style="border-radius:10px;min-width:100px;">
                    Cancel
                </button>
                <button type="button" class="btn px-4" id="confirmOkBtn"
                        style="border-radius:10px;min-width:100px;">
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let pendingForm = null;

    const modal      = new bootstrap.Modal(document.getElementById('confirmModal'));
    const iconEl     = document.getElementById('confirmIcon');
    const titleEl    = document.getElementById('confirmTitle');
    const messageEl  = document.getElementById('confirmMessage');
    const okBtn      = document.getElementById('confirmOkBtn');

    // Color map for icon background
    const bgMap = {
        'text-danger'  : ['#fef2f2', '#dc2626'],
        'text-warning' : ['#fffbeb', '#d97706'],
        'text-success' : ['#f0fdf4', '#16a34a'],
        'text-info'    : ['#f0f9ff', '#0ea5e9'],
    };

    document.querySelectorAll('.confirm-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingForm = btn.closest('.confirm-form');

            const iconClass   = btn.dataset.icon       || 'bi-question-circle';
            const iconColor   = btn.dataset.iconColor  || 'text-secondary';
            const [bg, color] = bgMap[iconColor]       || ['#f8fafc', '#64748b'];
            const confirmCls  = btn.dataset.confirmClass || 'btn-primary';

            iconEl.className    = `mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bi ${iconClass}`;
            iconEl.style.background = bg;
            iconEl.style.color      = color;

            titleEl.textContent    = btn.dataset.title   || 'Are you sure?';
            messageEl.innerHTML    = btn.dataset.message || '';

            okBtn.className = `btn px-4 ${confirmCls}`;
            okBtn.style.borderRadius = '10px';
            okBtn.style.minWidth     = '100px';
            okBtn.textContent        = btn.dataset.confirmLabel || 'Confirm';

            modal.show();
        });
    });

    okBtn.addEventListener('click', function () {
        if (pendingForm) {
            modal.hide();
            pendingForm.submit();
            pendingForm = null;
        }
    });

    // Reset pending form if modal dismissed without confirming
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function () {
        pendingForm = null;
    });
})();
</script>
@endpush
@endsection
