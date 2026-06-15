@extends('super_admin.layout')
@section('title', 'SMS Management')
@section('breadcrumb')
    <li class="breadcrumb-item active">SMS Management</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">SMS Management</h4>
        <small class="text-muted">Platform OTP config + institute SMS monitoring</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('super_admin.sms.broadcast') }}" class="btn btn-sm btn-success">
            <i class="bi bi-broadcast me-1"></i>Broadcast SMS
        </a>
        <a href="{{ route('super_admin.sms.analytics') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-bar-chart-line me-1"></i>Analytics
        </a>
    </div>
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-primary">{{ $totalSmsThisMonth }}</div>
            <div class="small text-muted">Total SMS This Month</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-info">{{ $platformOtpThisMonth }}</div>
            <div class="small text-muted">OTP Sent (Your Cost)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-success">{{ $configuredCount }}</div>
            <div class="small text-muted">Institutes Active</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-3">
            <div class="fs-2 fw-bold text-warning">{{ $disabledCount }}</div>
            <div class="small text-muted">Institutes Disabled</div>
        </div>
    </div>
</div>

<div class="row g-4">

    {{-- Left: Platform OTP Settings --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
                <span class="fw-semibold small"><i class="bi bi-shield-lock me-2 text-primary"></i>Platform OTP Settings</span>
                @if($settings)
                    <span class="badge {{ $settings->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                        {{ $settings->is_active ? 'Active' : 'Inactive' }}
                    </span>
                @endif
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('super_admin.sms.save') }}" id="platformSmsForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">SMS Provider <span class="text-danger">*</span></label>
                        <select name="provider" class="form-select form-select-sm" id="providerSelect" onchange="onProviderChange()">
                            <option value="msg91"    {{ ($settings?->provider ?? 'msg91') === 'msg91'    ? 'selected' : '' }}>MSG91</option>
                            <option value="fast2sms" {{ ($settings?->provider) === 'fast2sms'            ? 'selected' : '' }}>Fast2SMS</option>
                            <option value="custom"   {{ ($settings?->provider) === 'custom'              ? 'selected' : '' }}>Custom HTTP Provider (Any Provider)</option>
                        </select>
                    </div>

                    {{-- Standard provider fields --}}
                    <div id="apiKeySection">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Auth Key / API Key</label>
                            @if($settings && $settings->api_key)
                                <div class="input-group input-group-sm">
                                    <input type="password" name="api_key" class="form-control form-control-sm"
                                           placeholder="Enter new key to change (leave blank to keep current)"
                                           id="apiKeyInput">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleApiKey()">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted">Current: <code>{{ $settings->masked_api_key }}</code></div>
                            @else
                                <input type="text" name="api_key" class="form-control form-control-sm"
                                       placeholder="Paste your API key here" id="apiKeyInput">
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Sender ID <span class="text-danger">*</span></label>
                            <input type="text" name="sender_id" class="form-control form-control-sm standard-sender-id"
                                   value="{{ ($settings?->provider !== 'custom') ? ($settings?->sender_id ?? '') : '' }}"
                                   placeholder="e.g. ERPOTP" maxlength="20" style="text-transform:uppercase;">
                            <div class="form-text text-muted">DLT registered sender ID — 6 chars for MSG91</div>
                        </div>
                    </div>

                    {{-- Custom HTTP provider fields --}}
                    <div id="customSection" style="display:none;">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Koi bhi provider support karo</strong> — URL, method, aur body template set karo.<br>
                            Placeholders: <code>{mobile}</code> <code>{message}</code> <code>{sender_id}</code>
                            + credentials JSON ke keys (e.g. <code>{username}</code> <code>{password}</code>)
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">API Endpoint URL <span class="text-danger">*</span></label>
                            <input type="url" name="custom_endpoint" class="form-control form-control-sm"
                                   value="{{ $settings?->custom_endpoint ?? '' }}"
                                   placeholder="https://api.bulkssms.com/sendsms">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-semibold">Method</label>
                                <select name="custom_method" class="form-select form-select-sm">
                                    <option value="POST" {{ ($settings?->custom_method ?? 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="GET"  {{ ($settings?->custom_method) === 'GET'  ? 'selected' : '' }}>GET</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label small fw-semibold">Sender ID <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="sender_id" class="form-control form-control-sm custom-sender-id"
                                       value="{{ ($settings?->provider === 'custom') ? ($settings?->sender_id ?? '') : '' }}"
                                       placeholder="If your provider needs it" maxlength="20" style="text-transform:uppercase;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Body / Params Template</label>
                            <textarea name="custom_body_template" class="form-control form-control-sm font-monospace" rows="3"
                                      placeholder='JSON: {"user":"{username}","pass":"{password}","to":"{mobile}","msg":"{message}"}&#10;OR query string: user={username}&pass={password}&to={mobile}&msg={message}'>{{ $settings?->custom_body_template ?? '' }}</textarea>
                            <div class="form-text text-muted">JSON object ya query string — dono format supported hain</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Success Response Key</label>
                                <input type="text" name="custom_success_key" class="form-control form-control-sm"
                                       value="{{ $settings?->custom_success_key ?? '' }}"
                                       placeholder="e.g. status">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Success Value</label>
                                <input type="text" name="custom_success_value" class="form-control form-control-sm"
                                       value="{{ $settings?->custom_success_value ?? '' }}"
                                       placeholder="e.g. success">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Extra Headers <span class="text-muted fw-normal">(optional JSON)</span></label>
                            <input type="text" name="custom_headers_json" class="form-control form-control-sm font-monospace"
                                   value="{{ $settings?->custom_headers_json ?? '' }}"
                                   placeholder='{"Content-Type":"application/json"}'>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Credentials <span class="text-muted fw-normal">(JSON — encrypted storage)</span></label>
                            <textarea name="custom_credentials_json" class="form-control form-control-sm font-monospace" rows="3"
                                      placeholder='{"username":"myuser","password":"mypass","client_code":"CC123"}'>{{ ($settings?->provider === 'custom' && $settings?->custom_credentials_json !== '{}') ? $settings->custom_credentials_json : '' }}</textarea>
                            <div class="form-text text-muted">
                                Keys yahan daalo → body template mein <code>{key_name}</code> use karo.
                            </div>
                        </div>

                        <div class="alert alert-secondary py-2 small mb-3">
                            <strong>bulkssms.com example:</strong><br>
                            Endpoint: <code>https://bulkssms.com/submitsm</code> &nbsp;|&nbsp; Method: POST<br>
                            Body: <code>user={username}&amp;pass={password}&amp;sid={sender_id}&amp;mobile={mobile}&amp;msg={message}&amp;fl=0&amp;gwid=2</code><br>
                            Credentials: <code>{"username":"YOUR_USER","password":"YOUR_PASS"}</code>
                        </div>
                    </div>

                    <hr class="my-3">
                    <p class="small fw-semibold text-muted mb-2">OTP Behaviour</p>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small">Expiry (min)</label>
                            <input type="number" name="otp_expiry_minutes" class="form-control form-control-sm"
                                   value="{{ $settings?->otp_expiry_minutes ?? 5 }}" min="1" max="60">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Max Attempts</label>
                            <input type="number" name="otp_max_attempts" class="form-control form-control-sm"
                                   value="{{ $settings?->otp_max_attempts ?? 3 }}" min="1" max="10">
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Resend (sec)</label>
                            <input type="number" name="otp_resend_cooldown_seconds" class="form-control form-control-sm"
                                   value="{{ $settings?->otp_resend_cooldown_seconds ?? 30 }}" min="10" max="300">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">OTP Message Template <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="otp_message_template" class="form-control form-control-sm" rows="3"
                                  placeholder="Dear User, Your OTP for login to College ERP portal is {otp}. Please do not share this OTP. Regards, BM Memorial Degree College Team">{{ $settings?->otp_message_template ?? '' }}</textarea>
                        <div class="form-text text-muted">
                            Use <code>{otp}</code> as OTP placeholder. Must match your DLT registered template exactly.
                            Leave blank to use default message.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testConnection()">
                            <i class="bi bi-plug me-1"></i>Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>Save Settings
                        </button>
                    </div>
                </form>

                {{-- Toggle form OUTSIDE platformSmsForm — nested forms are invalid HTML --}}
                @if($settings)
                <form method="POST" action="{{ route('super_admin.sms.toggle-active') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm w-100 {{ $settings->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                        {{ $settings->is_active ? 'Disable OTP SMS' : 'Enable OTP SMS' }}
                    </button>
                </form>
                @endif

                <div id="testResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    {{-- Right: Institute Overview --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
                <span class="fw-semibold small"><i class="bi bi-buildings me-2 text-success"></i>Institute SMS Overview</span>
                <span class="badge bg-secondary-subtle text-secondary border">{{ $instituteStats->count() }} total</span>
            </div>
            <div class="card-body p-0">
                @if($instituteStats->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-phone-vibrate fs-3 d-block mb-2"></i>
                        No active institutes found.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Institute</th>
                                <th>Provider</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">This Month</th>
                                <th>Last Used</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instituteStats as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['institute']->name }}</td>
                                <td>
                                    @if($row['setting'])
                                        <span class="badge bg-light text-dark border">
                                            {{ strtoupper($row['setting']->provider) }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(! $row['setting'])
                                        <span class="badge bg-secondary-subtle text-secondary border">Not Set Up</span>
                                    @elseif($row['setting']->is_sms_disabled)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Disabled</span>
                                    @elseif($row['setting']->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $row['sent_this_month'] > 0 ? number_format($row['sent_this_month']) : '—' }}</td>
                                <td class="text-muted">
                                    {{ $row['last_used'] ? \Carbon\Carbon::parse($row['last_used'])->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    @if($row['setting'])
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('super_admin.sms.institute-logs', $row['institute']->id) }}"
                                           class="btn btn-xs btn-outline-secondary" title="View Logs">
                                            <i class="bi bi-list-ul"></i>
                                        </a>
                                        <form method="POST" action="{{ route('super_admin.sms.toggle-institute', $row['institute']->id) }}">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-xs {{ $row['setting']->is_sms_disabled ? 'btn-outline-success' : 'btn-outline-danger' }}"
                                                title="{{ $row['setting']->is_sms_disabled ? 'Enable SMS' : 'Disable SMS' }}"
                                                onclick="return confirm('Are you sure?')">
                                                <i class="bi {{ $row['setting']->is_sms_disabled ? 'bi-check-lg' : 'bi-slash-circle' }}"></i>
                                            </button>
                                        </form>
                                    </div>
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
    </div>
</div>

<style>
.btn-xs { padding: 2px 6px; font-size: 0.7rem; }
</style>

<script>
function onProviderChange() {
    const provider     = document.getElementById('providerSelect').value;
    const apiKeySection = document.getElementById('apiKeySection');
    const customSection = document.getElementById('customSection');

    if (provider === 'custom') {
        apiKeySection.style.display = 'none';
        customSection.style.display = '';
        // disable standard sender_id so it doesn't conflict
        apiKeySection.querySelectorAll('input,select,textarea').forEach(el => el.disabled = true);
        customSection.querySelectorAll('input,select,textarea').forEach(el => el.disabled = false);
    } else {
        apiKeySection.style.display = '';
        customSection.style.display = 'none';
        apiKeySection.querySelectorAll('input,select,textarea').forEach(el => el.disabled = false);
        customSection.querySelectorAll('input,select,textarea').forEach(el => el.disabled = true);
    }
}

function toggleApiKey() {
    const input = document.getElementById('apiKeyInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function testConnection() {
    const provider  = document.getElementById('providerSelect').value;
    const resultDiv = document.getElementById('testResult');

    resultDiv.innerHTML = '<div class="alert alert-secondary py-2 small"><i class="bi bi-hourglass-split me-1"></i>Testing connection...</div>';
    resultDiv.style.display = '';

    let payload = { provider };

    if (provider === 'custom') {
        const endpoint = document.querySelector('input[name="custom_endpoint"]')?.value;
        if (! endpoint) {
            resultDiv.innerHTML = '<div class="alert alert-warning py-2 small">Endpoint URL daalo pehle.</div>';
            return;
        }
        payload.custom_endpoint = endpoint;
    } else {
        const apiKey   = document.getElementById('apiKeyInput')?.value;
        const senderEl = document.querySelector('.standard-sender-id');
        if (! apiKey) {
            resultDiv.innerHTML = '<div class="alert alert-warning py-2 small">API key daalo pehle.</div>';
            return;
        }
        payload.api_key   = apiKey;
        payload.sender_id = senderEl ? senderEl.value : '';
    }

    const ctrl    = new AbortController();
    const timerId = setTimeout(() => ctrl.abort(), 12000);

    fetch('{{ route('super_admin.sms.test-connection') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload),
        signal: ctrl.signal,
    })
    .then(r => { clearTimeout(timerId); return r.json(); })
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>${data.message}${data.balance ? ' — Balance: <strong>' + data.balance + '</strong>' : ''}</div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i>${data.error || 'Connection failed'}</div>`;
        }
    })
    .catch(err => {
        clearTimeout(timerId);
        const msg = err.name === 'AbortError'
            ? 'Timeout — server 12 seconds mein respond nahi kiya. Server unreachable ho sakta hai.'
            : 'Request failed. Check network.';
        resultDiv.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i>${msg}</div>`;
    });
}

// Init on page load
onProviderChange();
</script>

@endsection
