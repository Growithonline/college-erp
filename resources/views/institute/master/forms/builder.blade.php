@extends('institute.layout')
@section('title', $formInfo['label'].' Builder')
@section('breadcrumb', 'Master / Form Builder / '.$formInfo['label'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi {{ $formInfo['icon'] }} me-2 text-{{ $formInfo['color'] }}"></i>
            {{ $formInfo['label'] }}
        </h4>
        <small class="text-muted">{{ $formInfo['description'] ?? '' }}</small>
    </div>
    <a href="{{ route('master.forms.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('master.forms.save', $type) }}">
@csrf
<div class="row g-3">

    {{-- ══════════════════════════════════ --}}
    {{-- LEFT — Toggles                     --}}
    {{-- ══════════════════════════════════ --}}
    <div class="col-md-5">

        @foreach($sections as $sKey => $section)
        @php
            $hasSection = array_key_exists('section_enabled', $section); // Quick form only
            $secEnabled = $hasSection ? $section['section_enabled'] : true;
        @endphp

        <div class="card border-0 shadow-sm mb-3" id="card_{{ $sKey }}">

            {{-- Section Header --}}
            <div class="card-header py-2 d-flex align-items-center justify-content-between"
                 style="background:#f8fafc; border-bottom:2px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi {{ $section['icon'] }} text-primary"></i>
                    <span class="fw-bold small">{{ $section['label'] }}</span>
                </div>

                {{-- Section-level toggle (Quick form only) --}}
                @if($hasSection)
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size:10px;">Section ON/OFF</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input section-toggle"
                               type="checkbox"
                               name="section_enabled[{{ $sKey }}]"
                               value="1"
                               id="sec_{{ $sKey }}"
                               data-section="{{ $sKey }}"
                               {{ $secEnabled ? 'checked' : '' }}
                               onchange="toggleSection(this)"
                               style="cursor:pointer;">
                    </div>
                </div>
                @endif
            </div>

            {{-- Fields — collapse if section off --}}
            <div class="card-body p-0 section-fields" id="fields_{{ $sKey }}"
                 style="{{ ($hasSection && !$secEnabled) ? 'display:none' : '' }}">

                {{-- Column headers --}}
                <div class="d-flex align-items-center px-3 py-1 border-bottom"
                     style="background:#f1f5f9;">
                    <div class="flex-fill" style="font-size:11px;font-weight:600;color:#64748b;">Field</div>
                    <div class="text-center" style="width:60px;font-size:11px;font-weight:600;color:#64748b;">Show</div>
                    <div class="text-center" style="width:70px;font-size:11px;font-weight:600;color:#64748b;">Required</div>
                </div>

                @foreach($section['fields'] as $field)
                <div class="d-flex align-items-center px-3 py-2 border-bottom field-row"
                     style="transition:background 0.15s;">
                    <div class="flex-fill">
                        <span style="font-size:12px;font-weight:500;">{{ $field['label'] }}</span>
                        @if(!empty($field['readonly']))
                            <span class="badge bg-light text-muted ms-1" style="font-size:9px;">auto</span>
                        @endif
                    </div>
                    <div class="text-center" style="width:60px;">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input class="form-check-input field-toggle"
                                   type="checkbox"
                                   name="fields[{{ $field['key'] }}][enabled]"
                                   value="1"
                                   data-key="{{ $field['key'] }}"
                                   data-label="{{ $field['label'] }}"
                                   data-section="{{ $sKey }}"
                                   data-type="{{ $type }}"
                                   {{ $field['enabled'] ? 'checked' : '' }}
                                   onchange="toggleField(this)">
                        </div>
                    </div>
                    <div class="text-center" style="width:70px;">
                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="fields[{{ $field['key'] }}][required]"
                                   value="1"
                                   id="req_{{ $field['key'] }}"
                                   data-key="{{ $field['key'] }}"
                                   {{ $field['required'] ? 'checked' : '' }}
                                   onchange="toggleRequired(this)">
                        </div>
                    </div>
                </div>
                @endforeach

            </div>{{-- /section-fields --}}
        </div>
        @endforeach

        {{-- Quick Form: Collect Fee option --}}
        @if($type === 'quick')
        <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #f59e0b !important;">
            <div class="card-header py-2 d-flex align-items-center gap-2"
                 style="background:#fffbeb;">
                <i class="bi bi-cash-coin text-warning"></i>
                <span class="fw-bold small">Quick Registration Settings</span>
            </div>
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center justify-content-between py-1">
                    <div>
                        <span class="small fw-semibold">Fee Collection</span>
                        <div class="text-muted" style="font-size:11px;">Collect fee after quick registration?</div>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox"
                               name="form_config[collect_fee]"
                               value="1"
                               id="collectFeeToggle"
                               {{ ($currentFormConfig['collect_fee'] ?? true) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Online Form: Document Upload Setting --}}
        @if($type === 'online')
        @php
            $onlineDocVal = $currentFormConfig['doc_upload'] ?? 'skip';
            $docOptions = [
                'skip'     => ['label' => 'Skip',     'icon' => 'bi-dash-circle',        'color' => 'secondary', 'desc' => 'Document upload step will not be shown'],
                'optional' => ['label' => 'Optional', 'icon' => 'bi-info-circle',        'color' => 'warning',   'desc' => 'Soft reminder — student can skip'],
                'required' => ['label' => 'Required', 'icon' => 'bi-exclamation-circle', 'color' => 'danger',    'desc' => 'Hard block — cannot proceed without upload'],
            ];
        @endphp
        <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #10b981 !important;">
            <div class="card-header py-2 d-flex align-items-center gap-2"
                 style="background:#ecfdf5;">
                <i class="bi bi-paperclip text-success"></i>
                <span class="fw-bold small">Document Upload Settings</span>
            </div>
            <div class="card-body py-3 px-3">
                <div class="text-muted mb-2" style="font-size:11px;">
                    Show document upload step after online admission submission?
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($docOptions as $val => $opt)
                    <div>
                        <input type="radio" class="btn-check" name="form_config[doc_upload]"
                               id="online_doc_{{ $val }}" value="{{ $val }}"
                               {{ $onlineDocVal === $val ? 'checked' : '' }}>
                        <label class="btn btn-sm btn-outline-{{ $opt['color'] }}"
                               for="online_doc_{{ $val }}"
                               data-bs-toggle="tooltip" title="{{ $opt['desc'] }}">
                            <i class="bi {{ $opt['icon'] }} me-1"></i>{{ $opt['label'] }}
                        </label>
                    </div>
                    @endforeach
                </div>
                <div class="text-muted mt-2" style="font-size:10px;">
                    <i class="bi bi-info-circle me-1"></i>
                    Configure course-wise document requirements from the "Document Rules" section.
                </div>
            </div>
        </div>
        @endif

        <button type="submit" class="btn btn-primary w-100 mb-4">
            <i class="bi bi-check-lg me-1"></i> Save Configuration
        </button>
    </div>

    {{-- ══════════════════════════════════ --}}
    {{-- RIGHT — Live Preview               --}}
    {{-- ══════════════════════════════════ --}}
    <div class="col-md-7">
        <div class="card border-0 shadow-sm" style="position:sticky;top:10px;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center"
                 style="background:#1e293b;color:white;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-eye text-info"></i>
                    <span class="fw-bold small">Live Preview</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($type !== 'receipt')
                    <button type="button" onclick="printFormPreview()" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:10px;">
                        <i class="bi bi-printer me-1"></i>Print Form
                    </button>
                    @endif
                    <span class="badge bg-{{ $formInfo['color'] }}" style="font-size:10px;">
                        {{ $formInfo['label'] }}
                    </span>
                </div>
            </div>

            {{-- ─── RECEIPT PREVIEW ─── --}}
            @if($type === 'receipt')
            <div class="card-body p-3" style="background:#f8fafc;max-height:80vh;overflow-y:auto;">
                <div class="border rounded p-3" style="background:white;">
                    <div class="text-center border-bottom pb-2 mb-2">
                        <div id="prev_show_logo" {{ !($sections['layout']['fields'][0]['enabled'] ?? false) ? 'style=display:none' : '' }}>
                            <div class="rounded-circle border d-inline-flex align-items-center justify-content-center mb-1"
                                 style="width:40px;height:40px;background:#f1f5f9;">
                                <i class="bi bi-building text-primary"></i>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:13px;">{{ auth()->user()->institute->name ?? 'Institute Name' }}</div>
                        <div style="font-size:10px;color:#64748b;">Fee Receipt</div>
                    </div>
                    <div class="row g-1 mb-2" style="font-size:11px;">
                        <div class="col-6"><span class="text-muted">Receipt No:</span> <b>BBA/FEE/2026/00001</b></div>
                        <div class="col-6 text-end"><span class="text-muted">Date:</span> {{ date('d/m/Y') }}</div>
                        <div class="col-6"><span class="text-muted">Student:</span> Rahul Kumar</div>
                        <div id="prev_receipt_student_id" class="col-6" {{ !($sections['fields']['fields'][0]['enabled'] ?? false) ? 'style=display:none' : '' }}>
                            <span class="text-muted">ID:</span> BBA/STU/2026/0001
                        </div>
                        <div id="prev_receipt_course" class="col-12" {{ !($sections['fields']['fields'][1]['enabled'] ?? false) ? 'style=display:none' : '' }}>
                            <span class="text-muted">Course:</span> B.Ed — Sanskrit
                        </div>
                        <div id="prev_receipt_father" class="col-12" {{ !($sections['fields']['fields'][2]['enabled'] ?? false) ? 'style=display:none' : '' }}>
                            <span class="text-muted">Father:</span> Ram Kumar
                        </div>
                        <div id="prev_receipt_mobile" class="col-6" {{ !($sections['fields']['fields'][3]['enabled'] ?? false) ? 'style=display:none' : '' }}>
                            <span class="text-muted">Mobile:</span> 9876543210
                        </div>
                    </div>
                    <div class="border-top border-bottom py-1 mb-2" style="font-size:11px;">
                        <div class="d-flex justify-content-between"><span>Course Fee</span><span>₹5,000</span></div>
                        <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>₹5,000</span></div>
                    </div>
                    <div id="prev_receipt_collected_by" style="font-size:10px;color:#64748b;{{ !($sections['fields']['fields'][5]['enabled'] ?? false) ? 'display:none;' : '' }}">
                        Collected By: BMC Center
                    </div>
                    <div id="prev_receipt_footer_note" style="font-size:10px;color:#64748b;{{ !($sections['fields']['fields'][6]['enabled'] ?? false) ? 'display:none;' : '' }}">
                        Note: Fees once paid are non-refundable.
                    </div>
                    <div id="prev_show_sign_line" class="d-flex justify-content-between mt-3 pt-2 border-top"
                         style="font-size:10px;{{ !($sections['layout']['fields'][2]['enabled'] ?? false) ? 'display:none;' : '' }}">
                        <span>Student Sign</span><span>Authorized Sign</span>
                    </div>
                </div>
            </div>

            {{-- ─── FORM PREVIEW (Admission / Quick / Online) ─── --}}
            @else
            <div class="card-body p-3" style="background:#f8fafc;max-height:80vh;overflow-y:auto;">

                {{-- Institute Header --}}
                <div class="text-center border-bottom pb-2 mb-3">
                    <div class="fw-bold" style="font-size:13px;">
                        {{ auth()->user()->institute->name ?? 'Institute Name' }}
                    </div>
                    <div style="font-size:11px;color:#64748b;">
                        @if($type==='quick') Quick Registration
                        @elseif($type==='online') Online Admission Form
                        @else Admission Form — {{ date('Y') }}
                        @endif
                    </div>
                </div>

                @if($type==='online')
                <div class="alert alert-success py-1 px-2 mb-2" style="font-size:11px;">
                    <i class="bi bi-globe me-1"></i>
                    Public URL: <b>{{ url('/apply/'.strtolower(auth()->user()->institute->short_name ?? 'bba')) }}</b>
                </div>
                @endif

                @php
                $sectionColors = [
                    'office'    => ['bg'=>'#eff6ff','color'=>'#1d4ed8','border'=>'#bfdbfe'],
                    'personal'  => ['bg'=>'#f0fdf4','color'=>'#166534','border'=>'#bbf7d0'],
                    'address'   => ['bg'=>'#fff7ed','color'=>'#9a3412','border'=>'#fed7aa'],
                    'education' => ['bg'=>'#faf5ff','color'=>'#6b21a8','border'=>'#e9d5ff'],
                    'basic'     => ['bg'=>'#f0fdf4','color'=>'#166534','border'=>'#bbf7d0'],
                ];
                // Admission form correct sequence
                $previewOrder = $type === 'admission' || $type === 'online'
                    ? ['office','personal','address','education']
                    : array_keys($sections);
                @endphp

                @foreach($previewOrder as $sKey)
                @if(!isset($sections[$sKey])) @continue @endif
                @php
                    $section = $sections[$sKey];
                    $c = $sectionColors[$sKey] ?? $sectionColors['office'];
                    $isEdu = in_array($sKey, ['education']);
                    $secVisible = $section['section_enabled'] ?? true;
                @endphp

                <div id="preview_section_wrap_{{ $sKey }}"
                     style="{{ !$secVisible ? 'display:none' : '' }}">
                    <div class="mb-3">
                        <div class="fw-semibold px-2 py-1 rounded mb-2"
                             style="background:#1e293b;color:white;font-size:11px;">
                            <i class="bi {{ $section['icon'] }} me-1"></i> {{ $section['label'] }}
                        </div>
                        <div id="preview_fields_{{ $sKey }}"
                             class="{{ $isEdu ? '' : 'd-flex flex-wrap gap-1' }}">
                            @foreach($section['fields'] as $f)
                            @if($f['enabled'])
                            @if($isEdu)
                            <div id="preview_{{ $f['key'] }}"
                                 class="d-flex align-items-center gap-1 mb-1 px-2 py-1 rounded"
                                 style="background:{{ $c['bg'] }};border:1px solid {{ $c['border'] }};font-size:11px;color:{{ $c['color'] }};">
                                <i class="bi bi-table small"></i>
                                <span class="fw-semibold">{{ $f['label'] }}</span>
                                @if($f['required'])<span style="color:red">*</span>@endif
                            </div>
                            @else
                            <span id="preview_{{ $f['key'] }}"
                                  class="badge border fw-normal px-2 mb-1"
                                  style="background:{{ $c['bg'] }};color:{{ $c['color'] }};border-color:{{ $c['border'] }}!important;font-size:11px;">
                                {{ $f['label'] }}@if($f['required'])<span style="color:red">*</span>@endif
                            </span>
                            @endif
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- Course Details (always shown) --}}
                <div class="mb-2">
                    <div class="fw-semibold px-2 py-1 rounded mb-2"
                         style="background:#1e293b;color:white;font-size:11px;">
                        <i class="bi bi-book me-1"></i> Course Details
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge border fw-normal px-2"
                              style="background:#fefce8;color:#713f12;border-color:#fde68a!important;font-size:11px;">
                            Course Name <span style="color:red">*</span>
                        </span>
                        <span class="badge border fw-normal px-2"
                              style="background:#fefce8;color:#713f12;border-color:#fde68a!important;font-size:11px;">
                            Stream / Subject
                        </span>
                        <span class="badge border fw-normal px-2"
                              style="background:#fefce8;color:#713f12;border-color:#fde68a!important;font-size:11px;">
                            Semester / Year
                        </span>
                    </div>
                </div>

                {{-- Signature --}}
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <small class="text-muted" style="font-size:10px;">Student Signature</small>
                    <small class="text-muted" style="font-size:10px;">Office Signature</small>
                </div>

            </div>
            @endif
        </div>
    </div>

