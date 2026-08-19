@extends('institute.layout')
@section('title', 'Review SMS Broadcast')
@section('breadcrumb', 'Master / SMS / Send SMS / Review')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-send me-2 text-primary"></i>Review Broadcast</h4>
        @php($badge = [
            'draft'   => 'bg-secondary-subtle text-secondary',
            'queued'  => 'bg-info-subtle text-info',
            'sending' => 'bg-info-subtle text-info',
            'sent'    => 'bg-success-subtle text-success',
            'failed'  => 'bg-danger-subtle text-danger',
            'partial' => 'bg-warning-subtle text-warning',
        ][$broadcast->status] ?? 'bg-secondary-subtle text-secondary')
        <span class="badge {{ $badge }} border">{{ ucfirst($broadcast->status) }}</span>
    </div>
    <a href="{{ route('master.sms.broadcasts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="text-muted small">Template</div>
                <div class="fw-semibold">
                    {{ $broadcast->smsTemplate->name ?? ucfirst(str_replace('_', ' ', $broadcast->smsTemplate->type ?? '')) }}
                    <span class="text-muted small">({{ ucfirst(str_replace('_', ' ', $broadcast->smsTemplate->type ?? '')) }})</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Audience</div>
                <div class="fw-semibold text-capitalize">{{ $broadcast->audience_type }}</div>
            </div>
        </div>

        <div class="mb-3">
            <div class="text-muted small mb-1">Targeting</div>
            @if(count($targetingLabels))
                <ul class="mb-0 small">
                    @foreach($targetingLabels as $label)<li>{{ $label }}</li>@endforeach
                </ul>
            @else
                <div class="small">No restriction — sabhi active {{ $broadcast->audience_type }}</div>
            @endif
            @if($broadcast->recipient_mode === 'specific')
                <div class="small text-muted mt-1">Sirf specifically selected recipients ({{ count($broadcast->specific_recipient_ids ?? []) }})</div>
            @endif
        </div>

        <div class="mb-3">
            <div class="text-muted small mb-1">Sample Message <span class="fw-normal">([bracketed] = us recipient ka apna data)</span></div>
            <div class="p-2 rounded bg-light border small" style="white-space:pre-wrap;">{{ $sampleText }}</div>
        </div>

        <div class="p-3 rounded" style="background:#eff6ff;border:1.5px dashed #bfdbfe;">
            <i class="bi bi-people me-1 text-primary"></i>
            <span class="fw-semibold">No. of recipients:</span>
            <span class="fw-bold text-primary">{{ $recipientCount }}</span>
        </div>

        @if($broadcast->linkedNotice)
        <div class="mt-3 small text-muted">
            <i class="bi bi-megaphone me-1"></i>In-app Notice bhi post hua: <strong>{{ $broadcast->linkedNotice->title }}</strong>
        </div>
        @elseif($broadcast->notice_title)
        <div class="mt-3 small text-muted">
            <i class="bi bi-megaphone me-1"></i>Send karne pe ek in-app Notice bhi post hogi: <strong>{{ $broadcast->notice_title }}</strong>
        </div>
        @endif

        @if(in_array($broadcast->status, ['sent', 'failed', 'partial']))
        <div class="mt-3 small">
            <span class="text-success">{{ $broadcast->sent_count }} sent</span>
            @if($broadcast->failed_count)
                &nbsp;|&nbsp;<span class="text-danger">{{ $broadcast->failed_count }} failed</span>
            @endif
            &nbsp;|&nbsp;<span class="text-muted">{{ $broadcast->sent_at?->format('d M Y, h:i A') }}</span>
        </div>
        @endif

    </div>
</div>

@if($broadcast->status === 'draft')
<div class="d-flex gap-2">
    <form method="POST" action="{{ route('master.sms.broadcasts.send', $broadcast) }}"
          onsubmit="return confirm('{{ $recipientCount }} recipients ko SMS bhejna confirm karo? Ye undo nahi ho sakta.');">
        @csrf
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill me-1"></i>Confirm &amp; Send</button>
    </form>
    <form method="POST" action="{{ route('master.sms.broadcasts.destroy', $broadcast) }}"
          onsubmit="return confirm('Ye draft delete kar du?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger px-4"><i class="bi bi-trash me-1"></i>Discard Draft</button>
    </form>
</div>
@elseif($broadcast->status === 'queued' || $broadcast->status === 'sending')
<div class="alert alert-info mb-0">SMS bhej jaa rahe hain — thodi der me status update hoga (page reload karo).</div>
@endif

@if($deliveryLogs !== null)
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-list-ul me-1"></i>Delivery Log</span>
        <a href="{{ route('master.sms.logs', ['broadcast' => $broadcast->id]) }}" class="small">Open in SMS History</a>
    </div>
    @if($deliveryLogs->isEmpty())
        <div class="text-center py-4 text-muted small">Abhi tak koi delivery attempt record nahi hua.</div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Mobile</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveryLogs as $log)
                    <tr>
                        <td class="text-muted text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $log->mobile }}</td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                                <i class="bi bi-check-circle-fill text-success" title="Sent"></i>
                            @elseif($log->status === 'failed')
                                <i class="bi bi-x-circle-fill text-danger" title="Failed"></i>
                            @else
                                <i class="bi bi-hourglass-split text-muted" title="Pending"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $deliveryLogs->links() }}</div>
    @endif
</div>
@endif

@endsection
