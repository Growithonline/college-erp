@extends('institute.layout')
@section('title', 'Message Templates')
@section('breadcrumb', 'Master / SMS / Message Templates')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('master.sms.index') }}" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to SMS Settings
        </a>
        <h4 class="mb-0 fw-bold mt-1">Message Templates</h4>
        <small class="text-muted">DLT-registered templates per message type — used for notices, fee alerts, admission, and more</small>
    </div>
</div>

@if(! $smsConfigured)
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>SMS Provider not configured.</strong>
    <a href="{{ route('master.sms.index') }}" class="alert-link">Configure SMS provider</a> first — templates below won't be used until then.
</div>
@endif

{{-- Single-template types --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Category</th>
                    <th>DLT Template ID</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $type => $row)
                    @continue($row['meta']['is_multi'])
                    @php($t = $row['template'])
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $row['meta']['label'] }}</div>
                            @if(! $row['meta']['wired'])
                                <span class="badge bg-secondary-subtle text-secondary border small">Feature not built yet — template ready for later</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ ($t->category ?? $row['meta']['category']) === 'promotional' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle' }}">
                                {{ ucfirst($t->category ?? $row['meta']['category']) }}
                            </span>
                        </td>
                        <td>
                            @if($t && $t->dlt_template_id)
                                <code class="small">{{ $t->dlt_template_id }}</code>
                            @else
                                <span class="text-muted small">Not set</span>
                            @endif
                        </td>
                        <td>
                            @if(! $t)
                                <span class="badge bg-secondary-subtle text-secondary border">Not configured</span>
                            @elseif($t->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">Disabled</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_{{ $type }}">
                                <i class="bi bi-pencil-square me-1"></i>{{ $t ? 'Edit' : 'Set Up' }}
                            </button>
                            @if($t)
                            <form method="POST" action="{{ route('master.sms.templates.toggle') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="type" value="{{ $type }}">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi {{ $t->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    {{-- Edit modal --}}
                    <div class="modal fade" id="editModal_{{ $type }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('master.sms.templates.save') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">{{ $row['meta']['label'] }} Template</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Category</label>
                                            <select name="category" class="form-select form-select-sm">
                                                <option value="transactional" {{ ($t->category ?? $row['meta']['category']) === 'transactional' ? 'selected' : '' }}>Transactional</option>
                                                <option value="promotional" {{ ($t->category ?? $row['meta']['category']) === 'promotional' ? 'selected' : '' }}>Promotional</option>
                                            </select>
                                            <div class="form-text">Promotional uses the Promotional Sender ID from SMS Settings (if set).</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">DLT Template ID <span class="text-muted fw-normal">(Fast2SMS "Message ID")</span></label>
                                            <input type="text" name="dlt_template_id" class="form-control form-control-sm"
                                                   value="{{ $t->dlt_template_id ?? '' }}" placeholder="e.g. 223180">
                                            <div class="form-text">Copy from your Fast2SMS DLT template dashboard. Required for DLT-compliant delivery on Fast2SMS.</div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                                            <textarea name="content" class="form-control form-control-sm template-content template-content-input" rows="4" maxlength="1000">{{ $t->content ?? '' }}</textarea>
                                            <div class="form-text">Must match your DLT registered template exactly, word for word.</div>
                                            <div class="template-var-helper-slot"></div>
                                        </div>
                                        <div class="card border-0 bg-info-subtle p-3 rounded-3">
                                            <p class="small fw-semibold mb-1">Available Variables:</p>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($row['meta']['vars'] as $var)
                                                    <span class="badge bg-white text-dark border insert-var" data-var="{{ '{' . $var . '}' }}" style="cursor:pointer;">{{ '{' . $var . '}' }}</span>
                                                @endforeach
                                            </div>
                                            <div class="form-text mt-1">Click a badge to insert a variable into the content above.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i>Save Template
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Multi-template types (Notice) — an institute can have many named DLT templates --}}
@foreach($rows as $type => $row)
    @continue(! $row['meta']['is_multi'])
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
            <span class="fw-semibold small">
                <i class="bi bi-megaphone me-2 text-primary"></i>{{ $row['meta']['label'] }} Templates
                @if(! $row['meta']['wired'])
                    <span class="badge bg-secondary-subtle text-secondary border small ms-1">Feature not built yet</span>
                @endif
            </span>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal_{{ $type }}">
                <i class="bi bi-plus-lg me-1"></i>Add New Template
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>DLT Template ID</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($row['templates'] as $t)
                        <tr>
                            <td class="fw-semibold small">{{ $t->name }}</td>
                            <td>
                                <span class="badge {{ $t->category === 'promotional' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle' }}">
                                    {{ ucfirst($t->category) }}
                                </span>
                            </td>
                            <td>
                                @if($t->dlt_template_id)
                                    <code class="small">{{ $t->dlt_template_id }}</code>
                                @else
                                    <span class="text-muted small">Not set</span>
                                @endif
                            </td>
                            <td>
                                @if($t->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Disabled</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal_{{ $type }}_{{ $t->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form method="POST" action="{{ route('master.sms.templates.toggle') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <input type="hidden" name="template_id" value="{{ $t->id }}">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi {{ $t->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('master.sms.templates.destroy') }}" class="d-inline"
                                      onsubmit="return confirm('Delete this template?');">
                                    @csrf
                                    <input type="hidden" name="template_id" value="{{ $t->id }}">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- Edit modal for this specific template --}}
                        <div class="modal fade" id="editModal_{{ $type }}_{{ $t->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('master.sms.templates.save') }}">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <input type="hidden" name="template_id" value="{{ $t->id }}">
                                        <div class="modal-header">
                                            <h6 class="modal-title fw-bold">Edit Template — {{ $t->name }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $t->name }}" maxlength="100" required>
                                                <div class="form-text">Internal label to identify this template — not sent in the SMS.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Category</label>
                                                <select name="category" class="form-select form-select-sm">
                                                    <option value="transactional" {{ $t->category === 'transactional' ? 'selected' : '' }}>Transactional</option>
                                                    <option value="promotional" {{ $t->category === 'promotional' ? 'selected' : '' }}>Promotional</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">DLT Template ID <span class="text-muted fw-normal">(Fast2SMS "Message ID")</span></label>
                                                <input type="text" name="dlt_template_id" class="form-control form-control-sm" value="{{ $t->dlt_template_id }}" placeholder="e.g. 223154">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                                                <textarea name="content" class="form-control form-control-sm template-content-input" rows="5" maxlength="1000">{{ $t->content }}</textarea>
                                                <div class="form-text">
                                                    Must match your DLT registered template exactly, word for word. Use your own
                                                    <code>{variable_name}</code> placeholders — whatever you type here is what gets filled
                                                    in when creating a notice with this template selected.
                                                </div>
                                                <div class="template-var-helper-slot"></div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-save me-1"></i>Save Template
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted small py-4">
                                No {{ strtolower($row['meta']['label']) }} templates yet — click "Add New Template" to register one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add new template modal --}}
    <div class="modal fade" id="addModal_{{ $type }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('master.sms.templates.save') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Add {{ $row['meta']['label'] }} Template</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" maxlength="100" required
                                   placeholder="e.g. Registration Notice, Exam Schedule, Admit Card Distribution">
                            <div class="form-text">Internal label to identify this template — not sent in the SMS.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="transactional" selected>Transactional</option>
                                <option value="promotional">Promotional</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">DLT Template ID <span class="text-muted fw-normal">(Fast2SMS "Message ID")</span></label>
                            <input type="text" name="dlt_template_id" class="form-control form-control-sm" placeholder="e.g. 223154">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Content <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control form-control-sm template-content-input" rows="5" maxlength="1000"></textarea>
                            <div class="form-text">
                                Must match your DLT registered template exactly, word for word. Use your own
                                <code>{variable_name}</code> placeholders — whatever you type here is what gets filled
                                in when creating a notice with this template selected.
                            </div>
                            <div class="template-var-helper-slot"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>Save Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
