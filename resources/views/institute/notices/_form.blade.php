@if($errors->any())
<div class="alert alert-danger border-0 mb-4">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    {{-- Title --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $notice->title ?? '') }}" required maxlength="255"
               placeholder="Notice ka title likhо">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Type --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
        <select name="notice_type" class="form-select @error('notice_type') is-invalid @enderror" required>
            @foreach($types as $val => $label)
                <option value="{{ $val }}" {{ old('notice_type', $notice->notice_type ?? 'general') === $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('notice_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Visible To --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Visible To <span class="text-danger">*</span></label>
        @php
            $selectedVT = old('visible_to',
                isset($notice) ? (array) $notice->visible_to : array_keys(\App\Models\Notice::VISIBLE_TO)
            );
        @endphp
        <div class="border rounded p-2 @error('visible_to') border-danger @enderror" style="background:#f8fafc;">
            <div class="d-flex flex-wrap gap-3">
                @foreach($visibleTo as $val => $label)
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox"
                           name="visible_to[]" id="vt_{{ $val }}" value="{{ $val }}"
                           {{ in_array($val, $selectedVT) ? 'checked' : '' }}>
                    <label class="form-check-label" for="vt_{{ $val }}" style="font-size:13px;">
                        {{ $label }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>
        @error('visible_to')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
    </div>

    {{-- Notice Date --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Notice Date <span class="text-danger">*</span></label>
        <input type="date" name="notice_date" class="form-control @error('notice_date') is-invalid @enderror"
               value="{{ old('notice_date', isset($notice) ? $notice->notice_date->toDateString() : now()->toDateString()) }}" required>
        @error('notice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Expires At --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Expires On</label>
        <input type="date" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror"
               value="{{ old('expires_at', isset($notice) && $notice->expires_at ? $notice->expires_at->toDateString() : '') }}">
        <div class="form-text">Blank chhodo agar kabhi expire na ho</div>
        @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Scheduled At --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Schedule Publish</label>
        <input type="datetime-local" name="scheduled_at" class="form-control @error('scheduled_at') is-invalid @enderror"
               value="{{ old('scheduled_at', isset($notice) && $notice->scheduled_at ? $notice->scheduled_at->format('Y-m-d\TH:i') : '') }}">
        <div class="form-text">Blank = abhi publish, future date = scheduled</div>
        @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Pin --}}
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-1">
            <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" value="1"
                   {{ old('is_pinned', $notice->is_pinned ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_pinned">
                <i class="bi bi-pin-angle me-1 text-warning"></i> Pin this notice
            </label>
        </div>
    </div>

    {{-- Body --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Body <span class="text-danger">*</span></label>
        <textarea name="body" class="form-control @error('body') is-invalid @enderror"
                  rows="7" required maxlength="10000"
                  placeholder="Notice ka content likhо...">{{ old('body', $notice->body ?? '') }}</textarea>
        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Attachment --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Attachment <small class="text-muted">(PDF / Image, max 4MB)</small></label>
        <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror"
               accept=".pdf,.jpg,.jpeg,.png">
        @if(isset($notice) && $notice->attachment)
            <div class="form-text">
                <i class="bi bi-paperclip me-1"></i>
                Current:
                <a href="{{ Storage::url($notice->attachment) }}" target="_blank">View attachment</a>
                &nbsp;<span class="text-muted">(nayi file upload karo to replace ho jaegi)</span>
            </div>
        @endif
        @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Email Notification --}}
    <div class="col-12">
        <div class="card border-0 rounded-3 p-3" style="background:#f8fafc;border:1.5px dashed #e2e8f0 !important;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="send_email" id="send_email"
                           value="1" onchange="toggleEmailRoles(this)"
                           {{ old('send_email') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="send_email">
                        <i class="bi bi-envelope me-1 text-primary"></i> Email notification bhejna hai?
                    </label>
                </div>
                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px;">
                    Default: OFF
                </span>
            </div>
            <div class="form-text mb-2" style="font-size:11px;color:#94a3b8;">
                <i class="bi bi-info-circle me-1"></i>
                Email sirf tab bheja jaega jab aap yahan enable karein. SMTP charge lagta hai isliye soch samajhkar use karein.
            </div>

            <div id="emailRolesSection" style="display:{{ old('send_email') ? 'block' : 'none' }};">
                <div class="mb-1" style="font-size:12px;font-weight:600;color:#475569;">
                    Who should receive the email?
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    @php
                        $selectedRoles = old('email_roles', isset($notice) && $notice->email_to
                            ? explode(',', $notice->email_to) : []);
                    @endphp
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="email_roles[]"
                               id="email_staff" value="staff"
                               {{ in_array('staff', $selectedRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_staff" style="font-size:13px;">
                            <i class="bi bi-person-badge me-1 text-success"></i> Staff
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="email_roles[]"
                               id="email_center" value="center"
                               {{ in_array('center', $selectedRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_center" style="font-size:13px;">
                            <i class="bi bi-building me-1 text-primary"></i> Centers
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="email_roles[]"
                               id="email_channel" value="channel"
                               {{ in_array('channel', $selectedRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_channel" style="font-size:13px;">
                            <i class="bi bi-diagram-3 me-1 text-warning"></i> Channel Partners
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="email_roles[]"
                               id="email_student" value="student"
                               {{ in_array('student', $selectedRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_student" style="font-size:13px;">
                            <i class="bi bi-mortarboard me-1 text-danger"></i> Students
                            <small class="text-muted">(active, with email)</small>
                        </label>
                    </div>
                </div>
                @error('email_roles')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

    {{-- SMS Notification — sirf naye notice pe, edit pe nahi (update mein job dispatch nahi hoti) --}}
    @unless(isset($notice))
    <div class="col-12">
        <div class="card border-0 rounded-3 p-3" style="background:#f0fdf4;border:1.5px dashed #bbf7d0 !important;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="send_sms" id="send_sms"
                           value="1" onchange="toggleSmsRoles(this)"
                           {{ old('send_sms') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="send_sms">
                        <i class="bi bi-phone me-1 text-success"></i> SMS notification bhejna hai?
                    </label>
                </div>
                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px;">
                    Default: OFF
                </span>
            </div>
            <div class="form-text mb-2" style="font-size:11px;color:#94a3b8;">
                <i class="bi bi-info-circle me-1"></i>
                SMS sirf tab jaega jab institute ka SMS provider configured ho. Bulk SMS queue mein process hoga.
            </div>

            <div id="smsRolesSection" style="display:{{ old('send_sms') ? 'block' : 'none' }};">
                <div class="mb-1" style="font-size:12px;font-weight:600;color:#475569;">
                    Kisko SMS bhejna hai?
                </div>
                <div class="d-flex gap-3 flex-wrap">
                    @php
                        $selectedSmsRoles = old('sms_roles', isset($notice) && $notice->sms_to
                            ? explode(',', $notice->sms_to) : []);
                    @endphp
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sms_roles[]"
                               id="sms_staff" value="staff"
                               {{ in_array('staff', $selectedSmsRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sms_staff" style="font-size:13px;">
                            <i class="bi bi-person-badge me-1 text-success"></i> Staff
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sms_roles[]"
                               id="sms_students" value="students"
                               {{ in_array('students', $selectedSmsRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sms_students" style="font-size:13px;">
                            <i class="bi bi-mortarboard me-1 text-primary"></i> Students
                            <small class="text-muted">(active students with mobile)</small>
                        </label>
                    </div>
                </div>

                @php
                    $smsCost = null;
                    if(isset($notice) && $notice->institute_id) {
                        $staffCount    = \App\Models\StaffMember::where('institute_id', $notice->institute_id)->where('status', true)->whereNotNull('mobile')->count();
                        $studentCount  = \App\Models\Student::where('institute_id', $notice->institute_id)->whereNotNull('mobile')->count();
                    }
                @endphp
                @if(isset($staffCount))
                <div class="mt-2 p-2 rounded" style="background:#f8fafc;font-size:11px;color:#64748b;">
                    <i class="bi bi-info-circle me-1"></i>
                    Staff with mobile: <strong>{{ $staffCount }}</strong> &nbsp;|&nbsp;
                    Students with mobile: <strong>{{ $studentCount }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endunless
</div>

@push('scripts')
<script>
function toggleEmailRoles(checkbox) {
    document.getElementById('emailRolesSection').style.display = checkbox.checked ? 'block' : 'none';
}
function toggleSmsRoles(checkbox) {
    document.getElementById('smsRolesSection').style.display = checkbox.checked ? 'block' : 'none';
}
</script>
@endpush
