@extends('institute.layout')
@section('title', 'New SMS Broadcast')
@section('breadcrumb', 'Master / SMS / Send SMS / New')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-send me-2 text-primary"></i>New SMS Broadcast</h4>
        <small class="text-muted">Select a registered DLT template, fill in its variables, and target your audience.</small>
    </div>
    <a href="{{ route('master.sms.broadcasts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger border-0 mb-4">
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('master.sms.broadcasts.store') }}" id="broadcastForm">
@csrf

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-4">

        {{-- Audience --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Who should receive this? <span class="text-danger">*</span></label>
            @php($audience = old('audience_type', 'student'))
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="audience_type" id="aud_student" value="student" {{ $audience === 'student' ? 'checked' : '' }}>
                    <label class="form-check-label" for="aud_student"><i class="bi bi-mortarboard me-1 text-primary"></i>Students</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="audience_type" id="aud_staff" value="staff" {{ $audience === 'staff' ? 'checked' : '' }}>
                    <label class="form-check-label" for="aud_staff"><i class="bi bi-person-badge me-1 text-success"></i>Staff</label>
                </div>
            </div>
        </div>

        {{-- Template --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Template <span class="text-danger">*</span></label>
            <select name="sms_template_id" id="templateSelect" class="form-select @error('sms_template_id') is-invalid @enderror" required>
                <option value="">— Select a registered template —</option>
                @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}" {{ (string) old('sms_template_id') === (string) $tpl->id ? 'selected' : '' }}>
                        {{ $typeLabels[$tpl->type] ?? ucfirst($tpl->type) }} — {{ $tpl->name ?? $tpl->dlt_template_id ?? 'Default' }}
                    </option>
                @endforeach
            </select>
            @error('sms_template_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($templates->isEmpty())
                <div class="form-text text-danger">
                    No active template found. Go to <a href="{{ route('master.sms.templates.index') }}">Message Templates</a> and register or enable one first.
                </div>
            @endif
        </div>

        {{-- Variables + live preview --}}
        <div id="varsSection" class="mb-3" style="display:none;">
            <div class="card border-0 rounded-3 p-3" style="background:#f8fafc;border:1.5px dashed #e2e8f0 !important;">
                <div id="autoVarsNote" class="form-text mb-2" style="display:none;"></div>
                <div id="varsInputs"></div>
                <div class="mt-2">
                    <label class="form-label small fw-semibold mb-1">Live Preview</label>
                    <div id="previewBox" class="p-2 rounded bg-white border small" style="white-space:pre-wrap;min-height:40px;"></div>
                    <div id="charCount" class="form-text mt-1"></div>
                </div>
            </div>
        </div>

        {{-- Targeting: Students --}}
        <div id="studentTargeting" class="mb-3" style="display:none;">
            <div class="card border-0 rounded-3 p-3" style="background:#f8fafc;border:1.5px dashed #e2e8f0 !important;">
                <div class="mb-2" style="font-size:12px;font-weight:600;color:#475569;">
                    <i class="bi bi-funnel me-1"></i>Target Students <span class="text-muted fw-normal">(leave everything blank = all active students)</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Course Type</label>
                        <select id="courseTypeSelect" class="form-select form-select-sm">
                            <option value="">— All —</option>
                            @foreach($courseTypes as $ct)
                                <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Narrows the Course list (optional).</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Course(s)</label>
                        @php($selectedCourses = old('target_course_ids', []))
                        <select name="target_course_ids[]" id="courseSelect" class="form-select form-select-sm" multiple size="5">
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" data-course-type-id="{{ $course->course_type_id }}"
                                        {{ in_array($course->id, $selectedCourses) ? 'selected' : '' }}>{{ $course->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Ctrl/Cmd+click to select multiple.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Stream(s)</label>
                        @php($selectedStreams = old('target_stream_ids', []))
                        <select name="target_stream_ids[]" id="streamSelect" class="form-select form-select-sm" multiple size="5">
                            @foreach($streams as $stream)
                                <option value="{{ $stream->id }}" data-course-id="{{ $stream->course_id }}"
                                        {{ in_array($stream->id, $selectedStreams) ? 'selected' : '' }}>
                                    {{ $stream->course->name ?? '' }} — {{ $stream->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">The list filters when you pick a course.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Semester(s)</label>
                        @php($selectedSemesters = old('target_semesters', []))
                        <div class="d-flex flex-wrap gap-2" id="semesterCheckboxes">
                            @for($sem = 1; $sem <= 12; $sem++)
                            <div class="form-check form-check-inline border rounded px-2 py-1 sem-wrap {{ in_array($sem, $selectedSemesters) ? 'bg-warning-subtle border-warning' : 'bg-white' }}" data-sem="{{ $sem }}">
                                <input class="form-check-input" type="checkbox" name="target_semesters[]" id="sem_{{ $sem }}" value="{{ $sem }}"
                                       {{ in_array($sem, $selectedSemesters) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="sem_{{ $sem }}">Sem {{ $sem }}</label>
                            </div>
                            @endfor
                        </div>
                        <div class="form-text">Picking a course limits this to its actual semesters.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Targeting: Staff --}}
        <div id="staffTargeting" class="mb-3" style="display:none;">
            <div class="card border-0 rounded-3 p-3" style="background:#f8fafc;border:1.5px dashed #e2e8f0 !important;">
                <div class="mb-2" style="font-size:12px;font-weight:600;color:#475569;">
                    <i class="bi bi-funnel me-1"></i>Target Staff Role(s) <span class="text-muted fw-normal">(blank = all active staff)</span>
                </div>
                @php($selectedRoles = old('target_staff_role_ids', []))
                <select name="target_staff_role_ids[]" id="roleSelect" class="form-select form-select-sm" multiple size="5">
                    @foreach($staffRoles as $role)
                        <option value="{{ $role->id }}" {{ in_array($role->id, $selectedRoles) ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Recipient count + specific override --}}
        <div class="mb-3">
            <div class="card border-0 rounded-3 p-3" style="background:#eff6ff;border:1.5px dashed #bfdbfe !important;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="bi bi-people me-1 text-primary"></i>
                        <span class="fw-semibold">No. of recipients:</span>
                        <span id="recipientCount" class="fw-bold text-primary">—</span>
                    </div>
                    <div class="d-flex gap-3">
                        @php($recipientMode = old('recipient_mode', 'all'))
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_mode" id="mode_all" value="all" {{ $recipientMode === 'all' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="mode_all">Send to everyone</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="recipient_mode" id="mode_specific" value="specific" {{ $recipientMode === 'specific' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="mode_specific">Choose specific recipients</label>
                        </div>
                    </div>
                </div>

                <div id="selectedChips" class="d-flex flex-wrap gap-1 mt-2"></div>
                <div id="selectedContainer"></div>

                <div class="mt-3">
                    <input type="text" id="recipientSearch" class="form-control form-control-sm" placeholder="Search the list by name...">
                    <div id="recipientList" class="border rounded mt-2 bg-white" style="max-height:240px;overflow-y:auto;"></div>
                    <div id="recipientListNote" class="form-text mt-1"></div>
                </div>
            </div>
        </div>

        {{-- Optional in-app notice link --}}
        <div class="mb-1">
            <div class="card border-0 rounded-3 p-3" style="background:#f0fdf4;border:1.5px dashed #bbf7d0 !important;">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="link_notice" id="link_notice" value="1" {{ old('link_notice') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="link_notice">
                        <i class="bi bi-megaphone me-1 text-success"></i> Also post an in-app Notice with this?
                    </label>
                </div>
                <div id="noticeFields" style="display:{{ old('link_notice') ? 'block' : 'none' }};">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Notice Title <span class="text-danger">*</span></label>
                        <input type="text" name="notice_title" class="form-control form-control-sm" value="{{ old('notice_title') }}" maxlength="255">
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Notice Body <span class="text-danger">*</span></label>
                        <textarea name="notice_body" id="noticeBody" class="form-control form-control-sm" rows="4">{{ old('notice_body') }}</textarea>
                        <div class="form-text">Pre-filled from the preview — feel free to edit it (e.g. rewrite bits like {name} into proper wording).</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Save Draft</button>
    <a href="{{ route('master.sms.broadcasts.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>
<div class="form-text mt-2">The SMS won't send yet — after saving the draft you'll review it and confirm.</div>

</form>

@php($oldTemplateValues = old('template_values', new stdClass()))
@php($oldSpecificIds = old('specific_recipient_ids', []))
@push('scripts')
<script>
const TEMPLATES = @json($templatesForJs);
const AUTO_VARS = {
    student: @json($autoVarsStudent),
    staff:   @json($autoVarsStaff),
};
const OVERRIDABLE_AUTO_VARS = @json($overridableVars);
const OLD_TEMPLATE_VALUES = @json($oldTemplateValues);
const OLD_SPECIFIC_IDS    = @json($oldSpecificIds);
const COURSE_SEMESTERS    = @json($courseSemesterCounts);
const PREVIEW_COUNT_URL   = "{{ route('master.sms.broadcasts.preview-count') }}";
const SEARCH_URL          = "{{ route('master.sms.broadcasts.search-recipients') }}";
const CSRF_TOKEN          = "{{ csrf_token() }}";

const templateSelect  = document.getElementById('templateSelect');
const varsSection     = document.getElementById('varsSection');
const varsInputs      = document.getElementById('varsInputs');
const autoVarsNote    = document.getElementById('autoVarsNote');
const previewBox      = document.getElementById('previewBox');
const charCount       = document.getElementById('charCount');
const studentTargeting = document.getElementById('studentTargeting');
const staffTargeting   = document.getElementById('staffTargeting');
const recipientCountEl = document.getElementById('recipientCount');
const selectedChips    = document.getElementById('selectedChips');
const selectedContainer = document.getElementById('selectedContainer');
const searchInput      = document.getElementById('recipientSearch');
const recipientList    = document.getElementById('recipientList');
const recipientListNote = document.getElementById('recipientListNote');
const noticeFields     = document.getElementById('noticeFields');
const noticeBody       = document.getElementById('noticeBody');

let selectedRecipients = new Map(); // id -> name

function currentAudience() {
    return document.querySelector('input[name="audience_type"]:checked')?.value || 'student';
}

function toggleAudienceSections() {
    const isStaff = currentAudience() === 'staff';
    studentTargeting.style.display = isStaff ? 'none' : 'block';
    staffTargeting.style.display   = isStaff ? 'block' : 'none';
}

function renderVarsAndPreview() {
    const tpl = TEMPLATES.find(t => String(t.id) === templateSelect.value);
    if (!tpl) { varsSection.style.display = 'none'; return; }

    varsSection.style.display = 'block';
    const auto = AUTO_VARS[currentAudience()] || [];
    const autoUsed = tpl.vars.filter(v => auto.includes(v));
    const autoFixed = autoUsed.filter(v => !OVERRIDABLE_AUTO_VARS.includes(v));
    const autoOverridable = autoUsed.filter(v => OVERRIDABLE_AUTO_VARS.includes(v));
    const sharedVars = tpl.vars.filter(v => !auto.includes(v));

    if (autoFixed.length) {
        autoVarsNote.style.display = 'block';
        autoVarsNote.innerHTML = '<i class="bi bi-info-circle me-1"></i>Auto-filled per recipient: ' +
            autoFixed.map(v => `<code>{${v}}</code>`).join(', ');
    } else {
        autoVarsNote.style.display = 'none';
    }

    varsInputs.innerHTML = '';

    // Overridable auto vars (e.g. {mobile}) — blank = recipient's own (default), typed = one
    // fixed value for everyone (e.g. the college office number instead of each student's own).
    autoOverridable.forEach(v => {
        const oldVal = OLD_TEMPLATE_VALUES && OLD_TEMPLATE_VALUES[v] ? OLD_TEMPLATE_VALUES[v] : '';
        const wrap  = document.createElement('div');
        wrap.className = 'mb-2';
        const label = document.createElement('label');
        label.className = 'form-label small';
        label.innerHTML = `${v} <span class="text-muted fw-normal">(blank = each recipient's own ${v}; fill it in to send the same fixed value to everyone — e.g. the college office number)</span>`;
        const input = document.createElement('input');
        input.type = 'text';
        input.name = `template_values[${v}]`;
        input.className = 'form-control form-control-sm var-input';
        input.placeholder = 'Leave blank for auto-fill';
        input.value = oldVal;
        wrap.appendChild(label);
        wrap.appendChild(input);
        varsInputs.appendChild(wrap);
    });

    sharedVars.forEach(v => {
        const oldVal = OLD_TEMPLATE_VALUES && OLD_TEMPLATE_VALUES[v] ? OLD_TEMPLATE_VALUES[v] : '';
        const wrap  = document.createElement('div');
        wrap.className = 'mb-2';
        const label = document.createElement('label');
        label.className = 'form-label small';
        label.textContent = v;
        const input = document.createElement('input');
        input.type = 'text';
        input.name = `template_values[${v}]`;
        input.className = 'form-control form-control-sm var-input';
        input.value = oldVal;
        wrap.appendChild(label);
        wrap.appendChild(input);
        varsInputs.appendChild(wrap);
    });
    varsInputs.querySelectorAll('.var-input').forEach(inp => inp.addEventListener('input', renderPreviewOnly));

    renderPreviewOnly();
}

function renderPreviewOnly() {
    const tpl = TEMPLATES.find(t => String(t.id) === templateSelect.value);
    if (!tpl) { previewBox.textContent = ''; charCount.textContent = ''; return; }

    const auto = AUTO_VARS[currentAudience()] || [];
    let text = tpl.content;
    tpl.vars.forEach(v => {
        const input = document.querySelector(`[name="template_values[${v}]"]`);
        const val = input ? input.value : '';

        if (auto.includes(v)) {
            const overridden = OVERRIDABLE_AUTO_VARS.includes(v) && val;
            text = text.split('{' + v + '}').join(overridden ? val : '[' + v + ']');
        } else {
            text = text.split('{' + v + '}').join(val || ('{' + v + '}'));
        }
    });
    previewBox.textContent = text;
    const segments = Math.max(1, Math.ceil(text.length / 160));
    charCount.textContent = `${text.length} characters (~${segments} SMS segment${segments > 1 ? 's' : ''}). [bracketed] = that student's/staff's own data, filled in at send time.`;

    if (document.getElementById('link_notice').checked && !noticeBody.dataset.userEdited) {
        noticeBody.value = text.replace(/\[|\]/g, '');
    }
}

function collectTargetingFilters() {
    const audience = currentAudience();
    const filters = { audience_type: audience };

    if (audience === 'student') {
        filters.target_course_ids = Array.from(document.getElementById('courseSelect').selectedOptions).map(o => o.value);
        filters.target_stream_ids = Array.from(document.getElementById('streamSelect').selectedOptions).map(o => o.value);
        filters.target_semesters  = Array.from(document.querySelectorAll('input[name="target_semesters[]"]:checked')).map(i => i.value);
    } else {
        filters.target_staff_role_ids = Array.from(document.getElementById('roleSelect').selectedOptions).map(o => o.value);
    }

    return filters;
}

function refreshRecipientCount() {
    refreshRecipientList();

    const mode = document.querySelector('input[name="recipient_mode"]:checked')?.value || 'all';
    if (mode === 'specific') {
        recipientCountEl.textContent = selectedRecipients.size;
        return;
    }

    const filters = collectTargetingFilters();
    const body = new URLSearchParams();
    body.append('_token', CSRF_TOKEN);
    body.append('audience_type', filters.audience_type);
    (filters.target_course_ids || []).forEach(v => body.append('target_course_ids[]', v));
    (filters.target_stream_ids || []).forEach(v => body.append('target_stream_ids[]', v));
    (filters.target_semesters || []).forEach(v => body.append('target_semesters[]', v));
    (filters.target_staff_role_ids || []).forEach(v => body.append('target_staff_role_ids[]', v));

    recipientCountEl.textContent = '...';
    fetch(PREVIEW_COUNT_URL, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => { recipientCountEl.textContent = d.count; })
        .catch(() => { recipientCountEl.textContent = '—'; });
}

function filterStreamsByCourse() {
    const courseSelect = document.getElementById('courseSelect');
    const streamSelect = document.getElementById('streamSelect');
    if (!courseSelect || !streamSelect) return;
    const selectedCourseIds = Array.from(courseSelect.selectedOptions).map(o => o.value);

    Array.from(streamSelect.options).forEach(opt => {
        const belongs = selectedCourseIds.length === 0 || selectedCourseIds.includes(opt.dataset.courseId);
        opt.hidden = !belongs;
        if (!belongs) opt.selected = false;
    });
}

function filterCoursesByType() {
    const courseTypeSelect = document.getElementById('courseTypeSelect');
    const courseSelect = document.getElementById('courseSelect');
    if (!courseTypeSelect || !courseSelect) return;
    const typeId = courseTypeSelect.value;

    Array.from(courseSelect.options).forEach(opt => {
        const belongs = !typeId || opt.dataset.courseTypeId === typeId;
        opt.hidden = !belongs;
        if (!belongs) opt.selected = false;
    });
    filterStreamsByCourse();
}

// A trimester 4-year course has 12 semesters, a plain semester 3-year course has 6 — cap the
// visible Sem checkboxes to the max across selected courses so admins can't target a semester
// that course doesn't have. No course selected = show the full range (can't know a max yet).
function updateSemesterRange() {
    const courseSelect = document.getElementById('courseSelect');
    if (!courseSelect) return;
    const selectedCourseIds = Array.from(courseSelect.selectedOptions).map(o => o.value);

    let maxSem = 12;
    if (selectedCourseIds.length) {
        maxSem = Math.max(...selectedCourseIds.map(id => COURSE_SEMESTERS[id] || 12));
    }

    document.querySelectorAll('.sem-wrap').forEach(wrap => {
        const sem = parseInt(wrap.dataset.sem, 10);
        const visible = sem <= maxSem;
        wrap.style.display = visible ? '' : 'none';
        if (!visible) wrap.querySelector('input').checked = false;
    });
}

function renderSelectedChips() {
    selectedChips.innerHTML = '';
    selectedContainer.innerHTML = '';
    selectedRecipients.forEach((name, id) => {
        const chip = document.createElement('span');
        chip.className = 'badge bg-primary-subtle text-primary border';
        chip.appendChild(document.createTextNode(name + ' '));
        const removeBtn = document.createElement('span');
        removeBtn.style.cursor = 'pointer';
        removeBtn.dataset.remove = id;
        removeBtn.textContent = '×';
        chip.appendChild(removeBtn);
        selectedChips.appendChild(chip);

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'specific_recipient_ids[]';
        hidden.value = id;
        selectedContainer.appendChild(hidden);
    });
    selectedChips.querySelectorAll('[data-remove]').forEach(el => {
        el.addEventListener('click', () => {
            selectedRecipients.delete(el.dataset.remove);
            renderSelectedChips();
            refreshRecipientCount();
        });
    });
}

// Shows who the current targeting actually resolves to — not just a count — so an admin can
// visually confirm the right people before sending. Same endpoint powers the specific-recipient
// picker: in "all" mode rows are read-only, in "specific" mode each row gets a checkbox.
let listFetchTimer = null;
function refreshRecipientList() {
    clearTimeout(listFetchTimer);
    listFetchTimer = setTimeout(() => {
        const filters = collectTargetingFilters();
        const body = new URLSearchParams();
        body.append('_token', CSRF_TOKEN);
        body.append('audience_type', filters.audience_type);
        body.append('q', searchInput.value || '');
        (filters.target_course_ids || []).forEach(v => body.append('target_course_ids[]', v));
        (filters.target_stream_ids || []).forEach(v => body.append('target_stream_ids[]', v));
        (filters.target_semesters || []).forEach(v => body.append('target_semesters[]', v));
        (filters.target_staff_role_ids || []).forEach(v => body.append('target_staff_role_ids[]', v));

        fetch(SEARCH_URL, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(rows => renderRecipientList(rows));
    }, 300);
}

function renderRecipientList(rows) {
    const mode = document.querySelector('input[name="recipient_mode"]:checked')?.value || 'all';
    recipientList.innerHTML = '';

    if (!rows.length) {
        recipientList.innerHTML = '<div class="p-2 small text-muted">No matches found.</div>';
        recipientListNote.textContent = '';
        return;
    }

    rows.forEach(row => {
        const item = document.createElement('div');
        item.className = 'p-2 small border-bottom d-flex align-items-center gap-2';

        if (mode === 'specific') {
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input flex-shrink-0 mt-0';
            cb.checked = selectedRecipients.has(String(row.id));
            cb.addEventListener('change', () => {
                if (cb.checked) selectedRecipients.set(String(row.id), row.name);
                else selectedRecipients.delete(String(row.id));
                renderSelectedChips();
                refreshRecipientCount();
            });
            item.appendChild(cb);
        }

        const label = document.createElement('span');
        label.textContent = row.details ? `${row.name} — ${row.mobile} · ${row.details}` : `${row.name} — ${row.mobile}`;
        item.appendChild(label);
        recipientList.appendChild(item);
    });

    recipientListNote.textContent = rows.length >= 50
        ? 'Showing the top 50 — search to find someone specific.'
        : '';
}

templateSelect.addEventListener('change', renderVarsAndPreview);
document.querySelectorAll('input[name="audience_type"]').forEach(r => r.addEventListener('change', () => {
    toggleAudienceSections();
    renderVarsAndPreview();
    refreshRecipientCount();
}));
document.getElementById('courseTypeSelect')?.addEventListener('change', () => { filterCoursesByType(); updateSemesterRange(); refreshRecipientCount(); });
document.getElementById('courseSelect')?.addEventListener('change', () => { filterStreamsByCourse(); updateSemesterRange(); refreshRecipientCount(); });
document.getElementById('streamSelect')?.addEventListener('change', refreshRecipientCount);
document.getElementById('roleSelect')?.addEventListener('change', refreshRecipientCount);
document.querySelectorAll('input[name="target_semesters[]"]').forEach(i => i.addEventListener('change', refreshRecipientCount));
document.querySelectorAll('input[name="recipient_mode"]').forEach(r => r.addEventListener('change', refreshRecipientCount));
searchInput?.addEventListener('input', refreshRecipientList);
document.getElementById('link_notice').addEventListener('change', function () {
    noticeFields.style.display = this.checked ? 'block' : 'none';
    if (this.checked) renderPreviewOnly();
});
noticeBody.addEventListener('input', () => { noticeBody.dataset.userEdited = '1'; });

// Initial state on load (also handles validation-error redisplay — old() values above already
// repopulated the form fields; this just makes the JS-driven sections match them).
toggleAudienceSections();
filterStreamsByCourse();
updateSemesterRange();
if (templateSelect.value) renderVarsAndPreview();
if (Array.isArray(OLD_SPECIFIC_IDS) && OLD_SPECIFIC_IDS.length) {
    // Ids only survive validation-error redisplay — names aren't known client-side, so show the id.
    OLD_SPECIFIC_IDS.forEach(id => selectedRecipients.set(String(id), '#' + id));
    renderSelectedChips();
}
refreshRecipientCount();
</script>
@endpush

@endsection
