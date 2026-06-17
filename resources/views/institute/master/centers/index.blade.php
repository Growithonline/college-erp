@extends('institute.layout')
@section('title','Centers')
@section('breadcrumb','Master / Centers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Centers</h4>
        <small class="text-muted">{{ $centers->count() }} center(s)</small>
    </div>
    <a href="{{ route('master.centers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Center
    </a>
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
                        <div class="d-flex gap-1">
                            <a href="{{ route('master.centers.edit', $c) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="openDeleteModal('{{ route('master.centers.destroy', $c) }}', '{{ addslashes($c->name) }}')">
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
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Delete Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to delete <strong id="deleteTargetName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone. Centers with linked students cannot be deleted.</p>
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
