@extends('institute.layout')
@section('title', 'Document Notification Settings')
@section('breadcrumb', 'Master / Documents / Notification Settings')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.document-rules.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to Rules
        </a>
        <h4 class="mb-0 fw-bold mt-1">Document Rejection Notification Settings</h4>
        <small class="text-muted">Control when and how rejection notifications are sent to students</small>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-white border-bottom">
                <span class="fw-semibold small"><i class="bi bi-bell me-2 text-info"></i>Notification Configuration</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('master.document-rules.notification-settings.save') }}">
                    @csrf

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="doc_rejection_notify" id="notifyToggle" value="1"
                                   @checked($institute->doc_rejection_notify)
                                   onchange="toggleNotifyOptions(this.checked)">
                            <label class="form-check-label fw-semibold" for="notifyToggle">
                                Enable document rejection notifications
                            </label>
                        </div>
                        <div class="form-text text-muted">
                            When OFF, no notification is sent to the student even when a document is rejected.
                        </div>
                    </div>

                    <div id="notifyOptions" style="{{ $institute->doc_rejection_notify ? '' : 'display:none;' }}">

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Notification Channel <span class="text-danger">*</span></label>
                            @php
                                $activeChannels = $institute->doc_rejection_channels
                                    ? array_map('trim', explode(',', $institute->doc_rejection_channels))
                                    : [];
                            @endphp
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="doc_rejection_channels[]"
                                           value="email" id="channelEmail"
                                           @checked(in_array('email', $activeChannels))>
                                    <label class="form-check-label" for="channelEmail">
                                        <i class="bi bi-envelope me-1"></i>Email
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="doc_rejection_channels[]"
                                           value="sms" id="channelSms"
                                           @checked(in_array('sms', $activeChannels))>
                                    <label class="form-check-label" for="channelSms">
                                        <i class="bi bi-phone me-1"></i>SMS
                                        <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size:0.65rem;">SMS gateway required</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small">Notification Trigger <span class="text-danger">*</span></label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="doc_rejection_trigger"
                                           value="per_document" id="triggerPer"
                                           @checked(($institute->doc_rejection_trigger ?? 'per_document') === 'per_document')>
                                    <label class="form-check-label" for="triggerPer">
                                        <strong>On every document rejection</strong>
                                        <div class="text-muted small">Student is notified immediately whenever admin rejects a document</div>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="doc_rejection_trigger"
                                           value="final_only" id="triggerFinal"
                                           @checked(($institute->doc_rejection_trigger ?? '') === 'final_only')>
                                    <label class="form-check-label" for="triggerFinal">
                                        <strong>Only when admin manually clicks "Send Notification"</strong>
                                        <div class="text-muted small">Admin gets the option to send a notification when rejecting — it does not send automatically</div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Note:</strong> Email notifications require a valid email on the student's admission form.
                            SMS requires the SMS gateway to be configured.
                        </div>
                    </div>

                    <input type="hidden" name="doc_rejection_trigger" value="{{ $institute->doc_rejection_trigger ?? 'per_document' }}" id="hiddenTrigger">

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('master.document-rules.index') }}" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleNotifyOptions(show) {
    document.getElementById('notifyOptions').style.display = show ? '' : 'none';
}
</script>

@endsection