document.querySelectorAll('.insert-var').forEach(badge => {
    badge.addEventListener('click', () => {
        const textarea = badge.closest('.modal-body').querySelector('.template-content');
        const pos = textarea.selectionStart;
        const v = badge.dataset.var;
        textarea.value = textarea.value.substring(0, pos) + v + textarea.value.substring(pos);
        textarea.focus();
        textarea.setSelectionRange(pos + v.length, pos + v.length);
        textarea.dispatchEvent(new Event('input'));
    });
});

// Non-technical admins often paste their Fast2SMS/DLT-approved text as-is, which uses the
// carrier's generic "{#VAR#}" placeholder — the app can only fill named placeholders like
// {course}, so this live-detects both kinds and, for generic ones, walks the admin through
// naming each one instead of expecting them to hand-edit curly braces correctly themselves.
function initTemplateVarHelper(textarea) {
    const slot = textarea.closest('.mb-2, .mb-3')?.querySelector('.template-var-helper-slot');
    if (!slot) return;

    function render() {
        const text = textarea.value;
        const namedMatches = text.match(/\{[a-z_][a-z0-9_]*\}/g) || [];
        const named = [...new Set(namedMatches)];
        const generic = text.match(/\{#\s*var\s*#\}/gi) || [];

        // Same named placeholder appearing 2+ times (e.g. an already-saved template that had
        // {#VAR#} renamed to {text} four times before this occurrence-count check existed) —
        // it's still only ONE fillable variable, so every one of those slots gets the same
        // value. Offer the same split-into-distinct-slots fix as the generic {#VAR#} case.
        const counts = {};
        namedMatches.forEach(m => { counts[m] = (counts[m] || 0) + 1; });
        const repeated = Object.keys(counts).filter(k => counts[k] > 1);

        let html = '';
        if (named.length) {
            html += '<div class="small text-success mt-1 mb-1"><i class="bi bi-check-circle me-1"></i>Variables mile — naam badalna ho to yahin edit karke "Naam Update Karo" dabao:</div>' +
                '<div class="rename-named-rows"></div>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary mt-1 rename-named-btn"><i class="bi bi-pencil me-1"></i>Naam Update Karo</button>';
        }
        if (repeated.length) {
            html += '<div class="small p-2 rounded mt-1" style="background:#fff8e1;border:1px solid #ffe082;">' +
                '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>' +
                `<strong>${repeated.map(r => `${r} (${counts[r]}x)`).join(', ')}</strong> multiple jagah use ho raha hai — abhi sabko EK hi value milegi jab bhejoge. ` +
                'Agar har jagah alag value honi chahiye:' +
                ' <button type="button" class="btn btn-sm btn-outline-warning mt-1 split-duplicates-btn"><i class="bi bi-magic me-1"></i>Alag-Alag Banao</button>' +
                '</div>';
        }
        if (generic.length) {
            html += '<div class="small p-2 rounded mt-1" style="background:#fff8e1;border:1px solid #ffe082;">' +
                '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>' +
                `<strong>${generic.length} generic <code>{#VAR#}</code> placeholder(s) mile</strong> — ye DLT portal se copy-paste kiya hua lagta hai. ` +
                'Ye is form me kaam nahi karega jab tak har ek ko ek naam na do (jaise: course, date, venue).' +
                '<div class="rename-rows mt-2"></div>' +
                '<button type="button" class="btn btn-sm btn-outline-primary mt-1 apply-rename-btn"><i class="bi bi-magic me-1"></i>Naam Apply Karo</button>' +
                '</div>';
        }
        if (!named.length && !generic.length && text.trim()) {
            html += '<div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>Koi variable nahi mila — poora message fixed text jaega, kisi ke liye alag nahi hoga.</div>';
        }
        slot.innerHTML = html;

        if (named.length) {
            const namedRows = slot.querySelector('.rename-named-rows');
            named.forEach(token => {
                const bare = token.slice(1, -1); // "{course}" -> "course"
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2 mb-1';
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm rename-named-input';
                input.dataset.old = bare;
                input.value = bare;
                input.style.maxWidth = '220px';
                row.appendChild(input);
                namedRows.appendChild(row);
            });
            slot.querySelector('.rename-named-btn').addEventListener('click', () => {
                const renames = [];
                slot.querySelectorAll('.rename-named-input').forEach(inp => {
                    const oldName = inp.dataset.old;
                    const safeNew = inp.value.trim().toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '').replace(/^[0-9]+/, '');
                    if (safeNew && safeNew !== oldName) renames.push([oldName, safeNew]);
                });
                if (!renames.length) return;

                // Two-pass via temporary tokens — so renaming A->B and B->A at the same time
                // (or any other overlapping rename) can't collide mid-way. Marker deliberately
                // avoids double curly braces and @ signs — Blade compiles those as its own
                // syntax even inside a script block, since it scans the whole file's raw text.
                let newText = textarea.value;
                renames.forEach(([oldName], i) => {
                    newText = newText.split('{' + oldName + '}').join('__TMPRENAME' + i + '__');
                });
                renames.forEach(([, safeNew], i) => {
                    newText = newText.split('__TMPRENAME' + i + '__').join('{' + safeNew + '}');
                });
                textarea.value = newText;
                render();
            });
        }

        if (repeated.length) {
            slot.querySelector('.split-duplicates-btn').addEventListener('click', () => {
                let newText = textarea.value;
                repeated.forEach(token => {
                    const name = token.slice(1, -1); // "{text}" -> "text"
                    const escaped = token.replace(/[{}]/g, '\\$&');
                    let occurrence = 0;
                    newText = newText.replace(new RegExp(escaped, 'g'), () => {
                        occurrence++;
                        return occurrence === 1 ? token : '{' + name + '_' + occurrence + '}';
                    });
                });
                textarea.value = newText;
                render();
            });
        }

        if (generic.length) {
            const rows = slot.querySelector('.rename-rows');
            generic.forEach((_, i) => {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2 mb-1';
                row.innerHTML = `<span style="min-width:95px;">Placeholder #${i + 1}:</span>` +
                    '<input type="text" class="form-control form-control-sm rename-input" placeholder="jaise: mobile, exam_date" style="max-width:220px;">';
                rows.appendChild(row);
            });
            slot.querySelector('.apply-rename-btn').addEventListener('click', () => {
                let newText = textarea.value;
                // Two slots given the same name (e.g. admin types "date" twice) would collapse
                // into ONE variable that sends the same value to both DLT positions — auto-suffix
                // repeats (date, date_2, date_3...) so every slot stays independently fillable.
                const seenCounts = {};
                slot.querySelectorAll('.rename-input').forEach(inp => {
                    const raw = inp.value.trim();
                    if (!raw) return;
                    let safe = raw.toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '').replace(/^[0-9]+/, '');
                    if (!safe) return;
                    seenCounts[safe] = (seenCounts[safe] || 0) + 1;
                    if (seenCounts[safe] > 1) safe = safe + '_' + seenCounts[safe];
                    newText = newText.replace(/\{#\s*var\s*#\}/i, '{' + safe + '}');
                });
                textarea.value = newText;
                render();
            });
        }
    }

    textarea.addEventListener('input', render);
    render();
}

document.querySelectorAll('.template-content-input').forEach(initTemplateVarHelper);
</script>

@endsection
