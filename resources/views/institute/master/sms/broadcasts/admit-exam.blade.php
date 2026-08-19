@extends('institute.layout')
@section('title', 'Admit Card & Exam SMS')
@section('breadcrumb', 'Master / SMS / Send SMS / Admit Card & Exam')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.broadcasts.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to Send SMS
        </a>
        <h4 class="mb-0 fw-bold mt-1"><i class="bi bi-mortarboard me-2 text-primary"></i>Admit Card &amp; Exam SMS</h4>
        <small class="text-muted">Select a template, filter the list, tick students, and send to everyone selected at once.</small>
    </div>
</div>

@if($templates->isEmpty())
<div class="alert alert-warning">
    No active Admit Card / Exam Info template found.
    <a href="{{ route('master.sms.templates.index') }}" class="alert-link">Register or enable one on the Message Templates page first.</a>
</div>
@else

<form method="GET" action="{{ route('master.sms.broadcasts.admit-exam') }}" id="filterForm">

{{-- Template + Variables --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Template <span class="text-danger">*</span></label>
                <select name="template_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" {{ $template && $template->id === $tpl->id ? 'selected' : '' }}>
                            {{ $tpl->type === 'admit_card' ? 'Admit Card' : 'Exam Info' }} — {{ $tpl->name ?? $tpl->dlt_template_id ?? 'Default' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($template)
        <div class="mt-3 p-3 rounded" style="background:#f8fafc;border:1.5px dashed #e2e8f0;">
            @if(count($autoFixedVars))
                <div class="small text-muted mb-2">
                    <i class="bi bi-info-circle me-1"></i>Auto-filled per recipient:
                    @foreach($autoFixedVars as $v)<code>{{ '{' . $v . '}' }}</code> @endforeach
                </div>
            @endif

            @foreach($overridableUsed as $v)
                <div class="mb-2">
                    <label class="form-label small">{{ $v }} <span class="text-muted fw-normal">(blank = each student's own {{ $v }}; fill it in to send the same fixed value to everyone — e.g. the college office number)</span></label>
                    <input type="text" name="template_values[{{ $v }}]" class="form-control form-control-sm var-input"
                           value="{{ old('template_values.' . $v, request()->input('template_values.' . $v)) }}" placeholder="Leave blank for auto-fill">
                </div>
            @endforeach

            @foreach($sharedVars as $v)
                <div class="mb-2">
                    <label class="form-label small">{{ $v }}</label>
                    <input type="text" name="template_values[{{ $v }}]" class="form-control form-control-sm var-input"
                           value="{{ old('template_values.' . $v, request()->input('template_values.' . $v)) }}">
                </div>
            @endforeach

            <div class="mt-2">
                <label class="form-label small fw-semibold mb-1">Live Preview</label>
                <div id="previewBox" class="p-2 rounded bg-white border small" style="white-space:pre-wrap;min-height:36px;"></div>
                <div id="charCount" class="form-text mt-1"></div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Course Type</label>
                <select name="course_type_id" id="courseTypeFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All —</option>
                    @foreach($courseTypes as $ct)
                        <option value="{{ $ct->id }}" {{ (string) request('course_type_id') === (string) $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Course</label>
                <select name="course_id" id="courseFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All —</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" data-course-type-id="{{ $c->course_type_id }}" {{ (string) request('course_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Stream</label>
                <select name="stream_id" id="streamFilter" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— All —</option>
                    @foreach($streams as $st)
                        <option value="{{ $st->id }}" data-course-id="{{ $st->course_id }}" {{ (string) request('stream_id') === (string) $st->id ? 'selected' : '' }}>{{ $st->course->name ?? '' }} — {{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Semester</label>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0" {{ (int) request('semester', 0) === 0 ? 'selected' : '' }}>All</option>
                    @for($s = 1; $s <= $maxSem; $s++)
                        <option value="{{ $s }}" {{ (int) request('semester', 0) === $s ? 'selected' : '' }}>Sem {{ $s }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, mobile, roll no...">
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('master.sms.broadcasts.admit-exam', ['template_id' => $template?->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Reset</a>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Bulk Send Bar --}}
<div class="d-flex align-items-center justify-content-between mb-2" id="bulkSendBar" style="display:none !important;">
    <div class="small text-muted"><span id="selectedCount">0</span> student(s) selected</div>
    <div class="d-flex align-items-center gap-2">
        <span id="sendResult" class="small"></span>
        <button type="button" class="btn btn-warning btn-sm" onclick="sendToSelected()" id="bulkSendBtn">
            <i class="bi bi-send me-1"></i>Send SMS to Selected
        </button>
    </div>
</div>

{{-- Student Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($students->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people fs-1 opacity-50"></i>
                <div class="mt-2">No students match this filter.</div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3"><input type="checkbox" class="form-check-input" id="selectAllCheckbox" onchange="toggleSelectAll(this)"></th>
                        <th>#</th>
                        <th>Student</th>
                        <th>Roll No</th>
                        <th>Course / Stream</th>
                        <th class="text-center">Semester</th>
                        <th>Mobile</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                        <tr>
                            <td class="ps-3">
                                <input type="checkbox" class="form-check-input student-checkbox" value="{{ $student->id }}" onchange="updateBulkBar()">
                            </td>
                            <td class="text-muted">{{ $students->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-semibold">{{ $student->name }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $student->student_uid }}</div>
                            </td>
                            <td class="text-muted">{{ $student->roll_no ?: '—' }}</td>
                            <td>
                                <div>{{ $student->stream->course->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $student->stream->name ?? '' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary fw-normal">Sem {{ $student->current_semester ?? '—' }}</span>
                            </td>
                            <td>{{ $student->mobile }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-3 pb-3">
            @include('institute.components.pagination', ['paginator' => $students])
        </div>
        @endif
    </div>
</div>

@endif

@push('scripts')
<script>
const TEMPLATE_CONTENT = @json($template?->content ?? '');
const AUTO_FIXED_VARS  = @json($autoFixedVars ?? []);
const ALL_VARS         = @json(array_values(array_unique(array_merge($autoFixedVars ?? [], $overridableUsed ?? [], $sharedVars ?? []))));

function renderPreview() {
    const box = document.getElementById('previewBox');
    if (!box) return;
    let text = TEMPLATE_CONTENT;
    ALL_VARS.forEach(v => {
        if (AUTO_FIXED_VARS.includes(v)) {
            text = text.split('{' + v + '}').join('[' + v + ']');
            return;
        }
        const input = document.querySelector(`[name="template_values[${v}]"]`);
        const val = input ? input.value : '';
        text = text.split('{' + v + '}').join(val || ('{' + v + '}'));
    });
    box.textContent = text;
    const segments = Math.max(1, Math.ceil(text.length / 160));
    const cc = document.getElementById('charCount');
    if (cc) cc.textContent = `${text.length} characters (~${segments} SMS segment${segments > 1 ? 's' : ''}). [bracketed] = that recipient's own data.`;
}
document.querySelectorAll('.var-input').forEach(inp => inp.addEventListener('input', renderPreview));
renderPreview();

document.getElementById('courseTypeFilter')?.addEventListener('change', function () {
    const typeId = this.value;
    document.querySelectorAll('#courseFilter option[data-course-type-id]').forEach(opt => {
        opt.hidden = !!typeId && opt.dataset.courseTypeId !== typeId;
    });
});

function getSelectedStudentIds() {
    return Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
}

function toggleSelectAll(checkbox) {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const ids = getSelectedStudentIds();
    document.getElementById('selectedCount').textContent = ids.length;
    document.getElementById('bulkSendBar').style.setProperty('display', ids.length ? 'flex' : 'none', 'important');
}

function sendToSelected() {
    const ids = getSelectedStudentIds();
    if (!ids.length) return;

    const templateSelect = document.querySelector('select[name="template_id"]');
    if (!templateSelect || !templateSelect.value) return;

    if (!confirm(`Send SMS to ${ids.length} student(s)? This can't be undone.`)) return;

    const btn = document.getElementById('bulkSendBtn');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    const body = new URLSearchParams();
    body.append('_token', "{{ csrf_token() }}");
    body.append('sms_template_id', templateSelect.value);
    ids.forEach(id => body.append('student_ids[]', id));
    document.querySelectorAll('.var-input').forEach(inp => {
        const match = inp.name.match(/^template_values\[(.+)\]$/);
        if (match) body.append(`template_values[${match[1]}]`, inp.value);
    });

    fetch("{{ route('master.sms.broadcasts.admit-exam.send') }}", {
        method: 'POST',
        body,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            const resultEl = document.getElementById('sendResult');
            resultEl.className = data.success ? 'small text-success' : 'small text-danger';
            resultEl.textContent = data.success ? data.message : (data.error || 'Failed to send.');
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            const resultEl = document.getElementById('sendResult');
            resultEl.className = 'small text-danger';
            resultEl.textContent = 'Request failed. Check your network.';
        });
}
</script>
@endpush

@endsection
