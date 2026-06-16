@extends('institute.layout')
@section('title', 'Channel Partners')
@section('breadcrumb', 'Master / Channel Partners')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Channel Partners</h4>
        <small class="text-muted">{{ $partners->count() }} partner(s) registered</small>
    </div>
    <a href="{{ route('master.channel-partners.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Partner
    </a>
</div>

@if($partners->isEmpty())
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-people" style="font-size:3rem; color:#94a3b8;"></i>
            <h5 class="mt-3 text-muted">No Channel Partners Yet</h5>
            <p class="text-muted small">Add partners who refer students to your institute.</p>
            <a href="{{ route('master.channel-partners.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Add First Partner
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
                        <th>Partner</th>
                        <th>Contact</th>
                        <th>Commission</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partners as $i => $partner)
                    <tr>
                        <td class="text-muted small">{{ $i+1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $partner->name }}</div>
                            <small class="text-muted">{{ $partner->email }}</small>
                        </td>
                        <td>
                            <div class="small">{{ $partner->mobile }}</div>
                            <small class="text-muted">{{ $partner->city }}</small>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                {{ $partner->commission_percent }}%
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if($partner->can_add_admission)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-plus-circle me-1"></i>Admission
                                    </span>
                                @endif
                                @if($partner->can_view_students)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="bi bi-eye me-1"></i>View
                                    </span>
                                @endif
                                @if($partner->can_collect_fee)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="bi bi-cash me-1"></i>Fee
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('master.channel-partners.toggle', $partner) }}">
                                @csrf
                                <button class="btn btn-sm {{ $partner->status ? 'btn-success' : 'btn-secondary' }}">
                                    <i class="bi bi-{{ $partner->status ? 'check-circle' : 'x-circle' }}"></i>
                                    {{ $partner->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('master.channel-partners.edit', $partner) }}"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="openDeleteModal('{{ route('master.channel-partners.destroy', $partner) }}', '{{ addslashes($partner->name) }}')">
                                    <i class="bi bi-trash"></i>
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
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Delete Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete <strong id="deleteTargetName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i>Delete
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
