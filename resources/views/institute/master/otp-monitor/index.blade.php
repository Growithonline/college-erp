@extends('institute.layout')
@section('title', 'Login OTPs')
@section('breadcrumb', 'Master / Security / Login OTPs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Live Login OTPs</h4>
        <small class="text-muted">Currently pending OTPs for Staff, Center & Channel Partner logins</small>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
</div>

<div class="alert alert-warning border-warning d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-shield-exclamation fs-5 mt-1"></i>
    <div class="small">
        Anyone with access to this page can log in as the users listed below. Share an OTP only when the
        user has confirmed by phone that they didn't receive it. Every visit to this page is recorded in
        the audit log.
    </div>
</div>

@if($pending->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-shield-check" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No OTPs pending right now</h5>
        <p class="text-muted small mb-0">This list fills up as Staff, Center & Channel Partner users attempt to log in.</p>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>OTP</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $row)
                <tr>
                    <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle text-capitalize">{{ $row['type'] }}</span></td>
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td class="small text-muted">{{ $row['email'] }}</td>
                    <td>
                        <code class="bg-warning-subtle px-2 py-1 rounded fs-6">{{ $row['otp'] }}</code>
                    </td>
                    <td class="small text-muted">
                        {{ $row['sent_at']?->diffForHumans() ?? '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
    setTimeout(function () { window.location.reload(); }, 20000);
</script>
@endpush
@endsection
