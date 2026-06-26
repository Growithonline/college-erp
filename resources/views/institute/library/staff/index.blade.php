@extends('institute.layout')
@section('title', 'Library Staff')
@section('breadcrumb', 'Library Management / Staff')
@section('content')

<style>
.status-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.lock-badge { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.active-badge { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
.inactive-badge { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
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
                                <form method="POST" action="{{ route('library.staff.reset-lock', $member) }}" class="mt-1">
                                    @csrf
                                    <button class="btn btn-xs btn-outline-warning" style="font-size:11px;padding:1px 8px;">
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
                                <a href="{{ route('library.staff.edit', $member) }}"
                                   class="btn btn-outline-primary btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('library.staff.toggle', $member) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $member->status ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $member->status ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $member->status ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('library.staff.resend-credentials', $member) }}"
                                      onsubmit="return confirm('Send new login credentials to {{ addslashes($member->email) }}?')">
                                    @csrf
                                    <button class="btn btn-outline-info btn-sm" title="Resend Credentials">
                                        <i class="bi bi-envelope-arrow-up"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('library.staff.destroy', $member) }}"
                                      onsubmit="return confirmDelete('{{ addslashes($member->name) }}')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="Delete">
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

@push('scripts')
<script>
function confirmDelete(name) {
    return confirm('Delete library staff member "' + name + '"? This action cannot be undone.');
}
</script>
@endpush
@endsection
