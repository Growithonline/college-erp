@extends('institute.layout')
@section('title', 'Due Payment Reminders')
@section('breadcrumb', 'Master / SMS / Due Reminders')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS Settings
        </a>
        <h4 class="mb-0 fw-bold mt-1">Due Payment Reminders</h4>
        <small class="text-muted">Send automatic SMS to students with pending fee dues</small>
    </div>
    @if($setting)
    <form method="POST" action="{{ route('master.sms.reminders.toggle') }}">
        @csrf
        <button type="submit" class="btn btn-sm {{ $setting->is_enabled ? 'btn-danger' : 'btn-success' }}">
            <i class="bi {{ $setting->is_enabled ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
            {{ $setting->is_enabled ? 'Disable Reminders' : 'Enable Reminders' }}
        </button>
    </form>
    @endif
</div>

@if(! $smsConfigured)
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>SMS Provider not configured.</strong>
    <a href="{{ route('master.sms.index') }}" class="alert-link">Configure SMS provider</a> first — reminders will not be sent until then.
</div>
@endif

@if($setting && $setting->is_enabled)
<div class="alert alert-success mb-4">
    <i class="bi bi-check-circle me-2"></i>
    <strong>Reminders Active</strong> — Running daily at {{ \Carbon\Carbon::parse($setting->send_time)->format('h:i A') }}.
    Trigger days: <strong>{{ implode(', ', $setting->trigger_days_array) }}</strong> days after due date.
</div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-gear me-2 text-warning"></i>Reminder Configuration</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.sms.reminders.save') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">When to Send? <span class="text-danger">*</span></label>
                        <div class="form-text mb-2">Select how many days after the due date an SMS should be sent (0 = on the due date):</div>
                        @php
                            $selectedDays = $setting ? $setting->trigger_days_array : [0, 3, 7];
                            $dayOptions = [
                                0  => 'On due date',
                                1  => '1 day after',
                                3  => '3 days after',
                                5  => '5 days after',
                                7  => '7 days after',
                                14 => '14 days after',
                                30 => '30 days after',
                            ];
                        @endphp
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($dayOptions as $day => $label)
                            <div class="form-check form-check-inline border rounded px-3 py-2 {{ in_array($day, $selectedDays) ? 'bg-warning-subtle border-warning' : 'bg-light' }}">
                                <input class="form-check-input" type="checkbox"
                                       name="trigger_days[]" id="day_{{ $day }}" value="{{ $day }}"
                                       {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="day_{{ $day }}">
                                    {{ $label }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('trigger_days')<div class="text-danger mt-1 small">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Daily Run Time <span class="text-danger">*</span></label>
                        <input type="time" name="send_time" class="form-control form-control-sm" style="width:140px;"
                               value="{{ $setting ? \Carbon\Carbon::parse($setting->send_time)->format('H:i') : '09:00' }}">
                        <div class="form-text">Reminders will be automatically checked and sent at this time.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Message Template <span class="text-danger">*</span></label>
                        <textarea name="message_template" class="form-control form-control-sm"
                                  rows="3" maxlength="500"
                                  placeholder="{{ \App\Models\SmsDueReminderSetting::defaultTemplate() }}">{{ old('message_template', $setting?->message_template ?? \App\Models\SmsDueReminderSetting::defaultTemplate()) }}</textarea>
                        <div id="charCount" class="form-text">Characters: <span id="charNum">0</span>/160 (1 SMS)</div>
                    </div>

                    <div class="card border-0 bg-info-subtle p-3 mb-4 rounded-3">
                        <p class="small fw-semibold mb-1">Available Variables:</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['{name}' => 'Student name', '{amount}' => 'Pending amount', '{due_date}' => 'Due date', '{institute_name}' => 'Institute name', '{course}' => 'Course name'] as $var => $desc)
                            <span class="badge bg-white text-dark border" style="cursor:pointer;" onclick="insertVar('{{ $var }}')" title="{{ $desc }}">{{ $var }}</span>
                            @endforeach
                        </div>
                        <div class="form-text mt-1">Click a badge to insert a variable into the template.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-save me-1"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-eye me-2"></i>Preview</span>
            </div>
            <div class="card-body p-3">
                <div class="rounded-3 p-3" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:13px;line-height:1.6;" id="previewBox">
                    {{ $setting?->message_template ?? \App\Models\SmsDueReminderSetting::defaultTemplate() }}
                </div>
                <div class="mt-3 small text-muted">
                    <p class="fw-semibold mb-1">Sample (with variables replaced):</p>
                    <div class="rounded-3 p-3 bg-success-subtle" id="sampleBox" style="font-size:12px;line-height:1.6;color:#166534;">
                        Loading preview...
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold small"><i class="bi bi-info-circle me-2 text-info"></i>How it works</span>
            </div>
            <div class="card-body small text-muted p-3">
                <ol class="ps-3 mb-0">
                    <li class="mb-1">Every day the system checks for students with pending fee dues</li>
                    <li class="mb-1">Students whose due date matches the selected <strong>trigger days</strong> will receive an SMS</li>
                    <li class="mb-1">SMS is sent via the institute's configured SMS provider</li>
                    <li>Logged in SMS History as type "Due Reminder"</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
const templateEl = document.querySelector('textarea[name="message_template"]');
const charNum    = document.getElementById('charNum');
const previewBox = document.getElementById('previewBox');
const sampleBox  = document.getElementById('sampleBox');

const sampleVars = {
    '{name}': 'Rahul Kumar',
    '{amount}': '5,200',
    '{due_date}': '15 Jun 2026',
    '{institute_name}': 'ABC College',
    '{course}': 'B.Com',
};

function updatePreview() {
    const val = templateEl.value;
    charNum.textContent = val.length;
    document.getElementById('charCount').style.color = val.length > 160 ? '#dc2626' : '';
    previewBox.textContent = val;

    let sample = val;
    Object.entries(sampleVars).forEach(([k, v]) => {
        sample = sample.replaceAll(k, v);
    });
    sampleBox.textContent = sample || '(empty)';
}

function insertVar(v) {
    const pos = templateEl.selectionStart;
    const before = templateEl.value.substring(0, pos);
    const after  = templateEl.value.substring(pos);
    templateEl.value = before + v + after;
    templateEl.focus();
    templateEl.setSelectionRange(pos + v.length, pos + v.length);
    updatePreview();
}

templateEl.addEventListener('input', updatePreview);
updatePreview();
</script>

@endsection
