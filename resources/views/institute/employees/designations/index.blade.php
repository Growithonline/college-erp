@extends('institute.layout')
@section('title', 'Employee Designations')
@section('breadcrumb', 'Employees / Designations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Employee Designations</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Driver, Helper, Guard, Peon, etc.</p>
    </div>
    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg me-1"></i> Add Designation
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
        @if($designations->count())
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Designation</th>
                    <th>Department</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($designations as $desig)
                <tr>
                    <td class="ps-4 fw-medium">{{ $desig->name }}</td>
                    <td class="text-muted" style="font-size:13px;">{{ $desig->department?->name ?? '—' }}</td>
                    <td><span class="badge text-bg-light text-dark border">{{ $desig->employees_count }}</span></td>
                    <td>
                        <span class="badge {{ $desig->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $desig->status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-primary"
                            onclick="openEdit({{ $desig->id }}, '{{ addslashes($desig->name) }}', {{ $desig->employee_department_id ?? 'null' }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('employees.designations.destroy', $desig) }}" class="d-inline"
                            onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-person-badge fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-3">No designations yet.</p>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg me-1"></i> Add Designation
            </button>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.designations.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Add Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Designation Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Driver, Guard">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Department</label>
                        <select class="form-select" name="employee_department_id">
                            <option value="">— Select Department —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
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

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Department</label>
                        <select class="form-select" name="employee_department_id" id="editDept">
                            <option value="">— None —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
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
function openEdit(id, name, deptId) {
    document.getElementById('editForm').action = `/employees/designations/${id}`;
    document.getElementById('editName').value = name;
    document.getElementById('editDept').value = deptId ?? '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endsection
