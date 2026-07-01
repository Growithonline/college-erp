@extends('institute.layout')
@section('title', 'Employees')
@section('breadcrumb', 'Employees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Employees</h4>
        <p class="text-muted mb-0" style="font-size:13px;">Non-teaching support staff management</p>
    </div>
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="bi bi-plus-lg me-1"></i> Add Employee
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="row g-2 mb-4">
    <div class="col-md-3">
        <input type="text" class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Name / Code / Phone">
    </div>
    <div class="col-md-3">
        <select class="form-select form-select-sm" name="department">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select form-select-sm" name="status">
            <option value="">All Status</option>
            <option value="active"     {{ request('status') === 'active'     ? 'selected' : '' }}>Active</option>
            <option value="inactive"   {{ request('status') === 'inactive'   ? 'selected' : '' }}>Inactive</option>
            <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            <option value="resigned"   {{ request('status') === 'resigned'   ? 'selected' : '' }}>Resigned</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary px-3"><i class="bi bi-search me-1"></i> Search</button>
        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light px-3">Reset</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($employees->count())
        <table class="table table-hover mb-0 align-middle">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="ps-4">Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            @if($emp->photo)
                                <img src="{{ Storage::url($emp->photo) }}" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                            @else
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-semibold" style="width:32px;height:32px;font-size:12px;">
                                    {{ strtoupper(substr($emp->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="fw-medium" style="font-size:13px;">{{ $emp->name }}</div>
                                @if($emp->employee_code)
                                    <div class="text-muted" style="font-size:11px;">{{ $emp->employee_code }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px;">{{ $emp->department?->name ?? '—' }}</td>
                    <td style="font-size:13px;">{{ $emp->designation?->name ?? '—' }}</td>
                    <td style="font-size:13px;">{{ $emp->phone ?? '—' }}</td>
                    <td><span class="badge text-bg-light text-dark border" style="font-size:11px;">{{ str_replace('_', ' ', ucfirst($emp->employment_type)) }}</span></td>
                    <td>
                        @php
                            $statusColors = ['active' => 'success', 'inactive' => 'secondary', 'terminated' => 'danger', 'resigned' => 'warning'];
                        @endphp
                        <span class="badge text-bg-{{ $statusColors[$emp->status] ?? 'secondary' }}">
                            {{ ucfirst($emp->status) }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $employees->links() }}</div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-3">No employees found.</p>
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-plus-lg me-1"></i> Add Employee
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
