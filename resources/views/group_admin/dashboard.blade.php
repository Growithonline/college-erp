@extends('group_admin.layout')
@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#dcfce7;">
                    <i class="bi bi-cash-coin fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Today's Collection</div>
                    <div class="fw-bold fs-5">₹{{ number_format($todayCollected, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#dbeafe;">
                    <i class="bi bi-graph-up-arrow fs-4" style="color:#2563eb;"></i>
                </div>
                <div>
                    <div class="text-muted small">This Month's Collection</div>
                    <div class="fw-bold fs-5">₹{{ number_format($monthCollected, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#ede9fe;">
                    <i class="bi bi-person-plus fs-4" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="text-muted small">Today's Admissions</div>
                    <div class="fw-bold fs-5">{{ $todayAdmissions }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#fee2e2;">
                    <i class="bi bi-building fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Institutes</div>
                    <div class="fw-bold fs-5">{{ $institutes->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">Institutes in {{ $groupAdmin->group->name ?? 'this group' }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Institute</th>
                    <th>Short Name</th>
                    <th>Institute ID</th>
                    <th>Owner Name</th>
                    <th>Login Email</th>
                    <th class="text-end">Today's Collection</th>
                    <th class="text-end">Total Students</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perInstitute as $row)
                    <tr>
                        <td>{{ $row['institute']->name }}</td>
                        <td>{{ $row['institute']->short_name }}</td>
                        <td style="font-family:monospace;">{{ $row['institute']->institute_uid }}</td>
                        <td>{{ $row['institute']->owner_name }}</td>
                        <td>{{ $row['institute']->owner_email }}</td>
                        <td class="text-end">₹{{ number_format($row['today_collected'], 2) }}</td>
                        <td class="text-end">{{ $row['total_students'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No institutes assigned to this group yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
