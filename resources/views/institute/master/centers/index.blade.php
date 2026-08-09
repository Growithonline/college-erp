@extends('institute.layout')
@section('title','Centers')
@section('breadcrumb','Master / Centers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Centers</h4>
        <small class="text-muted">{{ $centers->count() }} active center(s)</small>
    </div>
    <div class="d-flex gap-2">
        @if($trashedCount > 0)
        <a href="{{ route('master.centers.trashed') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-archive me-1"></i> Archived
            <span class="badge bg-secondary ms-1">{{ $trashedCount }}</span>
        </a>
        @endif
        <form method="POST" action="{{ route('master.centers.notify-login-id') }}" class="d-inline"
              onsubmit="return confirm('Email every active center their new Login ID?');">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-envelope-check me-1"></i> Notify Login ID
            </button>
        </form>
        <a href="{{ route('master.centers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Center
        </a>
    </div>
</div>


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
                    <th>OTP</th>
                    <th>Login</th>
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
                        <div><small class="text-muted" style="font-family:monospace;">{{ $c->center_uid ?? '—' }}</small></div>
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
                        <form method="POST" action="{{ route('master.centers.toggle-otp-bypass', $c) }}" title="When ON, this center logs in without OTP">
                            @csrf
                            <button class="btn btn-sm {{ $c->otp_bypass ? 'btn-warning' : 'btn-outline-secondary' }}">
                                <i class="bi bi-{{ $c->otp_bypass ? 'shield-slash' : 'shield-check' }}"></i>
                                {{ $c->otp_bypass ? 'Bypass' : 'Required' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('master.centers.toggle-login-block', $c) }}" title="When blocked, this center cannot log in but still appears as an admission source">
                            @csrf
                            <button class="btn btn-sm {{ $c->login_blocked ? 'btn-danger' : 'btn-outline-success' }}"
                                    onclick="return confirm('{{ $c->login_blocked ? 'Unblock login for this center?' : 'Block login for this center? They will no longer be able to log in, but will still show up in the admission source list.' }}');">
                                <i class="bi bi-{{ $c->login_blocked ? 'lock-fill' : 'unlock-fill' }}"></i>
                                {{ $c->login_blocked ? 'Blocked' : 'Allowed' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('master.centers.edit', $c) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Archive Center"
                                    onclick="openDeleteModal('{{ route('master.centers.destroy', $c) }}', '{{ addslashes($c->name) }}')">
                                <i class="bi bi-archive"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-archive me-2 text-secondary"></i>Archive Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Archive <strong id="deleteTargetName"></strong>?</p>
                <p class="text-muted small mb-0">Center will be deactivated and moved to Archived list. All data (students, fees, wallet) stays safe. You can restore it anytime.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" id="confirmDeleteBtn">
                    <i class="bi bi-archive me-1"></i>Archive
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var _deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
var _deleteUrl   = '';

function openDeleteModal(url, name) {
    _deleteUrl = url;
    document.getElementById('deleteTargetName').textContent = '"' + name + '"';
    _deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';
    try {
        var res = await fetch(_deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        var data = await res.json();
        _deleteModal.hide();
        if (data.success) {
            window.showToast?.(data.message, 'success');
            setTimeout(function () { window.location.reload(); }, 900);
        } else {
            window.showToast?.(data.message || 'Delete failed.', 'danger');
        }
    } catch (e) {
        _deleteModal.hide();
        window.showToast?.('Network error. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
    }
});
</script>
@endpush
@endsection
