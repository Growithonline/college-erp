@extends('staff.layout')
@section('title', $staffMember->name)
@section('breadcrumb', 'Staff Management / ' . $staffMember->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>{{ $staffMember->name }}</h4>
        <small class="text-muted">{{ $staffMember->role?->name ?? 'Staff Member' }}</small>
    </div>
    <a href="{{ route('staff.staff-manage.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">Basic Info</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr><th class="text-muted w-40">Name</th><td>{{ $staffMember->name }}</td></tr>
                    <tr><th class="text-muted">Role</th><td>{{ $staffMember->role?->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Mobile</th><td>{{ $staffMember->mobile }}</td></tr>
                    <tr><th class="text-muted">Email</th><td>{{ $staffMember->email ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Status</th>
                        <td>
                            @if($staffMember->status)
                                <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Joining Date</th><td>{{ $staffMember->joining_date ? \Carbon\Carbon::parse($staffMember->joining_date)->format('d M Y') : '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">Salary Info</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <th class="text-muted w-40">Payroll Type</th>
                        <td>{{ ucfirst($staffMember->payroll_type ?? 'monthly') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Monthly Salary</th>
                        <td>{{ $staffMember->monthly_salary !== null ? 'Rs ' . number_format((float) $staffMember->monthly_salary, 2) : '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Daily Wage</th>
                        <td>{{ $staffMember->daily_wage !== null ? 'Rs ' . number_format((float) $staffMember->daily_wage, 2) : '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
