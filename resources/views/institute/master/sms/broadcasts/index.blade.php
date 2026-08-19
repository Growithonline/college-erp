@extends('institute.layout')
@section('title', 'Send SMS')
@section('breadcrumb', 'Master / SMS / Send SMS')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS Settings
        </a>
        <h4 class="mb-0 fw-bold mt-1">Send SMS</h4>
        <small class="text-muted">Bulk SMS using your registered DLT templates — targeted by course, stream, semester or staff role</small>
    </div>
    <a href="{{ route('master.sms.broadcasts.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Broadcast
    </a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Template</th>
                    <th>Audience</th>
                    <th>Recipients</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($broadcasts as $b)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $b->smsTemplate->name ?? ucfirst(str_replace('_', ' ', $b->smsTemplate->type ?? '')) }}</div>
                            <span class="text-muted small">{{ ucfirst(str_replace('_', ' ', $b->smsTemplate->type ?? '')) }}</span>
                        </td>
                        <td class="small text-capitalize">{{ $b->audience_type }}</td>
                        <td class="small">
                            {{ $b->sent_count }}/{{ $b->total_recipients }}
                            @if($b->failed_count)
                                <span class="text-danger">({{ $b->failed_count }} failed)</span>
                            @endif
                        </td>
                        <td>
                            @php($badge = [
                                'draft'   => 'bg-secondary-subtle text-secondary',
                                'queued'  => 'bg-info-subtle text-info',
                                'sending' => 'bg-info-subtle text-info',
                                'sent'    => 'bg-success-subtle text-success',
                                'failed'  => 'bg-danger-subtle text-danger',
                                'partial' => 'bg-warning-subtle text-warning',
                            ][$b->status] ?? 'bg-secondary-subtle text-secondary')
                            <span class="badge {{ $badge }} border">{{ ucfirst($b->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $b->created_at->format('d M Y, h:i A') }}</td>
                        <td class="text-end">
                            <a href="{{ route('master.sms.broadcasts.show', $b) }}" class="btn btn-outline-primary btn-sm">
                                {{ $b->status === 'draft' ? 'Review & Send' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted small py-4">
                            Koi SMS broadcast nahi bheja gaya abhi tak — "New Broadcast" pe click karke shuru karo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($broadcasts->hasPages())
<div class="mt-3">{{ $broadcasts->links() }}</div>
@endif

@endsection
