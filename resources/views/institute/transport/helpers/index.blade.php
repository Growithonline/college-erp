@extends('institute.layout')
@section('title', 'Transport Helpers')
@section('breadcrumb', 'Transport / Helpers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Transport Helpers</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Bus attendants / conductors</p>
    </div>
    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Add Helper
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($helpers->count())
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Name</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($helpers as $h)
                <tr>
                    <td class="ps-4 fw-medium">{{ $h->name }}</td>
                    <td>{{ $h->mobile ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $h->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $h->status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary"
                            onclick="openEdit({{ $h->id }}, '{{ addslashes($h->name) }}', '{{ $h->mobile ?? '' }}', '{{ addslashes($h->notes ?? '') }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('transport.helpers.toggle', $h) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm {{ $h->status ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                <i class="bi bi-{{ $h->status ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('transport.helpers.destroy', $h) }}" class="d-inline"
                            onsubmit="return confirm('Delete this helper?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $helpers->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-badge fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-3">No helpers added yet.</p>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg me-1"></i> Add First Helper
            </button>
        </div>
        @endif
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('transport.helpers.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Helper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Mobile</label>
                        <input type="text" class="form-control" name="mobile" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold"><i class="bi bi-pencil me-2 text-warning"></i>Edit Helper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Mobile</label>
                        <input type="text" class="form-control" name="mobile" id="editMobile" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Notes</label>
                        <textarea class="form-control" name="notes" id="editNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-floppy me-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(id, name, mobile, notes) {
    document.getElementById('editForm').action = `/transport/helpers/${id}`;
    document.getElementById('editName').value   = name;
    document.getElementById('editMobile').value = mobile;
    document.getElementById('editNotes').value  = notes;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
@if($errors->any())
    document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('addModal')).show());
@endif
</script>
@endsection
