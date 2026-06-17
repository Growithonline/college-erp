@extends('super_admin.layout')
@section('title', 'Institutes')
@section('breadcrumb')
    <li class="breadcrumb-item active">Institutes</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold"><i class="bi bi-building text-primary me-2"></i> All Institutes</h5>
    <a href="{{ route('super_admin.institutes.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Institute
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Institute</th>
                        <th>Owner</th>
                        <th>Contact</th>
                        <th>City</th>
                        <th class="text-center">Students</th>
                        <th>Subscription</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($institutes as $i => $inst)
                    @php
                        $isExpired    = $inst->subscription_end && now()->gt($inst->subscription_end);
                        $expiringSoon = $inst->subscription_end && !$isExpired && now()->addDays(30)->gte($inst->subscription_end);
                        $subColor     = $isExpired ? 'danger' : ($expiringSoon ? 'warning' : 'success');
                    @endphp
                    <tr>
                        <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($inst->image)
                                    <img src="{{ asset('storage/' . $inst->image) }}" alt=""
                                         style="height:28px;width:28px;object-fit:contain;border-radius:4px;border:1px solid #e5e7eb;flex-shrink:0;">
                                @else
                                    <div style="height:28px;width:28px;border-radius:4px;border:1px solid #e5e7eb;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:#f9fafb;">
                                        <i class="bi bi-building text-muted" style="font-size:12px;"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $inst->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $inst->institute_uid }} &bull; {{ $inst->short_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $inst->owner_name }}</td>
                        <td>
                            <div class="small">{{ $inst->mobile }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $inst->email }}</div>
                        </td>
                        <td class="small text-muted">{{ $inst->city }}@if($inst->state), {{ $inst->state }}@endif</td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary">
                                {{ number_format($inst->students_count) }} / {{ number_format($inst->student_limit ?? 0) }}
                            </span>
                        </td>
                        <td class="small">
                            @if($inst->subscription_end)
                                <span class="text-{{ $subColor }}">
                                    {{ \Carbon\Carbon::parse($inst->subscription_end)->format('d M Y') }}
                                </span>
                                @if($isExpired)<div><span class="badge bg-danger-subtle text-danger" style="font-size:10px;">Expired</span></div>
                                @elseif($expiringSoon)<div><span class="badge bg-warning-subtle text-warning" style="font-size:10px;">Expiring Soon</span></div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($inst->hasSmtp())
                                <span class="badge bg-success-subtle text-success border border-success-subtle"
                                      title="Own SMTP verified — emails sent from {{ $inst->smtp_from_email }}"
                                      data-bs-toggle="tooltip">
                                    <i class="bi bi-envelope-check-fill me-1"></i>Own
                                </span>
                            @elseif(filled($inst->smtp_host))
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                      title="SMTP saved but not yet verified"
                                      data-bs-toggle="tooltip">
                                    <i class="bi bi-envelope-exclamation me-1"></i>Unverified
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary"
                                      title="Using platform SMTP"
                                      data-bs-toggle="tooltip">
                                    <i class="bi bi-envelope me-1"></i>Platform
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($inst->status === 'active')
                            <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('super_admin.institutes.show', $inst->id) }}"
                                   class="btn btn-sm btn-outline-primary py-0 px-2" title="View">
                                    <i class="bi bi-eye" style="font-size:11px;"></i>
                                </a>
                                <form method="POST" action="{{ route('super_admin.institutes.toggle', $inst->id) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-{{ $inst->status === 'active' ? 'danger' : 'success' }} py-0 px-2"
                                        title="{{ $inst->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi bi-{{ $inst->status === 'active' ? 'slash-circle' : 'check-circle' }}" style="font-size:11px;"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-building fs-2 d-block mb-2 opacity-25"></i>
                            No institutes found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>

@endsection