</div>
</form>

@php
    $_inst        = auth()->user()->institute;
    $_instAddr    = trim(implode(', ', array_filter([
        $_inst->address ?? '',
        $_inst->city    ?? '',
        $_inst->state   ?? '',
        $_inst->pincode ?? '',
    ])));
    $_instLogo    = $_inst->image ? asset('storage/'.$_inst->image) : '';
@endphp
@push('scripts')
<script>
const sectionMeta = {
    office:    { bg:'#eff6ff', color:'#1d4ed8', border:'#bfdbfe', edu:false },
    personal:  { bg:'#f0fdf4', color:'#166534', border:'#bbf7d0', edu:false },
    address:   { bg:'#fff7ed', color:'#9a3412', border:'#fed7aa', edu:false },
    education: { bg:'#faf5ff', color:'#6b21a8', border:'#e9d5ff', edu:true  },
    basic:     { bg:'#f0fdf4', color:'#166534', border:'#bbf7d0', edu:false },
};

// ─── Section toggle (Quick form) ───────────────────────────────────
function toggleSection(input) {
    const sKey    = input.dataset.section;
    const enabled = input.checked;

    // Left panel — show/hide fields
    const fieldsDiv = document.getElementById('fields_' + sKey);
    if (fieldsDiv) fieldsDiv.style.display = enabled ? '' : 'none';

    // Right panel — show/hide preview section
    const previewWrap = document.getElementById('preview_section_wrap_' + sKey);
    if (previewWrap) previewWrap.style.display = enabled ? '' : 'none';
}

