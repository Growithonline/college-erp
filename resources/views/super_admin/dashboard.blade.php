@extends('super_admin.layout')
@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#ede9fe;">
                    <i class="bi bi-building fs-4" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Institutes</div>
                    <div class="fw-bold fs-4">{{ $total }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#dcfce7;">
                    <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Active</div>
                    <div class="fw-bold fs-4 text-success">{{ $active }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#fee2e2;">
                    <i class="bi bi-x-circle-fill fs-4 text-danger"></i>
                </div>
                <div>
                    <div class="text-muted small">Expired</div>
                    <div class="fw-bold fs-4 text-danger">{{ $expired }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3" style="background:#fef9c3;">
                    <i class="bi bi-clock-history fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Expiring Soon</div>
                    <div class="fw-bold fs-4 text-warning">{{ $expiringSoon }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Institutes Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>All Institutes</h6>
        <a href="{{ route('super_admin.institutes.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Institute
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Institute</th>
                        <th>Owner</th>
                        <th>City</th>
                        <th class="text-center">Students</th>
                        <th>Subscription</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($institutes as $i => $inst)
                    @php
                        $isExpired = $inst->subscription_end && now()->gt($inst->subscription_end);
                        $expiringSoon = $inst->subscription_end && !$isExpired && now()->addDays(30)->gte($inst->subscription_end);
                        $subColor = $isExpired ? 'danger' : ($expiringSoon ? 'warning' : 'success');
                        $subIcon  = $isExpired ? 'x-circle' : ($expiringSoon ? 'clock-history' : 'check-circle');
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $inst->name }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $inst->institute_uid }} &bull; {{ $inst->short_name }}
                            </div>
                        </td>
                        <td>
                            <div class="small">{{ $inst->owner_name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $inst->owner_mobile }}</div>
                        </td>
                        <td class="small text-muted">{{ $inst->city }}@if($inst->state), {{ $inst->state }}@endif</td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">
                                {{ number_format($inst->students_count) }} / {{ number_format($inst->student_limit ?? 0) }}
                            </span>
                        </td>
                        <td>
                            @if($inst->subscription_end)
                            <div class="small">
                                <i class="bi bi-{{ $subIcon }} text-{{ $subColor }} me-1"></i>
                                {{ \Carbon\Carbon::parse($inst->subscription_end)->format('d M Y') }}
                            </div>
                            @if($isExpired)
                            <span class="badge bg-danger-subtle text-danger" style="font-size:10px;">Expired</span>
                            @elseif($expiringSoon)
                            <span class="badge bg-warning-subtle text-warning" style="font-size:10px;">Expiring Soon</span>
                            @endif
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($inst->status === 'active')
                            <span class="badge bg-success-subtle text-success px-2">Active</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary px-2">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('super_admin.institutes.show', $inst->id) }}"
                                   class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" title="View">
                                    <i class="bi bi-eye" style="font-size:11px;"></i>
                                </a>
                                <form method="POST" action="{{ route('super_admin.institutes.toggle', $inst->id) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-xs btn-outline-{{ $inst->status === 'active' ? 'danger' : 'success' }} btn-sm py-0 px-2"
                                            title="{{ $inst->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $inst->status === 'active' ? 'slash-circle' : 'check-circle' }}" style="font-size:11px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                            No institutes onboarded yet.
                            <div class="mt-2">
                                <a href="{{ route('super_admin.institutes.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-lg me-1"></i> Add First Institute
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
