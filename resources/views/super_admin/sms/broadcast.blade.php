@extends('super_admin.layout')
@section('title', 'Broadcast SMS')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('super_admin.sms.index') }}">SMS Management</a></li>
    <li class="breadcrumb-item active">Broadcast SMS</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Broadcast SMS</h4>
        <small class="text-muted">All institutes ya single institute ko platform account se SMS bhejo</small>
    </div>
    <a href="{{ route('super_admin.sms.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 mb-4">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 mb-4">
        <i class="bi bi-x-circle me-2"></i>{{ session('error') }}
    </div>
@endif

@if(! $platformConfigured)
    <div class="alert alert-warning border-0 mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Platform SMS configured nahi hai.</strong>
        <a href="{{ route('super_admin.sms.index') }}" class="alert-link ms-1">SMS Settings mein provider set karo</a>
        — broadcast SMS tab hi send ho sakta hai.
    </div>
@endif

<div class="row g-4">

    {{-- Left: Compose --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="bi bi-broadcast me-2 text-primary"></i>Compose Broadcast</span>
            </div>
            <div class="card-body p-4">

                <form method="POST" action="{{ route('super_admin.sms.broadcast.send') }}" id="broadcastForm">
                    @csrf

                    {{-- Target --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Target <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target" id="targetAll"
                                       value="all" {{ old('target', 'all') === 'all' ? 'checked' : '' }}
                                       onchange="toggleTarget()">
                                <label class="form-check-label" for="targetAll">
                                    <i class="bi bi-broadcast me-1 text-primary"></i>
                                    All Active Institutes
                                    <span class="badge bg-primary-subtle text-primary border ms-1">{{ $institutes->count() }} with mobile</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="target" id="targetSingle"
                                       value="single" {{ old('target') === 'single' ? 'checked' : '' }}
                                       onchange="toggleTarget()">
                                <label class="form-check-label" for="targetSingle">
                                    <i class="bi bi-building me-1 text-success"></i> Specific Institute
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Institute Select (shown only for single) --}}
                    <div class="mb-3" id="instituteSelectDiv" style="display:none;">
                        <label class="form-label fw-semibold small">Select Institute <span class="text-danger">*</span></label>
                        <select name="institute_id" class="form-select form-select-sm" id="instituteSelect"
                                onchange="updatePreview()">
                            <option value="">— Select institute —</option>
                            @foreach($institutes as $inst)
                                <option value="{{ $inst->id }}"
                                        data-name="{{ $inst->name }}"
                                        data-owner="{{ $inst->owner_name }}"
                                        data-uid="{{ $inst->institute_uid }}"
                                        data-expiry="{{ $inst->subscription_end ? $inst->subscription_end->format('d M Y') : 'N/A' }}"
                                        data-mobile="{{ $inst->owner_mobile }}"
                                        {{ old('institute_id') == $inst->id ? 'selected' : '' }}>
                                    {{ $inst->name }} — {{ $inst->owner_mobile }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Message Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Message Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select form-select-sm" id="messageType">
                            <option value="broadcast"            {{ old('type','broadcast') === 'broadcast'            ? 'selected':'' }}>General Broadcast / Notice</option>
                            <option value="subscription_expiry"  {{ old('type') === 'subscription_expiry'              ? 'selected':'' }}>Subscription Expiry Warning</option>
                            <option value="payment_reminder"     {{ old('type') === 'payment_reminder'                 ? 'selected':'' }}>Payment Reminder</option>
                        </select>
                    </div>

                    {{-- Quick Templates --}}
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Quick Templates</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="applyTemplate('Dear {owner_name}, your College ERP subscription expires on {subscription_end}. Please renew to avoid interruption. Institute ID: {institute_id}')">
                                Subscription Expiry
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="applyTemplate('Dear {owner_name}, your College ERP payment is pending. Please clear dues to continue uninterrupted service. Contact support for help. Institute: {institute_name}')">
                                Payment Reminder
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="applyTemplate('Important notice from Gaurangi Technologies: ')">
                                Custom Notice
                            </button>
                        </div>
                    </div>

                    {{-- Message --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">
                            Message <span class="text-danger">*</span>
                            <span class="text-muted fw-normal ms-1">— max 500 chars</span>
                        </label>
                        <textarea name="message" id="messageText" class="form-control form-control-sm font-monospace"
                                  rows="5" maxlength="500" required
                                  oninput="updateCounter(); updatePreview()"
                                  placeholder="Type your message here...">{{ old('message') }}</textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div class="form-text">
                                Variables: <code>{institute_name}</code> <code>{owner_name}</code>
                                <code>{institute_id}</code> <code>{subscription_end}</code>
                            </div>
                            <small id="charCounter" class="text-muted">0 / 160</small>
                        </div>
                        @error('message')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary"
                            {{ ! $platformConfigured ? 'disabled' : '' }}
                            onclick="return confirmSend()">
                        <i class="bi bi-send me-1"></i>Send SMS
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Preview + Info --}}
    <div class="col-lg-5">

        {{-- Live Preview --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-phone me-2 text-success"></i>Live Preview</span>
            </div>
            <div class="card-body p-3">
                <div style="background:#e9ecef;border-radius:12px;padding:14px 16px;font-size:13px;line-height:1.6;min-height:80px;color:#212529;" id="previewBox">
                    <span class="text-muted">Message type karo preview dekhne ke liye...</span>
                </div>
                <div class="mt-2 d-flex justify-content-between small text-muted">
                    <span>Preview (sample data)</span>
                    <span id="smsUnits">0 chars</span>
                </div>
            </div>
        </div>

        {{-- Variable Guide --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-braces me-2 text-info"></i>Available Variables</span>
            </div>
            <div class="card-body p-3 small">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr><td><code>{institute_name}</code></td><td class="text-muted">Institute ka naam</td></tr>
                        <tr><td><code>{owner_name}</code></td><td class="text-muted">Owner ka naam</td></tr>
                        <tr><td><code>{institute_id}</code></td><td class="text-muted">Unique ID (e.g. GT/2026/0001)</td></tr>
                        <tr><td><code>{subscription_end}</code></td><td class="text-muted">Subscription expiry date</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-info-circle me-2"></i>Platform SMS Info</span>
            </div>
            <div class="card-body p-3 small">
                @if($platformConfigured)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Platform SMS <strong>configured & active</strong> hai</span>
                    </div>
                @else
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <span>Platform SMS <strong>configured nahi</strong> hai</span>
                    </div>
                @endif
                <div class="text-muted" style="font-size:11px;line-height:1.7;">
                    <i class="bi bi-info-circle me-1"></i>
                    Broadcast SMS platform ke SMS account se jaata hai — institute ke account se nahi.<br>
                    Bulk send ke liye queue use hoti hai — tatkaal nahi, thoda time lagta hai.<br>
                    Har SMS ka log "Broadcast History" mein dikha hai.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Broadcast History --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
        <span class="fw-semibold small"><i class="bi bi-clock-history me-2"></i>Recent Broadcast / Welcome SMS History</span>
    </div>
    <div class="card-body p-0">
        @if($recentBroadcasts->isEmpty())
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-broadcast d-block mb-1 fs-4"></i>
                Abhi tak koi broadcast nahi bheja gaya.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Institute</th>
                        <th>Mobile</th>
                        <th>Message</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentBroadcasts as $log)
                    <tr>
                        <td class="text-muted text-nowrap">{{ $log->created_at->format('d M H:i') }}</td>
                        <td>
                            @php
                                $tmap = [
                                    'broadcast'           => ['Broadcast', 'primary'],
                                    'welcome'             => ['Welcome', 'success'],
                                    'subscription_expiry' => ['Sub Expiry', 'warning'],
                                    'payment_reminder'    => ['Pay Reminder', 'info'],
                                ];
                                [$tlabel, $tcolor] = $tmap[$log->type] ?? [$log->type, 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $tcolor }}-subtle text-{{ $tcolor }} border border-{{ $tcolor }}-subtle">
                                {{ $tlabel }}
                            </span>
                        </td>
                        <td>{{ $log->institute?->name ?? '—' }}</td>
                        <td>{{ $log->mobile }}</td>
                        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="{{ $log->message }}">
                            {{ $log->message }}
                        </td>
                        <td class="text-center">
                            @if($log->status === 'sent')
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @elseif($log->status === 'failed')
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            @else
                                <i class="bi bi-hourglass-split text-muted"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<script>
// Sample data for preview (first institute from list)
const sampleData = {
    institute_name:  '{{ $institutes->first()?->name ?? "ABC College" }}',
    owner_name:      '{{ $institutes->first()?->owner_name ?? "Ramesh Kumar" }}',
    institute_id:    '{{ $institutes->first()?->institute_uid ?? "GT/2026/0001" }}',
    subscription_end:'{{ $institutes->first()?->subscription_end?->format("d M Y") ?? "31 Dec 2026" }}',
};

function toggleTarget() {
    const isSingle = document.getElementById('targetSingle').checked;
    document.getElementById('instituteSelectDiv').style.display = isSingle ? '' : 'none';
    document.getElementById('instituteSelect').required = isSingle;
    updatePreview();
}

function applyTemplate(text) {
    document.getElementById('messageText').value = text;
    updateCounter();
    updatePreview();
}

function updateCounter() {
    const text  = document.getElementById('messageText').value;
    const len   = text.length;
    const units = len <= 160 ? 1 : Math.ceil(len / 153);
    document.getElementById('charCounter').textContent = `${len} / 160 (${units} SMS)`;
    document.getElementById('charCounter').className = len > 160 ? 'text-warning small' : 'text-muted small';
}

function updatePreview() {
    const text = document.getElementById('messageText').value;
    if (!text.trim()) {
        document.getElementById('previewBox').innerHTML = '<span class="text-muted">Message type karo preview dekhne ke liye...</span>';
        document.getElementById('smsUnits').textContent = '0 chars';
        return;
    }

    // Use selected institute data for preview if single target
    let data = { ...sampleData };
    if (document.getElementById('targetSingle').checked) {
        const sel = document.getElementById('instituteSelect');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            data = {
                institute_name:   opt.dataset.name  || sampleData.institute_name,
                owner_name:       opt.dataset.owner  || sampleData.owner_name,
                institute_id:     opt.dataset.uid    || sampleData.institute_id,
                subscription_end: opt.dataset.expiry || sampleData.subscription_end,
            };
        }
    }

    let preview = text;
    for (const [k, v] of Object.entries(data)) {
        preview = preview.replaceAll('{' + k + '}', `<strong>${v}</strong>`);
    }

    document.getElementById('previewBox').innerHTML = preview;
    document.getElementById('smsUnits').textContent = `${text.length} chars`;
}

function confirmSend() {
    const target = document.querySelector('input[name="target"]:checked')?.value;
    const count  = {{ $institutes->count() }};
    if (target === 'all') {
        return confirm(`${count} institutes ko SMS bhejna hai. Confirm karein?`);
    }
    return true;
}

// Init
toggleTarget();
updateCounter();
updatePreview();
</script>

@endsection