// ─── Field toggle ──────────────────────────────────────────────────
function toggleField(input) {
    const key      = input.dataset.key;
    const label    = input.dataset.label;
    const section  = input.dataset.section;
    const formType = input.dataset.type;
    const enabled  = input.checked;
    const existing = document.getElementById('preview_' + key);

    // Receipt — direct show/hide
    if (formType === 'receipt') {
        const el = document.getElementById('prev_' + key);
        if (el) el.style.display = enabled ? '' : 'none';
        return;
    }

    if (!enabled) { if (existing) existing.remove(); return; }

    const container = document.getElementById('preview_fields_' + section);
    if (!container) return;

    const req  = document.getElementById('req_' + key)?.checked || false;
    const star = req ? '<span style="color:red">*</span>' : '';
    const m    = sectionMeta[section] || sectionMeta.office;

    let html = '';
    if (m.edu) {
        html = `<div id="preview_${key}"
                     class="d-flex align-items-center gap-1 mb-1 px-2 py-1 rounded"
                     style="background:${m.bg};border:1px solid ${m.border};font-size:11px;color:${m.color};">
                    <i class="bi bi-table small"></i>
                    <span class="fw-semibold">${label}</span>${star}
                </div>`;
    } else {
        html = `<span id="preview_${key}"
                      class="badge border fw-normal px-2 mb-1"
                      style="background:${m.bg};color:${m.color};border-color:${m.border}!important;font-size:11px;">
                    ${label}${star}
                </span>`;
    }

    if (existing) existing.outerHTML = html;
    else container.insertAdjacentHTML('beforeend', html);
}

