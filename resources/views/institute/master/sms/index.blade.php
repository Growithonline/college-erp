@extends('institute.layout')
@section('title', 'SMS Settings')
@section('breadcrumb', 'Master / SMS Settings')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">SMS Configuration</h4>
        <small class="text-muted">Configure your SMS provider — notices and due reminders will be sent through this</small>
    </div>
    @if($setting && $setting->isUsable())
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkBalance()">
                <i class="bi bi-wallet2 me-1"></i>Check Balance
            </button>
            <a href="{{ route('master.sms.logs') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list-ul me-1"></i>SMS History
            </a>
        </div>
    @endif
</div>

{{-- Disabled by super admin --}}
@if($setting && $setting->is_sms_disabled)
<div class="alert alert-danger mb-4">
    <i class="bi bi-slash-circle me-2"></i>
    <strong>SMS Disabled:</strong> SMS has been temporarily disabled for this institute by the super admin. Please contact support.
</div>
@endif

<div class="row g-4">

    {{-- Left: Provider Config --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
                <span class="fw-semibold small"><i class="bi bi-phone me-2 text-primary"></i>Provider Configuration</span>
                @if($setting)
                    <span class="badge {{ $setting->isUsable() ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border' }}">
                        {{ $setting->isUsable() ? 'Configured' : 'Inactive' }}
                    </span>
                @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Not Configured</span>
                @endif
            </div>
            <div class="card-body p-4">

                @if(! $setting)
                <div class="alert alert-info small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    Configure SMS to send notices and due reminders to students and staff.
                    <strong>You need a MSG91 or Fast2SMS account</strong> with DLT registration.
                </div>
                @endif

                <form method="POST" action="{{ route('master.sms.save') }}" id="smsForm">
                    @csrf

                    {{-- Provider Select --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">SMS Provider <span class="text-danger">*</span></label>
                        <select name="provider" class="form-select form-select-sm" id="providerSelect">
                            <option value="msg91"     {{ ($setting?->provider ?? 'msg91') === 'msg91'     ? 'selected' : '' }}>MSG91 (Recommended)</option>
                            <option value="fast2sms"  {{ ($setting?->provider) === 'fast2sms'             ? 'selected' : '' }}>Fast2SMS</option>
                            <option value="custom"    {{ ($setting?->provider) === 'custom'               ? 'selected' : '' }}>Custom HTTP Provider (Any Provider)</option>
                        </select>
                    </div>

                    {{-- Standard: API Key (hidden for custom) --}}
                    <div class="mb-3" id="apiKeySection">
                        <label class="form-label small fw-semibold">API Key / Auth Key <span class="text-danger">*</span></label>
                        @if($setting && $setting->provider !== 'custom' && $setting->api_key)
                            <div class="input-group input-group-sm">
                                <input type="password" name="api_key" id="apiKeyInput"
                                       class="form-control form-control-sm"
                                       placeholder="Enter a new key to update">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleApiKey()">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="form-text">Current: <code>{{ $setting->masked_api_key }}</code> — Leave blank to keep the current key</div>
                        @else
                            <input type="text" name="api_key" id="apiKeyInput"
                                   class="form-control form-control-sm"
                                   placeholder="Paste your API key here">
                        @endif
                    </div>

                    {{-- Custom HTTP Fields (shown only when custom selected) --}}
                    <div id="customSection" style="display:none;">
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>
                            Use any SMS provider — configure the URL, method, and body template.
                            Placeholder format: <code>{mobile}</code> <code>{message}</code> <code>{sender_id}</code> + keys from your custom credentials.
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label small fw-semibold">API Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" name="custom_endpoint"
                                       class="form-control form-control-sm"
                                       value="{{ $setting?->custom_endpoint ?? '' }}"
                                       placeholder="https://api.yourprovider.com/send">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-semibold">HTTP Method</label>
                                <select name="custom_method" class="form-select form-select-sm">
                                    <option value="POST" {{ ($setting?->custom_method ?? 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="GET"  {{ ($setting?->custom_method) === 'GET'            ? 'selected' : '' }}>GET</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">
                                Extra Credentials (JSON)
                                <span class="text-muted fw-normal">— username, password, client_code etc.</span>
                            </label>
                            <textarea name="custom_credentials_json" class="form-control form-control-sm font-monospace" rows="3"
                                      placeholder='{"username": "myuser", "password": "mypass", "client_code": "ABC123"}'>{{ $setting?->custom_credentials_json !== '{}' ? $setting?->custom_credentials_json : '' }}</textarea>
                            <div class="form-text">
                                These keys become placeholders: <code>{username}</code> <code>{password}</code> <code>{client_code}</code>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">
                                Body / Query Parameters Template
                            </label>
                            <textarea name="custom_body_template" class="form-control form-control-sm font-monospace" rows="4"
                                      placeholder="Query string format:&#10;username={username}&amp;password={password}&amp;mobile={mobile}&amp;message={message}&amp;sender={sender_id}&#10;&#10;OR JSON format:&#10;{&quot;to&quot;: &quot;{mobile}&quot;, &quot;text&quot;: &quot;{message}&quot;}">{{ $setting?->custom_body_template ?? '' }}</textarea>
                            <div class="form-text">
                                Available placeholders: <code>{mobile}</code> <code>{message}</code> <code>{sender_id}</code> <code>{api_key}</code> + credentials ke keys
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">
                                Request Headers (JSON, optional)
                            </label>
                            <textarea name="custom_headers_json" class="form-control form-control-sm font-monospace" rows="2"
                                      placeholder='{"Authorization": "Bearer {api_key}", "Content-Type": "application/json"}'>{{ $setting?->custom_headers_json ?? '' }}</textarea>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 mb-1">
                                <span class="small fw-semibold">Success Response Check</span>
                                <span class="text-muted small"> — which key-value in the response indicates success?</span>
                            </div>
                            <div class="col-5">
                                <input type="text" name="custom_success_key"
                                       class="form-control form-control-sm"
                                       value="{{ $setting?->custom_success_key ?? '' }}"
                                       placeholder="Response key  e.g. status">
                                <div class="form-text">JSON key (dotted: <code>data.status</code>)</div>
                            </div>
                            <div class="col-1 text-center pt-1"><small class="text-muted">=</small></div>
                            <div class="col-5">
                                <input type="text" name="custom_success_value"
                                       class="form-control form-control-sm"
                                       value="{{ $setting?->custom_success_value ?? '' }}"
                                       placeholder="Expected value  e.g. success">
                                <div class="form-text">Leave blank to check by HTTP 2xx status</div>
                            </div>
                        </div>

                        {{-- Optional API Key for header-based auth --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">
                                API Key <span class="text-muted fw-normal">(optional — only if you use header-based auth)</span>
                            </label>
                            @if($setting && $setting->provider === 'custom' && $setting->api_key)
                            <div class="input-group input-group-sm">
                                <input type="password" name="api_key" id="apiKeyInput"
                                       class="form-control form-control-sm"
                                       placeholder="Enter a new key to update">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleApiKey()">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            <div class="form-text">Current: <code>{{ $setting->masked_api_key }}</code></div>
                            @else
                            <input type="text" name="api_key" id="apiKeyInputCustom"
                                   class="form-control form-control-sm"
                                   placeholder="For the {api_key} placeholder — optional">
                            @endif
                        </div>
                    </div>

                    {{-- Sender ID (always shown) --}}
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Sender ID <span class="text-danger">*</span></label>
                        <input type="text" name="sender_id"
                               class="form-control form-control-sm"
                               value="{{ $setting?->sender_id ?? '' }}"
                               placeholder="e.g. ABCCLG"
                               maxlength="20"
                               style="text-transform:uppercase;">
                        <div class="form-text">
                            DLT registered Sender ID — registered under your institute name.
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

                <div id="testResult" class="mt-3" style="display:none;"></div>
                <div id="balanceResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>

        {{-- Provider Help --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-question-circle me-2 text-info"></i>Provider Setup Guide</span>
            </div>
            <div class="card-body small">
                <div id="msg91Help">
                    <p class="fw-semibold mb-1">MSG91 Setup:</p>
                    <ol class="ps-3 mb-0 text-muted">
                        <li>Create a MSG91 account → complete DLT registration</li>
                        <li>Dashboard → API → copy your Auth Key</li>
                        <li>Register a Sender ID in the DLT Panel (6 characters)</li>
                        <li>Get approval for the Transactional route</li>
                    </ol>
                </div>
                <div id="fast2smsHelp" style="display:none;">
                    <p class="fw-semibold mb-1">Fast2SMS Setup:</p>
                    <ol class="ps-3 mb-0 text-muted">
                        <li>Create a Fast2SMS account → complete KYC</li>
                        <li>Dashboard → Dev API → copy your Authorization key</li>
                        <li>You can use the Quick SMS or Bulk SMS route</li>
                        <li>DLT registration is required for a custom Sender ID</li>
                    </ol>
                </div>
                <div id="customHelp" style="display:none;">
                    <p class="fw-semibold mb-1">Custom HTTP Setup (BulkSMS, Textlocal, Exotel, etc.):</p>
                    <ol class="ps-3 mb-2 text-muted">
                        <li>Check your provider's API documentation</li>
                        <li>Enter the Endpoint URL (e.g. <code>http://sms.bulkssms.com/submitSMS</code>)</li>
                        <li>Add username/password/client_code in Extra Credentials</li>
                        <li>Use <code>{mobile}</code> <code>{message}</code> <code>{username}</code> etc. in the body template</li>
                        <li>Success check: specify the field in the provider's response that indicates success</li>
                    </ol>
                    <p class="fw-semibold mb-1" style="font-size:11px;">BulkSMS.com example:</p>
                    <div class="bg-light rounded p-2 small font-monospace text-muted" style="font-size:10px;line-height:1.6;">
                        Endpoint: <code>http://sms.bulkssms.com/submitSMS</code><br>
                        Method: <code>GET</code><br>
                        Creds: <code>{"username":"u","password":"p","clientcode":"c"}</code><br>
                        Body: <code>username={username}&password={password}&clientcode={clientcode}&mobile={mobile}&message={message}&sender={sender_id}&smstype=4</code><br>
                        Success key: <code>status</code> = <code>success</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Stats + Recent Logs --}}
    <div class="col-lg-6">

        {{-- This Month Stats --}}
        @php
            $noticesSent  = $logsThisMonth->where('type', 'notice')->where('status', 'sent')->sum('total');
            $duesSent     = $logsThisMonth->where('type', 'due_reminder')->where('status', 'sent')->sum('total');
            $failed       = $logsThisMonth->where('status', 'failed')->sum('total');
        @endphp
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="fs-4 fw-bold text-info">{{ number_format($noticesSent) }}</div>
                    <div class="small text-muted">Notices Sent</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="fs-4 fw-bold text-warning">{{ number_format($duesSent) }}</div>
                    <div class="small text-muted">Due Reminders</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($failed) }}</div>
                    <div class="small text-muted">Failed</div>
                </div>
            </div>
        </div>

        {{-- Recent Logs --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
                <span class="fw-semibold small"><i class="bi bi-clock-history me-2"></i>Recent SMS</span>
                @if($setting)
                <a href="{{ route('master.sms.logs') }}" class="small text-decoration-none">View All</a>
                @endif
            </div>
            <div class="card-body p-0">
                @if($recentLogs->isEmpty())
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-chat-square d-block mb-1 fs-4"></i>
                        No SMS sent yet.
                    </div>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Mobile</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td class="text-muted text-nowrap">{{ $log->created_at->format('d M H:i') }}</td>
                                <td>
                                    @php
                                        $typeMap = ['notice' => ['Notice','info'], 'due_reminder' => ['Due Rem.','warning'], 'otp' => ['OTP','secondary']];
                                        [$label, $color] = $typeMap[$log->type] ?? [$log->type, 'light'];
                                    @endphp
                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle">{{ $label }}</span>
                                </td>
                                <td>{{ $log->mobile }}</td>
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
    </div>
</div>

<script>
const providerSelect = document.getElementById('providerSelect');

function onProviderChange() {
    const v = providerSelect.value;
    const isCustom = v === 'custom';

    document.getElementById('apiKeySection').style.display  = isCustom ? 'none' : '';
    document.getElementById('customSection').style.display  = isCustom ? '' : 'none';
    document.getElementById('msg91Help').style.display      = v === 'msg91'     ? '' : 'none';
    document.getElementById('fast2smsHelp').style.display   = v === 'fast2sms'  ? '' : 'none';
    document.getElementById('customHelp').style.display     = isCustom          ? '' : 'none';
}

providerSelect.addEventListener('change', onProviderChange);
onProviderChange(); // init

function toggleApiKey() {
    const input = document.getElementById('apiKeyInput');
    const icon  = document.getElementById('eyeIcon');
    if (!input) return;
    input.type     = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function testConnection() {
    const provider  = providerSelect.value;
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<div class="alert alert-secondary py-2 small"><i class="bi bi-hourglass-split me-1"></i>Testing...</div>';
    resultDiv.style.display = '';

    let payload = { provider };

    if (provider === 'custom') {
        const endpoint = document.querySelector('input[name="custom_endpoint"]')?.value ?? '';
        if (!endpoint) {
            resultDiv.innerHTML = '<div class="alert alert-warning py-2 small">Enter the Endpoint URL first.</div>';
            return;
        }
        payload.custom_endpoint = endpoint;
    } else {
        const apiKey = document.getElementById('apiKeyInput')?.value ?? '';
        if (!apiKey) {
            resultDiv.innerHTML = '<div class="alert alert-warning py-2 small">Enter the API key to test.</div>';
            return;
        }
        payload.api_key   = apiKey;
        payload.sender_id = document.querySelector('input[name="sender_id"]')?.value ?? '';
    }

    const ctrl    = new AbortController();
    const timerId = setTimeout(() => ctrl.abort(), 12000);

    fetch('{{ route('master.sms.test-connection') }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body:    JSON.stringify(payload),
        signal:  ctrl.signal,
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
            ? 'Timeout — server did not respond within 12 seconds.'
            : 'Request failed. Check network.';
        resultDiv.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i>${msg}</div>`;
    });
}

function checkBalance() {
    const resultDiv = document.getElementById('balanceResult');
    resultDiv.innerHTML = '<div class="alert alert-secondary py-2 small"><i class="bi bi-hourglass-split me-1"></i>Fetching balance...</div>';
    resultDiv.style.display = '';

    fetch('{{ route('master.sms.check-balance') }}', {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-wallet2 me-1"></i>Current Balance: <strong>${data.balance}</strong></div>`;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-x-circle me-1"></i>${data.error}</div>`;
        }
    });
}
</script>

@endsection