// ─── Required toggle ───────────────────────────────────────────────
function toggleRequired(input) {
    const key = input.dataset.key;
    const el  = document.getElementById('preview_' + key);
    if (!el) return;
    el.querySelectorAll('span[style*="red"]').forEach(s => s.remove());
    if (input.checked) el.insertAdjacentHTML('beforeend', '<span style="color:red">*</span>');
}

// Row hover
document.querySelectorAll('.field-row').forEach(row => {
    row.addEventListener('mouseenter', () => row.style.background = '#f1f5f9');
    row.addEventListener('mouseleave', () => row.style.background = '');
});

// ─── Print Form Preview ────────────────────────────────────────────
function printFormPreview() {
    const instituteName    = @json($_inst->name    ?? '');
    const instituteAddress = @json($_instAddr);
    const instituteMobile  = @json($_inst->mobile  ?? '');
    const instituteEmail   = @json($_inst->email   ?? '');
    const logoUrl          = @json($_instLogo);
    const formLabel        = @json($formInfo['label']);

    // ── Collect sections + fields from live preview DOM ──────────────
    let sectionsHtml = '';

    document.querySelectorAll('[id^="preview_section_wrap_"]').forEach(wrap => {
        if (wrap.style.display === 'none') return;

        // Section label from the dark header bar
        const headerEl = wrap.querySelector('[style*="background:#1e293b"]');
        const sLabel   = headerEl ? headerEl.textContent.trim() : '';

        // Field elements: id="preview_{key}" — disabled ones are removed from DOM
        const fields = [];
        wrap.querySelectorAll('[id^="preview_"]:not([id*="fields_"])').forEach(el => {
            const reqEl  = el.querySelector('span[style*="color:red"]');
            const text   = el.textContent.trim().replace('*', '').trim();
            if (text) fields.push({ label: text, required: !!reqEl });
        });

        if (!fields.length) return;

        // 3 label-value pairs per row
        let rows = '';
        for (let i = 0; i < fields.length; i += 3) {
            const cells = [fields[i], fields[i+1] || null, fields[i+2] || null].map(f =>
                f ? `<td class="lbl">${f.label}${f.required ? '<span class="req">*</span>' : ''}</td><td class="val"></td>`
                  : `<td class="lbl"></td><td class="val"></td>`
            ).join('');
            rows += `<tr>${cells}</tr>`;
        }

        sectionsHtml += `
        <div class="sec-block">
            <div class="sec-head">${sLabel}</div>
            <table class="sec-tbl"><tbody>${rows}</tbody></table>
        </div>`;
    });

    // Course & Academic Details — always present
    sectionsHtml += `
    <div class="sec-block">
        <div class="sec-head">Course &amp; Academic Details</div>
        <table class="sec-tbl"><tbody>
            <tr>
                <td class="lbl">Course Name<span class="req">*</span></td><td class="val"></td>
                <td class="lbl">Stream / Subject</td><td class="val"></td>
                <td class="lbl">Semester / Year</td><td class="val"></td>
            </tr>
        </tbody></table>
    </div>`;

    // ── Logo / photo ─────────────────────────────────────────────────
    const logoHtml = logoUrl
        ? `<img src="${logoUrl}" alt="Logo" style="height:65px;width:65px;object-fit:contain;">`
        : `<div style="height:65px;width:65px;border:1px solid #cbd5e1;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;">&#127891;</div>`;

    const contactLine = [
        instituteMobile ? 'Mobile: ' + instituteMobile : '',
        instituteEmail  ? 'Website: ' + instituteEmail : '',
    ].filter(Boolean).join(' | ');

    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>${formLabel} — ${instituteName}</title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#1e293b; padding:14px 18px; }

  /* Header */
  .hdr { width:100%; border-collapse:collapse; margin-bottom:6px; }
  .hdr .logo-cell  { width:80px; vertical-align:middle; }
  .hdr .info-cell  { text-align:center; vertical-align:middle; padding:0 8px; }
  .hdr .info-cell h1 { font-size:19px; font-weight:bold; margin-bottom:3px; }
  .hdr .info-cell p  { font-size:10px; color:#475569; line-height:1.6; }
  .hdr .photo-cell { width:80px; vertical-align:middle; text-align:right; }
  .photo-box { width:70px; height:85px; border:1px solid #94a3b8; display:inline-flex;
               align-items:center; justify-content:center; font-size:10px;
               color:#94a3b8; text-align:center; flex-direction:column; gap:3px; }

  hr.div { border:none; border-top:2px solid #1e293b; margin:6px 0 8px; }

  /* Form title */
  .form-title { border:1.5px solid #1e293b; text-align:center; padding:5px;
                font-size:13px; font-weight:bold; letter-spacing:3px; margin-bottom:10px; }

  /* Top identity fields */
  .top-tbl { width:100%; border-collapse:collapse; margin-bottom:10px; }
  .top-tbl .tl { color:#64748b; padding:4px 5px; white-space:nowrap; font-size:10.5px; }
  .top-tbl .tv { border-bottom:1px solid #94a3b8; padding:4px 5px; }

  /* Sections */
  .sec-block { margin-bottom:10px; }
  .sec-head  { background:#1e293b; color:white; padding:4px 8px; font-size:11px; font-weight:bold; }
  .sec-tbl   { width:100%; border-collapse:collapse; border:1px solid #e2e8f0; }
  .sec-tbl .lbl { padding:5px 6px; color:#475569; border:1px solid #e2e8f0; width:14%;
                  background:#f8fafc; white-space:nowrap; font-size:10.5px; }
  .sec-tbl .val { padding:5px 6px; border:1px solid #e2e8f0; width:19%; font-size:10.5px; }
  .req { color:red; }

  /* Signatures */
  .sigs { display:flex; justify-content:space-between; margin-top:20px;
          padding-top:8px; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; }

  @@media print {
    body { padding:8px 12px; }
    @@page { margin:8mm; size:A4 portrait; }
  }
</style>
</head>
<body>

<!-- ── HEADER ─────────────────────────────────────── -->
<table class="hdr">
  <tr>
    <td class="logo-cell">${logoHtml}</td>
    <td class="info-cell">
      <h1>${instituteName}</h1>
      ${instituteAddress ? `<p>${instituteAddress}</p>` : ''}
      ${contactLine      ? `<p>${contactLine}</p>`      : ''}
    </td>
    <td class="photo-cell">
      <div class="photo-box">
        <span style="font-size:22px;">&#128100;</span>
        <span>Photo</span>
      </div>
    </td>
  </tr>
</table>

<hr class="div">

<!-- ── FORM TITLE ─────────────────────────────────── -->
<div class="form-title">${formLabel.toUpperCase()}</div>

<!-- ── TOP IDENTITY FIELDS ────────────────────────── -->
<table class="top-tbl">
  <tr>
    <td class="tl">Application No.</td><td class="tv"></td>
    <td class="tl">Name</td><td class="tv"></td>
    <td class="tl">Mobile</td><td class="tv"></td>
  </tr>
  <tr>
    <td class="tl">Email Id</td><td class="tv"></td>
    <td class="tl">Admission Date</td><td class="tv"></td>
    <td class="tl"></td><td style="padding:4px 5px;"></td>
  </tr>
</table>

<!-- ── SECTIONS ───────────────────────────────────── -->
${sectionsHtml}

<!-- ── SIGNATURES ─────────────────────────────────── -->
<div class="sigs">
  <span>Student Signature: _______________________</span>
  <span>Office Signature: _______________________</span>
</div>

<script>window.onload = function(){ window.print(); }<\/script>
</body>
</html>`;

    const win = window.open('', '_blank', 'width=960,height=750');
    win.document.write(html);
    win.document.close();
}
</script>
@endpush
@endsection
