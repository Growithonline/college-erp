<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print — {{ $student->name }} — {{ $student->student_uid }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:Arial,sans-serif; background:#f1f5f9; font-size:11px; }

/* ── Page shell ── */
.page-shell  { display:flex; flex-direction:column; height:100dvh; }
.workspace   { display:flex; flex:1; min-height:0; overflow:hidden; }
.preview-pane { flex:1; overflow-y:auto; padding:16px; min-width:0; background:#f1f5f9; }

/* ── Top quick-links bar ── */
.top-bar { background:#1e293b; flex-shrink:0; }
.top-bar-inner { display:flex; align-items:center; gap:6px; padding:7px 12px; flex-wrap:wrap; }
.student-tag { color:#94a3b8; font-size:11px; font-weight:600; white-space:nowrap; }
.top-links { display:flex; flex-wrap:wrap; gap:5px; margin-left:auto; }
.tl-btn { padding:5px 11px; border-radius:5px; font-size:11px; font-weight:600;
          text-decoration:none; border:none; cursor:pointer; display:inline-block; white-space:nowrap; line-height:1.4; }
.tl-purple { background:#7c3aed; color:#fff; }
.tl-green  { background:#16a34a; color:#fff; }
.tl-blue   { background:#1d4ed8; color:#fff; }
.tl-slate  { background:#475569; color:#fff; }
.tl-ghost  { background:transparent; color:#94a3b8; border:1px solid #475569; }

/* ── Left Sidebar ── */
.sidebar { width:186px; flex-shrink:0; background:#0f172a; overflow-y:auto;
           padding:10px 7px; display:flex; flex-direction:column; }
.sb-section { margin-bottom:12px; }
.sb-label { font-size:8px; font-weight:700; color:#475569; text-transform:uppercase;
            letter-spacing:.8px; padding:0 4px 5px; border-bottom:1px solid #1e293b; margin-bottom:4px; }
.sb-btn { display:flex; align-items:center; gap:6px; width:100%; text-align:left;
          padding:7px 10px; border:none; border-radius:5px; font-size:11px; font-weight:600;
          cursor:pointer; margin-bottom:2px; transition:background .12s, color .12s; }
.sb-view { background:#1e293b; color:#94a3b8; }
.sb-view:hover, .sb-view.active { background:#1d4ed8; color:#fff; }
.sb-print { background:#172554; color:#93c5fd; }
.sb-print:hover { background:#1d4ed8; color:#fff; }

/* ── Responsive ── */
@media (max-width:900px) { .sidebar { width:160px; } }
@media (max-width:768px) {
    html, body { height:auto; overflow:auto; }
    .page-shell { height:auto; }
    .workspace  { flex-direction:column; overflow:visible; }
    .sidebar    { width:100%; flex-direction:row; flex-wrap:wrap;
                  overflow-y:visible; overflow-x:auto; padding:6px 8px; gap:4px; }
    .sb-section { display:contents; }
    .sb-label   { display:none; }
    .sb-btn     { width:auto; flex-shrink:0; font-size:10px; padding:6px 10px; margin:0; }
    .preview-pane { padding:8px; overflow:visible; }
    .top-links  { margin-left:0; margin-top:4px; width:100%; }
}

/* ══ APPLICATION FORM ══ */
.app-sheet { width:210mm; min-height:297mm; background:white; margin:0 auto;
             padding:12mm 14mm; border:1px solid #ddd; font-size:10px; }

.app-header { display:flex; align-items:center; gap:10px; border-bottom:2px solid #000;
              padding-bottom:8px; margin-bottom:10px; }
.logo-box { width:55px; height:55px; flex-shrink:0; border:1px solid #ccc; overflow:hidden;
            display:flex; align-items:center; justify-content:center; }
.logo-box img { width:100%; height:100%; object-fit:contain; }
.inst-info { flex:1; text-align:center; }
.inst-name { font-size:18px; font-weight:900; }
.inst-sub  { font-size:10px; color:#444; margin-top:1px; }
.photo-box { width:30mm; height:38mm; border:1px solid #333; flex-shrink:0; overflow:hidden;
             display:flex; align-items:center; justify-content:center; }
.photo-box img { width:100%; height:100%; object-fit:cover; }
.photo-placeholder { text-align:center; color:#999; font-size:9px; }

.form-title { text-align:center; font-size:13px; font-weight:700; letter-spacing:2px;
              padding:5px; border:1px solid #000; margin:8px 0 10px; }

.basic-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:5px;
              border:1px solid #ccc; padding:8px; margin-bottom:8px; }
.bfield .lbl { font-size:9px; color:#666; font-weight:600; display:block; }
.bfield .val { font-size:11px; font-weight:700; border-bottom:1px solid #555;
               padding-bottom:1px; min-height:16px; display:block; }

.sec-head { font-size:10px; font-weight:700; background:#1e293b; color:white;
            padding:3px 8px; margin:8px 0 4px; }

.ftbl { width:100%; border-collapse:collapse; margin-bottom:6px; }
.ftbl td { border:1px solid #ccc; padding:4px 6px; font-size:10px; vertical-align:top; }
.ftbl .lc { font-weight:700; background:#f8fafc; width:20%; white-space:nowrap; }
.ftbl .vc { font-weight:600; }

.edu-tbl { width:100%; border-collapse:collapse; font-size:9.5px; }
.edu-tbl th { background:#1e293b; color:white; padding:3px 5px; border:1px solid #000; }
.edu-tbl td { border:1px solid #ccc; padding:3px 5px; height:18px; }

.sign-row { display:flex; justify-content:space-between; margin-top:14mm; }
.sign-box .line { border-top:1px solid #333; width:50mm; padding-top:3px;
                  font-size:9px; color:#555; text-align:center; }

/* ══ FEE RECEIPT A4 ══ */
.rcpt-sheet { width:210mm; background:white; margin:0 auto; border:1px solid #ddd;
              display:grid; grid-template-columns:1fr 1fr; }
.rcpt-col { padding:8mm 7mm; border:1px solid #e2e8f0; }

.rcpt-top { overflow:hidden; margin-bottom:4px; }
.copy-badge { float:right; font-size:9px; font-weight:700; padding:2px 7px;
              border-radius:3px; }
.badge-student { background:#dbeafe; color:#1d4ed8; }
.badge-office  { background:#dcfce7; color:#166534; }

.rcpt-logo-row { display:flex; align-items:center; gap:6px; margin-bottom:4px; }
.rcpt-logo { width:32px; height:32px; }
.rcpt-logo img { width:100%; height:100%; object-fit:contain; }
.rcpt-inst-name { font-size:12px; font-weight:800; }
.rcpt-inst-sub  { font-size:8px; color:#555; }

.rcpt-title { text-align:center; font-size:11px; font-weight:700; letter-spacing:1px;
              border-top:1px solid #333; border-bottom:1px solid #333;
              padding:3px; margin:5px 0; }

.rcpt-info { display:grid; grid-template-columns:1fr 1fr; gap:2px; margin-bottom:5px; }
.rinfo { font-size:8.5px; }
.rinfo .rl { color:#555; }
.rinfo .rv { font-weight:700; }

.rfee-tbl { width:100%; border-collapse:collapse; font-size:8.5px; margin:5px 0; }
.rfee-tbl th { background:#1e293b; color:white; padding:3px 4px; border:1px solid #000; }
.rfee-tbl td { border:1px solid #ccc; padding:3px 4px; }
.rfee-tbl .amt { text-align:right; }
.rfee-tbl .total-row td { font-weight:700; background:#f8fafc; border-top:2px solid #000; }
.rfee-tbl .disc-row td { color:#dc2626; }

.rcpt-mode { font-size:8px; margin-top:4px; color:#333; }
.rcpt-signs { display:flex; justify-content:space-between; margin-top:8mm; }
.rcpt-sign-box { border-top:1px solid #888; width:38mm; text-align:center;
                 padding-top:2px; font-size:8px; color:#555; }

/* â•â• ADMISSION SLIP A4 â•â• */

/* Fee Receipt A4 - Modern style */
.a4-sheet { width:210mm; min-height:148mm; background:white; margin:0 auto;
            display:grid; grid-template-columns:1fr 1fr;
            border:1px solid #cbd5e1; border-radius:4px; overflow:hidden; }
.receipt  { padding:7mm 6mm 6mm; border-right:2px dashed #94a3b8;
            position:relative; overflow:hidden; display:flex; flex-direction:column; }
.receipt:last-child { border-right:none; }
.copy-badge   { display:inline-block; font-size:7.5px; font-weight:700;
                padding:2px 7px; border-radius:20px; text-transform:uppercase;
                letter-spacing:.6px; margin-bottom:5px; align-self:flex-end; }
.copy-student   { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.copy-institute { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.r-header { text-align:center; padding-bottom:5px; margin-bottom:6px;
            border-bottom:2.5px solid #0f766e; position:relative; }
.r-header::before { content:''; display:block; height:3px;
                    background:linear-gradient(90deg,#0f766e,#0891b2);
                    border-radius:2px; margin-bottom:5px; }
.r-header .inst  { font-size:13.5px; font-weight:800; color:#0f172a; letter-spacing:.2px; }
.r-header .title { font-size:8.5px; color:#64748b; text-transform:uppercase;
                   letter-spacing:1.5px; margin-top:2px; }
.r-header .session-tag { font-size:8px; color:#0f766e; font-weight:600;
                          background:#f0fdfa; border:1px solid #99f6e4;
                          border-radius:10px; padding:1px 7px; display:inline-block; margin-top:3px; }
.r-meta-bar { display:flex; justify-content:space-between; align-items:center;
              background:#f8fafc; border:1px solid #e2e8f0; border-radius:5px;
              padding:4px 7px; margin-bottom:6px; font-size:8.5px; }
.r-meta-bar .inv-no  { font-weight:700; color:#0f172a; font-size:9px; }
.r-meta-bar .inv-date { color:#475569; text-align:right; line-height:1.5; }
.r-meta-bar .inv-date b { color:#0f172a; }
.r-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 6px; margin-bottom:6px; }
.r-row { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row .val { font-weight:600; color:#1e293b; flex:1; }
.r-row-full { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row-full .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row-full .val { font-weight:600; color:#1e293b; }
.r-divider { border:none; border-top:1px dashed #e2e8f0; margin:5px 0; }
.pay-box { background:linear-gradient(135deg,#f0fdf4,#f0fdfa);
           border:1.5px solid #6ee7b7; border-radius:6px; overflow:hidden; margin:5px 0; }
.pay-box-head { background:linear-gradient(90deg,#0f766e,#0891b2);
                color:white; font-size:8.5px; font-weight:700;
                padding:3px 8px; letter-spacing:.3px; }
.pay-box-body { padding:5px 8px; }
.pay-row { display:flex; justify-content:space-between; align-items:center;
           font-size:8.5px; padding:2px 0; }
.pay-row .pay-lbl { color:#475569; }
.pay-row .pay-val { font-weight:700; color:#0f172a; }
.pay-divider { border:none; border-top:1px solid #d1fae5; margin:3px 0; }
.amount-big { font-size:14px; font-weight:800; color:#15803d; }
.amount-due { font-size:12px; font-weight:700; }
.due-zero   { color:#15803d; }
.due-pos    { color:#dc2626; }
.mode-chip  { display:inline-block; font-size:7.5px; font-weight:700;
              padding:2px 8px; border-radius:20px; text-transform:uppercase;
              letter-spacing:.5px; background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.r-note   { font-size:7.5px; color:#94a3b8; font-style:italic; margin-top:3px; }
.r-footer { display:flex; justify-content:space-between; margin-top:auto;
            padding-top:5px; border-top:1px dashed #e2e8f0; }
.r-sign   { border-top:1px solid #94a3b8; width:65px; text-align:center;
            font-size:7.5px; padding-top:3px; color:#64748b; }
.watermark { position:absolute; top:50%; left:50%;
             transform:translate(-50%,-50%) rotate(-25deg);
             font-size:48px; font-weight:900;
             color:rgba(15,118,110,0.04); pointer-events:none; white-space:nowrap; }
.slip-sheet { width:210mm; min-height:297mm; background:white; margin:0 auto; border:1px solid #ddd;
              padding:12mm 14mm; }
.slip-banner { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 10px;
               border:1px solid #bfdbfe; background:#eff6ff; margin-bottom:10px; }
.slip-banner h2 { font-size:16px; font-weight:800; color:#1d4ed8; }
.slip-banner p { font-size:10px; color:#475569; margin-top:2px; }
.slip-status { padding:6px 10px; border-radius:999px; background:#dcfce7; color:#166534; font-size:10px; font-weight:800; }
.slip-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
.slip-card { border:1px solid #cbd5e1; padding:8px; background:#fff; }
.slip-card-title { font-size:10px; font-weight:800; color:#0f172a; margin-bottom:6px; text-transform:uppercase; letter-spacing:1px; }
.slip-meta { display:grid; grid-template-columns:1fr 1fr; gap:5px 10px; }
.slip-meta-item { border-bottom:1px dashed #cbd5e1; padding-bottom:3px; }
.slip-meta-item .label { font-size:8px; color:#64748b; display:block; }
.slip-meta-item .value { font-size:11px; font-weight:700; color:#0f172a; display:block; margin-top:1px; }
.slip-note { font-size:10px; color:#334155; line-height:1.5; }
.slip-receipt-head { display:flex; justify-content:space-between; align-items:end; gap:10px; margin-bottom:6px; }
.slip-total-box { text-align:right; }
.slip-total-box .label { font-size:8px; color:#64748b; display:block; }
.slip-total-box .value { font-size:16px; font-weight:800; color:#166534; display:block; }
.slip-sign-row { display:flex; justify-content:space-between; margin-top:14mm; }
.slip-sign { width:55mm; border-top:1px solid #64748b; text-align:center; padding-top:4px; font-size:9px; color:#64748b; }

/* ══ THERMAL RECEIPT ══ */
.thermal-sheet { width:76mm; max-width:76mm; background:#ffffff; margin:0 auto; padding:2mm;
                 border:1px solid #ccc; font-family:Verdana,sans-serif; font-size:14px; font-weight:700; line-height:1.4; color:#000; }
.thermal-sheet, .thermal-sheet * { color:#000 !important; font-weight:700; }

/* Header */
.th-inst-name { font-size:21px; font-weight:800; color:#000; text-align:center; margin:0 0 4px 0; }
.th-addr      { font-size:14px; font-weight:700; color:#000; text-align:center; }
.th-hdr       { padding:0 0 6px 0; border-bottom:1px solid #000; margin-bottom:4px; }

/* Section titles */
.th-section   { font-size:14px; font-weight:600; text-align:center; padding:4px;
                border:1px solid #000; margin:4px 0; }
.th-course    { font-size:14px; font-weight:600; text-align:center; padding:3px 0; }

/* Detail rows (student info + receipt info) */
.th-tbl       { width:100%; border-collapse:collapse; }
.th-tbl td    { font-size:14px; font-weight:600; padding:2px 0; border-bottom:0.8px solid #eeeeee; }
.th-tbl .tval { text-align:right; }

/* Receipt amount */
.th-amount td { font-size:16px; font-weight:700; padding:1px 0; border-bottom:0.8px solid #eeeeee; }
.th-amount .tval { text-align:right; }

/* Footer rows */
.th-balance   { font-size:14px; font-weight:700; padding:1px 0; border-bottom:0.8px solid #000; }
.th-foot-row  { font-size:14px; font-weight:700; padding:1px 0; }
.th-center    { text-align:center; }
.th-note      { font-size:14px; font-weight:700; text-align:center; padding:8px 0 2px 0; }
.th-legal     { text-align:center; font-size:12px; font-weight:600; margin-top:5mm; }
.th-sign      { display:flex; justify-content:space-between; margin-top:7mm; font-size:12px; font-weight:700; border-top:1px solid #000; padding-top:3px; }

/* Slip thermal — legacy classes still used */
.tc { text-align:center; }
.tb { font-weight:800; }
.tline { border-bottom:1px dashed #333; margin:4px 0; }
.tline-solid { border-bottom:2px solid #000; margin:4px 0; }
.trow { display:flex; justify-content:space-between; align-items:flex-start; gap:2mm; font-size:12px; margin-bottom:2px; }
.tl { flex:1 1 30mm; min-width:0; font-weight:700; }
.tr { flex:0 0 auto; max-width:40mm; text-align:right; white-space:normal; word-break:break-word; padding-left:2px; font-weight:800; }
.ttotal { display:flex; justify-content:space-between; gap:2mm; font-size:15px; font-weight:900;
          border-top:2px solid #000; padding-top:4px; margin-top:4px; }
.tsign { display:flex; justify-content:space-between; margin-top:7mm; font-size:11px; font-weight:700; }

@media print {
    html, body { background:white; height:auto !important; overflow:visible !important; }
    body.thermal-print-active { width:80mm; margin:0 !important; padding:0 !important; }
    .top-bar, .sidebar { display:none !important; }
    .page-shell   { display:block !important; height:auto !important; }
    .workspace    { display:block !important; overflow:visible !important; }
    .preview-pane { overflow:visible !important; padding:0 !important; }
    #thermalView, #slipThermalView { margin-top:0 !important; }
    body.thermal-print-active #thermalView,
    body.thermal-print-active #slipThermalView {
        transform: translateY(-9mm);
        margin-bottom: -9mm !important;
    }

    /* Thermal — reduced top/bottom margin */
    #thermalView .thermal-sheet {
        border: none !important;
        margin: 0 !important;
        padding: 2mm 2.6mm !important;
        width: 76mm !important;
        max-width: 76mm !important;
    }
    #slipThermalView .thermal-sheet {
        border: none !important;
        margin: 0 !important;
        padding: 2mm 2.6mm !important;
        width: 76mm !important;
        max-width: 76mm !important;
    }
    /* A4 receipt — full width so office copy is not clipped */
    #rcptView .a4-sheet {
        width: 100% !important;
        border: none !important;
        border-radius: 0 !important;
    }
    /* A4 app/slip */
    #appView .app-sheet, #slipView .slip-sheet {
        border: none !important;
    }
}
</style>
</head>
<body>
<div class="page-shell">
@php
    $panel = auth()->guard('staff')->check()
        ? 'staff'
        : (auth()->guard('center')->check()
            ? 'center'
            : (auth()->guard('partner')->check() ? 'partner' : 'institute'));
    $profileUrl = $panel === 'center'
        ? route('center.students.show', $student->id)
        : ($panel === 'partner'
            ? route('partner.students.show', $student->id)
            : ($panel === 'institute' ? route('admissions.show', $student->id) : route('staff.admissions.index')));
    $listUrl = $panel === 'staff'
        ? route('staff.admissions.index')
        : ($panel === 'center'
            ? route('center.students.index')
            : ($panel === 'partner' ? route('partner.students.index') : route('admissions.index')));
    $dashboardUrl = $panel === 'institute'
        ? route('institute.dashboard')
        : ($panel === 'center'
            ? route('center.dashboard')
            : ($panel === 'staff'
                ? route('staff.dashboard')
                : route('partner.dashboard')));
    $quickCreateUrl = $panel === 'staff'
        ? route('staff.admissions.quick-create')
        : ($panel === 'center'
            ? route('center.admissions.quick-create')
            : ($panel === 'partner' ? route('partner.admissions.quick-create') : route('admissions.quick-create')));
    $fullCreateUrl = $panel === 'staff'
        ? route('staff.admissions.create')
        : ($panel === 'center'
            ? route('center.admissions.create')
            : ($panel === 'partner' ? route('partner.admissions.create') : route('admissions.create')));
@endphp

{{-- Top Quick Links --}}
<div class="top-bar">
    <div class="top-bar-inner">
        <span class="student-tag">{{ $student->name }}</span>
        <span style="color:#334155;font-size:10px;">|</span>
        <span class="student-tag" style="color:#64748b;font-weight:400;font-size:10px;">{{ $student->student_uid }}</span>
        <div class="top-links">
            <a href="{{ $fullCreateUrl }}"  class="tl-btn tl-purple">+ New Full Admission</a>
            <a href="{{ $quickCreateUrl }}" class="tl-btn tl-green">⚡ Quick Admission</a>
            <a href="{{ $profileUrl }}"     class="tl-btn tl-blue">👤 Student Profile</a>
            <a href="{{ $listUrl }}"        class="tl-btn tl-slate">← Admissions List</a>
            <a href="{{ $dashboardUrl }}"   class="tl-btn tl-ghost">🏠 Dashboard</a>
        </div>
    </div>
</div>

{{-- Main Workspace --}}
<div class="workspace">

{{-- Left Sidebar --}}
<div class="sidebar">
    <div class="sb-section">
        <div class="sb-label">Print</div>
        <button class="sb-btn sb-print" onclick="printApp()">📄 Application Form</button>
        @if($invoice)
        <button class="sb-btn sb-print" onclick="printA4()">🧾 Fee Receipt A4</button>
        <button class="sb-btn sb-print" onclick="printThermal()">🖨 Thermal Receipt</button>
        <button class="sb-btn sb-print" onclick="printBoth()">📦 Both Receipts</button>
        @endif
        <button class="sb-btn sb-print" onclick="printSlip()">🎓 Admission Slip</button>
        <button class="sb-btn sb-print" onclick="printSlipThermal()">📋 Slip Thermal</button>
    </div>
    <div class="sb-section">
        <div class="sb-label">Preview</div>
        <button class="sb-btn sb-view active" id="sbApp"          onclick="showTab('app')">📄 Application Form</button>
        @if($invoice)
        <button class="sb-btn sb-view"        id="sbRcpt"         onclick="showTab('rcpt')">🧾 Fee Receipt A4</button>
        <button class="sb-btn sb-view"        id="sbThermal"      onclick="showTab('thermal')">🖨 Thermal Fee</button>
        @endif
        <button class="sb-btn sb-view"        id="sbSlip"         onclick="showTab('slip')">🎓 Admission Slip</button>
        <button class="sb-btn sb-view"        id="sbSlipThermal"  onclick="showTab('slipThermal')">📋 Slip Thermal</button>
    </div>
</div>

{{-- Preview Pane --}}
<div class="preview-pane" id="previewPane">

{{-- ═══════════════════════════════════════ --}}
{{-- APPLICATION FORM                        --}}
{{-- ═══════════════════════════════════════ --}}
<div id="appView">
<div class="app-sheet">

    <div class="app-header">
        <div class="logo-box">
            @if($institute?->image)
                <img src="{{ Storage::url($institute->image) }}" alt="">
            @else
                <span style="font-size:8px;color:#999;">Logo</span>
            @endif
        </div>

        <div class="inst-info">
            <div class="inst-name">{{ $institute->name ?? '' }}</div>
            <div class="inst-sub">{{ $institute->address ?? '' }}@if($institute?->city), {{ $institute->city }}@endif</div>
            @if($institute?->mobile)<div class="inst-sub">Mobile: {{ $institute->mobile }}@if($institute?->email) | Website: {{ $institute->email }}@endif</div>@endif
        </div>

        <div class="photo-box">
            @if($student->photo)
                <img src="{{ Storage::url($student->photo) }}" alt="">
            @else
                <div class="photo-placeholder"><div style="font-size:30px;color:#ccc;">👤</div><div>Photo</div></div>
            @endif
        </div>
    </div>

    <div class="form-title">APPLICATION FORM</div>

    <div class="basic-grid">
        <div class="bfield"><span class="lbl">Application No</span><span class="val">{{ $student->student_uid }}</span></div>
        <div class="bfield"><span class="lbl">Name</span><span class="val">{{ $student->name }}</span></div>
        <div class="bfield"><span class="lbl">Mobile</span><span class="val">{{ $student->mobile }}</span></div>
        @if($student->email)<div class="bfield"><span class="lbl">Email Id</span><span class="val">{{ $student->email }}</span></div>@endif
        @if($student->dob)<div class="bfield"><span class="lbl">Date Of Birth</span><span class="val">{{ $student->dob?->format('d M Y') }}</span></div>@endif
        <div class="bfield"><span class="lbl">Admission Date</span><span class="val">{{ $student->admission_date?->format('d/m/Y') }}</span></div>
    </div>

    @php
        $semOrdinal  = match((int)($student->current_semester ?? 1)) { 1=>'1st', 2=>'2nd', default=>$student->current_semester };
        $yearSemLabel = ($student->coursePart?->year_label ?? '') . '/' . $semOrdinal . ' Sem';
    @endphp

    <div class="sec-head">Office Details</div>
    <table class="ftbl">
        {{-- Row 1: Primary identifiers — always at top --}}
        <tr>
            <td class="lc">Form No.</td><td class="vc">{{ $student->institute_form_no ?? '' }}</td>
            <td class="lc">Serial No.</td><td class="vc">{{ $student->currentAcademicIdentity?->form_no ?? $student->id }}</td>
            <td class="lc">SR No.</td><td class="vc">{{ $student->sr_no ?? '' }}</td>
        </tr>
        @php
            $topIds = [];
            if (($formConfig['enrollment_no']['enabled'] ?? false) && ($formConfig['enrollment_no']['section_enabled'] ?? true))
                $topIds[] = ['Enrollment No.', $student->enrollment_no ?? ''];
            if (($formConfig['roll_no']['enabled'] ?? false) && ($formConfig['roll_no']['section_enabled'] ?? true))
                $topIds[] = ['Roll No.', $student->roll_no ?? ''];
            if (($formConfig['exam_form_no']['enabled'] ?? false) && ($formConfig['exam_form_no']['section_enabled'] ?? true))
                $topIds[] = ['Exam Form No.', $student->exam_form_no ?? ''];
            if (($formConfig['uin_no']['enabled'] ?? false) && ($formConfig['uin_no']['section_enabled'] ?? true))
                $topIds[] = ['UIN No.', $student->uin_no ?? ''];
            if (($formConfig['reference_no']['enabled'] ?? false) && ($formConfig['reference_no']['section_enabled'] ?? true))
                $topIds[] = ['Reference No.', $student->reference_no ?? ''];
            $topIdRows = array_chunk($topIds, 3);
        @endphp
        @foreach($topIdRows as $row)
        <tr>
            @foreach($row as $cell)
            <td class="lc">{{ $cell[0] }}</td><td class="vc">{{ $cell[1] }}</td>
            @endforeach
            @for($i = count($row); $i < 3; $i++)
            <td class="lc"></td><td class="vc"></td>
            @endfor
        </tr>
        @endforeach
        {{-- Course & session info --}}
        <tr>
            <td class="lc">Course</td><td class="vc">{{ $student->stream->course->name ?? '' }}</td>
            <td class="lc">Stream</td><td class="vc">{{ $student->stream->name ?? '' }}</td>
            <td class="lc">Year/Sem</td><td class="vc">{{ $yearSemLabel }}</td>
        </tr>
        <tr>
            <td class="lc">Session</td><td class="vc">{{ $student->session->name ?? '' }}</td>
            <td class="lc">Admission Date</td><td class="vc">{{ $student->admission_date?->format('d/m/Y') }}</td>
            <td class="lc">Submitted Date</td><td class="vc">{{ optional($student->submitted_date ?? $student->created_at)->format('d/m/Y') }}</td>
        </tr>
        {{-- Admission classification --}}
        <tr>
            <td class="lc">Admission Type</td><td class="vc">{{ ucfirst($student->admission_type ?? '') }}</td>
            <td class="lc">Student Type</td><td class="vc">{{ ucfirst($student->student_type ?? '') }}</td>
            <td class="lc">Admission Source</td><td class="vc">{{ ucfirst(str_replace('_', ' ', $student->admission_source ?? '')) }}{{ $admissionSourceName ? ' — ' . $admissionSourceName : '' }}</td>
        </tr>
        <tr>
            <td class="lc">Gap Year</td><td class="vc">{{ $student->gap_year ? 'Yes' : 'No' }}</td>
            <td class="lc"></td><td class="vc"></td>
            <td class="lc"></td><td class="vc"></td>
        </tr>
    </table>

    <div class="sec-head">Course & Academic Details</div>
    <table class="ftbl">
        <tr>
            <td class="lc">Course Name</td>
            <td class="vc">{{ $student->stream->course->name ?? '' }}</td>
            <td class="lc">Year/Sem</td>
            <td class="vc">{{ $yearSemLabel }}</td>
        </tr>
        @php
            $majorSubs = ($subjects->get('major') ?? collect())->merge($subjects->get('both') ?? collect());
            $minorSubs = ($subjects->get('minor') ?? collect())->merge($subjects->get('optional') ?? collect());
            $compSubs  = $subjects->get('compulsory') ?? collect();
        @endphp
        @if($majorSubs->isNotEmpty())
        <tr>
            <td class="lc">Major Subject</td>
            <td class="vc" colspan="3">{{ $majorSubs->pluck('subject.name')->filter()->implode(', ') }}</td>
        </tr>
        @endif
        @if($minorSubs->isNotEmpty())
        <tr>
            <td class="lc">Minor Subject</td>
            <td class="vc" colspan="3">{{ $minorSubs->pluck('subject.name')->filter()->implode(', ') }}</td>
        </tr>
        @endif
        @if($compSubs->isNotEmpty())
        <tr>
            <td class="lc">Compulsory</td>
            <td class="vc" colspan="3">{{ $compSubs->pluck('subject.name')->filter()->implode(', ') }}</td>
        </tr>
        @endif
    </table>

    <div class="sec-head">Personal Details</div>
    <table class="ftbl">
        <tr>
            <td class="lc">Nationality</td><td class="vc">{{ ucfirst($student->nationality ?? 'Indian') }}</td>
            <td class="lc">Religion</td><td class="vc">{{ ucfirst($student->religion ?? '') }}</td>
            <td class="lc">Category</td><td class="vc">{{ strtoupper($student->category ?? '') }}</td>
        </tr>
        <tr>
            <td class="lc">Special Category</td><td class="vc">{{ strtoupper($student->special_category ?? 'NONE') }}</td>
            <td class="lc">Aadhar No</td><td class="vc">{{ $student->aadhar_no ?? '' }}</td>
            <td class="lc">Apaar No.</td><td class="vc">{{ $student->apaar_no ?? '' }}</td>
        </tr>
        <tr>
            <td class="lc">Marital Status</td><td class="vc">{{ ucfirst($student->marital_status ?? '') }}</td>
            <td class="lc">Gender</td><td class="vc">{{ ucfirst($student->gender ?? '') }}</td>
            <td class="lc"></td><td class="vc"></td>
        </tr>
        @if($student->spouse_name)
        <tr>
            <td class="lc">Spouse Name</td><td class="vc" colspan="5">{{ $student->spouse_name }}</td>
        </tr>
        @endif
    </table>

    <div class="sec-head">Guardian Details</div>
    <table class="ftbl">
        <tr>
            <td class="lc">Father Name</td><td class="vc">{{ $student->father_name ?? '' }}</td>
            <td class="lc">Father Mobile</td><td class="vc">{{ $student->father_mobile ?? '' }}</td>
            <td class="lc">Father Occupation</td><td class="vc">{{ $student->father_occupation ?? '' }}</td>
        </tr>
        <tr>
            <td class="lc">Mother Name</td><td class="vc">{{ $student->mother_name ?? '' }}</td>
            <td class="lc">Mother Occupation</td><td class="vc">{{ $student->mother_occupation ?? '' }}</td>
            <td class="lc">Guardian Name</td><td class="vc">{{ $student->guardian_name ?? '' }}</td>
        </tr>
        <tr>
            <td class="lc">Guardian Mobile</td><td class="vc">{{ $student->guardian_mobile ?? '' }}</td>
            <td class="lc">Relation</td><td class="vc" colspan="3">{{ $student->guardian_relation ?? '' }}</td>
        </tr>
    </table>

    @if($student->perm_village || $student->perm_district || $student->comm_city || $student->comm_district)
    <div class="sec-head">Address Details</div>
    <table class="ftbl">
        <tr>
            <td class="lc" style="width:13%">Present Address</td>
            <td class="vc" style="width:37%">
                @if($student->comm_same_as_perm)
                    <em style="color:#666;">Same as Permanent Address</em>
                @else
                    @if($student->comm_city)City/Village: {{ $student->comm_city }}<br>@endif
                    @if($student->comm_post)Post: {{ $student->comm_post }}<br>@endif
                    @if($student->comm_thana)Thana: {{ $student->comm_thana }}<br>@endif
                    @if($student->comm_district)District: {{ $student->comm_district }}<br>@endif
                    @if($student->comm_state)State: {{ $student->comm_state }}<br>@endif
                    @if($student->comm_pincode)PIN: {{ $student->comm_pincode }}@endif
                @endif
            </td>
            <td class="lc" style="width:13%">Permanent Address</td>
            <td class="vc" style="width:37%">
                @if($student->perm_village)City/Village: {{ $student->perm_village }}<br>@endif
                @if($student->perm_post)Post: {{ $student->perm_post }}<br>@endif
                @if($student->perm_thana)Thana: {{ $student->perm_thana }}<br>@endif
                @if($student->perm_district)District: {{ $student->perm_district }}<br>@endif
                @if($student->perm_state)State: {{ $student->perm_state }}<br>@endif
                @if($student->perm_pincode)PIN: {{ $student->perm_pincode }}@endif
            </td>
        </tr>
    </table>
    @endif

    <div class="sec-head">Education Details</div>
    <table class="edu-tbl">
        <thead><tr><th>Exam</th><th>Stream</th><th>Institute Name</th><th>Board / University</th><th>Roll No.</th><th>Year</th><th>Marks (Obt/Max)</th><th>%</th><th>Division</th><th>District</th></tr></thead>
        <tbody>
            @if($student->educationDetails->isNotEmpty())
                @foreach($student->educationDetails as $edu)
                <tr>
                    <td>{{ $edu->exam_name }}</td><td>{{ $edu->education_stream ?? '' }}</td>
                    <td>{{ $edu->institute_name }}</td>
                    <td>{{ $edu->board_university }}</td><td>{{ $edu->roll_number ?? '' }}</td>
                    <td>{{ $edu->passing_year }}</td>
                    <td>{{ $edu->obtained_marks ?? '' }}{{ ($edu->obtained_marks && $edu->max_marks) ? '/' : '' }}{{ $edu->max_marks ?? '' }}</td>
                    <td>{{ $edu->percentage }}</td><td>{{ $edu->division }}</td><td>{{ $edu->district ?? '' }}</td>
                </tr>
                @endforeach
            @else
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            @endif
        </tbody>
    </table>

    @if($student->has_scholarship)
    <div class="sec-head">Scholarship Details</div>
    <table class="ftbl">
        <tr>
            <td class="lc">Scholarship Name</td><td class="vc">{{ $student->scholarship_name ?? '' }}</td>
            <td class="lc">Type</td><td class="vc">{{ ucfirst($student->scholarship_type ?? '') }}</td>
            <td class="lc">Authority</td><td class="vc">{{ $student->scholarship_authority ?? '' }}</td>
        </tr>
        <tr>
            <td class="lc">Amount (₹)</td><td class="vc">{{ $student->scholarship_amount ?? '' }}</td>
            <td class="lc">Applied Date</td><td class="vc">{{ $student->scholarship_applied_date?->format('d/m/Y') ?? '' }}</td>
            <td class="lc">Ref No.</td><td class="vc">{{ $student->scholarship_ref_no ?? '' }}</td>
        </tr>
    </table>
    @endif

    <div class="sign-row">
        <div class="sign-box"><div class="line">Student Signature</div></div>
        <div class="sign-box"><div class="line">Office Signature</div></div>
    </div>

</div>
</div>

@if($invoice)
@php
    $normalizeFeeItem = function (array $item): array {
        $collected     = (float) ($item['amount'] ?? 0);   // cash actually received
        $fine          = (float) ($item['fine'] ?? 0);     // late fine charged
        $disc          = (float) ($item['discount'] ?? 0); // discount given
        $totalFee      = (float) ($item['total_fee'] ?? 0);
        $actualBalance = isset($item['actual_balance']) && $item['actual_balance'] >= 0
            ? (float) $item['actual_balance']
            : null;
        if ($totalFee <= 0) $totalFee = $collected;

        $paid    = $collected;
        // Use actual_balance from buildPendingRows if available (accounts for all prior payments)
        $balance = $actualBalance ?? max(0, $totalFee + $fine - $disc - $collected);

        return [
            'total_fee' => $totalFee,
            'fine'      => $fine,
            'discount'  => $disc,
            'paid'      => $paid,
            'balance'   => $balance,
        ];
    };

    $grandTotalFee = collect($feeItems)->sum(fn($item) => $normalizeFeeItem($item)['total_fee']);
    $grandFine     = collect($feeItems)->sum(fn($item) => $normalizeFeeItem($item)['fine']);
    $grandDisc     = collect($feeItems)->sum(fn($item) => $normalizeFeeItem($item)['discount']);
    $grandPaid     = collect($feeItems)->sum(fn($item) => $normalizeFeeItem($item)['paid']);
    $grandBalance  = (float) $remainingDue > 0
        ? (float) $remainingDue
        : max(0, $grandTotalFee + $grandFine - $grandDisc - $grandPaid);
@endphp
{{-- ═══════════════════════════════════════ --}}
{{-- FEE RECEIPT A4 — 2 column               --}}
{{-- ═══════════════════════════════════════ --}}
<div id="rcptView" style="display:none; margin-top:20px;">
@php
    $rcptMajor   = ($subjects->get('major') ?? collect())->merge($subjects->get('both') ?? collect());
    $rcptMinor   = ($subjects->get('minor') ?? collect())->merge($subjects->get('optional') ?? collect());
    $rcptComp    = $subjects->get('compulsory') ?? collect();
    $rcptAdmType = match($student->admission_type ?? '') {
        'fresh'        => 'Fresh Admission',
        'lateral'      => 'Lateral Entry',
        'transfer'     => 'Transfer',
        're-admission' => 'Re-Admission',
        default        => ucfirst($student->admission_type ?? 'New'),
    };
    $rcptSource = ucfirst(str_replace('_', ' ', $student->admission_source ?? 'direct'));
    if ($admissionSourceName) $rcptSource .= ' — ' . $admissionSourceName;
    $collBy  = $invoice->collected_by ?? 'Admin';
    $payMode = $invoice->payment_mode ?? '';
@endphp
<div class="a4-sheet">
    @foreach([ ['Student Copy','copy-student'], ['Office Copy','copy-institute'] ] as [$copyName,$copyClass])
    <div class="receipt">
        <div class="watermark">{{ $institute->short_name ?? 'PAID' }}</div>

        <span class="copy-badge {{ $copyClass }}">{{ $copyName }}</span>

        <div class="r-header">
            @if($institute?->image)
            <div style="text-align:center;margin-bottom:4px;">
                <img src="{{ Storage::url($institute->image) }}" style="height:30px;object-fit:contain;" alt="">
            </div>
            @endif
            <div class="inst">{{ $institute->name ?? 'Institute' }}</div>
            <div class="title">Fee Receipt</div>
            @if($student->session?->name)
            <span class="session-tag">Session: {{ $student->session->name }}</span>
            @endif
        </div>

        <div class="r-meta-bar">
            <div>
                <div style="font-size:7.5px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Invoice No</div>
                <div class="inv-no">{{ $invoice->invoice_no }}</div>
            </div>
            <div class="inv-date">
                <b>{{ \Carbon\Carbon::parse($invoice->payment_date)->format('d M Y') }}</b><br>
                <span style="font-size:8px;">{{ now()->format('h:i A') }}</span>
            </div>
        </div>

        <div class="r-info-grid">
            <div>
                <div class="r-row"><span class="lbl">Name:</span><span class="val">{{ $student->name }}</span></div>
                <div class="r-row"><span class="lbl">Father:</span><span class="val">{{ $student->father_name ?? '—' }}</span></div>
                @if($student->mother_name)
                <div class="r-row"><span class="lbl">Mother:</span><span class="val">{{ $student->mother_name }}</span></div>
                @endif
                <div class="r-row"><span class="lbl">Mobile:</span><span class="val">{{ $student->mobile }}</span></div>
            </div>
            <div>
                <div class="r-row"><span class="lbl">App No:</span><span class="val">{{ $student->student_uid }}</span></div>
                @if($student->institute_form_no)
                <div class="r-row"><span class="lbl">Form No:</span><span class="val">{{ $student->institute_form_no }}</span></div>
                @endif
                @if($student->enrollment_no)
                <div class="r-row"><span class="lbl">Enroll No:</span><span class="val">{{ $student->enrollment_no }}</span></div>
                @endif
                @if($student->roll_no)
                <div class="r-row"><span class="lbl">Roll No:</span><span class="val">{{ $student->roll_no }}</span></div>
                @endif
            </div>
        </div>

        <hr class="r-divider">

        <div class="r-row-full"><span class="lbl">Course:</span><span class="val">{{ $student->stream->course->name ?? '—' }}</span></div>
        <div class="r-row-full">
            <span class="lbl">Year / Sem:</span>
            <span class="val">{{ $student->coursePart?->year_label ?? '1st Year' }}@if($student->current_semester) / Sem {{ $student->current_semester }}@endif</span>
        </div>
        @if($rcptMajor->isNotEmpty())
        <div class="r-row-full"><span class="lbl">Major:</span><span class="val" style="font-size:8px;">{{ $rcptMajor->pluck('subject.name')->filter()->implode(', ') }}</span></div>
        @endif
        @if($rcptMinor->isNotEmpty())
        <div class="r-row-full"><span class="lbl">Minor:</span><span class="val" style="font-size:8px;">{{ $rcptMinor->pluck('subject.name')->filter()->implode(', ') }}</span></div>
        @endif
        @if($rcptComp->isNotEmpty())
        <div class="r-row-full"><span class="lbl">Compulsory:</span><span class="val" style="font-size:8px;">{{ $rcptComp->pluck('subject.name')->filter()->implode(', ') }}</span></div>
        @endif

        <hr class="r-divider">
        <div class="r-info-grid">
            <div>
                @if($student->admission_date)
                <div class="r-row"><span class="lbl">Adm. Date:</span><span class="val">{{ \Carbon\Carbon::parse($student->admission_date)->format('d/m/Y') }}</span></div>
                @endif
                <div class="r-row"><span class="lbl">Adm. Type:</span><span class="val">{{ $rcptAdmType }}</span></div>
            </div>
            <div>
                <div class="r-row"><span class="lbl">Source:</span><span class="val">{{ $rcptSource }}</span></div>
                <div class="r-row"><span class="lbl">Fee At:</span><span class="val">{{ $feeCenterLabel ?? 'Institute' }}</span></div>
            </div>
        </div>

        <div class="pay-box">
            <div class="pay-box-head">Payment Summary</div>
            <div class="pay-box-body">
                <div class="pay-row">
                    <span class="pay-lbl">Payment Mode</span>
                    <span class="mode-chip">{{ strtoupper($payMode) }}</span>
                </div>
                <hr class="pay-divider">
                <div class="pay-row">
                    <span class="pay-lbl">Amount Paid</span>
                    <span class="pay-val amount-big">&#8377; {{ number_format($grandPaid, 2) }}</span>
                </div>
                @if($grandFine > 0)
                <div class="pay-row">
                    <span class="pay-lbl">Fine Charged</span>
                    <span class="pay-val" style="color:#dc2626;">+ &#8377; {{ number_format($grandFine, 2) }}</span>
                </div>
                @endif
                @if($grandDisc > 0)
                <div class="pay-row">
                    <span class="pay-lbl">Discount</span>
                    <span class="pay-val" style="color:#d97706;">- &#8377; {{ number_format($grandDisc, 2) }}</span>
                </div>
                @endif
                <hr class="pay-divider">
                <div class="pay-row">
                    <span class="pay-lbl">Remaining Due</span>
                    <span class="pay-val amount-due {{ $grandBalance > 0 ? 'due-pos' : 'due-zero' }}">&#8377; {{ number_format($grandBalance, 2) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->transaction_ref)
        <div class="r-row-full" style="margin-top:3px;"><span class="lbl">Ref No:</span><span class="val">{{ $invoice->transaction_ref }}</span></div>
        @endif
        @if($invoice->remarks)
        <div class="r-row-full"><span class="lbl">Note:</span><span class="val">{{ $invoice->remarks }}</span></div>
        @endif
        <div class="r-row-full" style="margin-top:2px;"><span class="lbl">Collected By:</span><span class="val">{{ $collBy }}</span></div>

        <div class="r-note">Fees once paid are non-refundable.</div>

        <div class="r-footer">
            <div class="r-sign">Student Sign</div>
            <div class="r-sign">Authorized Sign</div>
        </div>
    </div>
    @endforeach
</div>
</div>
<div id="thermalView" style="display:none; margin-top:20px;">
<div class="thermal-sheet" style="font-size:10px;line-height:1.3;padding:1.5mm;font-family:Verdana,sans-serif;">
    @php $fr = 'display:flex;justify-content:space-between;margin-bottom:2px;font-size:10px;font-weight:700;color:#000;'; @endphp

    {{-- 1. Institute header --}}
    <div style="text-align:center;font-size:15px;font-weight:700;border-bottom:1.5px solid #000;padding-bottom:3px;margin-bottom:3px;">{{ $institute->name ?? '' }}</div>
    @if($institute?->address)
    <div style="text-align:center;font-size:9px;">{{ $institute->address }}@if($institute?->city), {{ $institute->city }}@endif</div>
    @endif
    @if($institute?->mobile)
    <div style="text-align:center;font-size:9px;margin-bottom:2px;">Ph: {{ $institute->mobile }}</div>
    @endif

    {{-- 2. Title --}}
    <div style="text-align:center;font-size:11px;font-weight:700;border:1px solid #000;padding:2px;margin:3px 0;">Fee Receipt ({{ $student->session->name ?? '' }})</div>

    {{-- 3. Payment / receipt details --}}
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Date:</span><span>{{ $invoice->payment_date?->format('d/m/Y') }} {{ now()->format('h:i A') }}</span></div>
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Receipt No:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $invoice->invoice_no }}</span></div>
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Mode:</span><span>{{ strtoupper($invoice->payment_mode ?? '') }}</span></div>
    @if($invoice->transaction_ref)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Ref:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $invoice->transaction_ref }}</span></div>
    @endif

    {{-- 4. Personal info --}}
    <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Name:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->name) }}</span></div>
    @if($student->father_name)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Father:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->father_name) }}</span></div>
    @endif
    @if($student->mother_name)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Mother:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->mother_name) }}</span></div>
    @endif
    @if($student->mobile)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Mobile:</span><span>{{ $student->mobile }}</span></div>
    @endif

    {{-- 5. Office details — Form No first --}}
    <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
    @if($student->institute_form_no)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Form No:</span><span style="text-align:right;max-width:42mm;word-break:break-word;">{{ $student->institute_form_no }}</span></div>
    @endif
    <div style="{{ $fr }}"><span style="white-space:nowrap;">App No:</span><span>{{ $student->student_uid }}</span></div>
    @if($student->enrollment_no)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Enroll No:</span><span>{{ $student->enrollment_no }}</span></div>
    @endif
    @if($student->roll_no)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Roll No:</span><span>{{ $student->roll_no }}</span></div>
    @endif
    @if($student->uin_no)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">UIN:</span><span>{{ $student->uin_no }}</span></div>
    @endif

    {{-- 6. Course details --}}
    <div style="text-align:center;font-size:10px;font-weight:700;border-top:0.8px dashed #555;padding-top:3px;margin-top:3px;margin-bottom:2px;">{{ $student->stream->course->name ?? '' }} | {{ $student->coursePart?->year_label ?? '' }}@if($student->current_semester) / Sem {{ $student->current_semester }}@endif</div>
    @if($rcptMajor->isNotEmpty())
    <div style="font-size:9px;font-weight:600;margin-bottom:1px;"><span style="opacity:.7;">Major:</span> {{ $rcptMajor->pluck('subject.name')->filter()->implode(', ') }}</div>
    @endif
    @if($rcptMinor->isNotEmpty())
    <div style="font-size:9px;font-weight:600;margin-bottom:1px;"><span style="opacity:.7;">Minor:</span> {{ $rcptMinor->pluck('subject.name')->filter()->implode(', ') }}</div>
    @endif
    @if($rcptComp->isNotEmpty())
    <div style="font-size:9px;font-weight:600;margin-bottom:2px;"><span style="opacity:.7;">Comp:</span> {{ $rcptComp->pluck('subject.name')->filter()->implode(', ') }}</div>
    @endif
    @if($student->admission_date)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Adm Date:</span><span>{{ $student->admission_date?->format('d/m/Y') }}</span></div>
    @endif
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Adm Type:</span><span>{{ $rcptAdmType ?? ucfirst($student->admission_type ?? '') }}</span></div>
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Source:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $rcptSource ?? ucfirst($student->admission_source ?? 'direct') }}</span></div>
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Fee At:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $feeCenterLabel ?? 'Institute' }}</span></div>

    {{-- 7. Amount --}}
    <div style="display:flex;justify-content:space-between;border-top:1.5px solid #000;margin-top:4px;padding-top:3px;font-size:13px;font-weight:700;">
        <span style="white-space:nowrap;">Amt Paid:</span><span>{{ number_format($grandPaid, 0) }}</span>
    </div>
    @if($grandFine > 0)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Fine:</span><span>+{{ number_format($grandFine, 0) }}</span></div>
    @endif
    @if($grandDisc > 0)
    <div style="{{ $fr }}"><span style="white-space:nowrap;">Discount:</span><span>-{{ number_format($grandDisc, 0) }}</span></div>
    @endif
    <div style="display:flex;justify-content:space-between;font-size:10px;font-weight:700;border-bottom:1.5px solid #000;padding:2px 0;">
        <span style="white-space:nowrap;">Balance Due:</span><span>{{ number_format($grandBalance, 0) }}</span>
    </div>

    <div style="{{ $fr }}"><span style="white-space:nowrap;">Cashier:</span><span>{{ $invoice->collected_by ?? 'Admin' }}</span></div>
    <div style="text-align:center;font-size:9px;padding:1px 0;">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
    @if($invoice->remarks)
    <div style="text-align:center;font-size:9px;padding:3px 0 1px;">Note: {{ $invoice->remarks }}</div>
    @else
    <div style="text-align:center;font-size:9px;padding:3px 0 1px;">नोट - कृपया रसीद को सुरक्षित रखें।</div>
    @endif
    <div style="text-align:center;font-size:9px;border-top:1.5px solid #000;margin-top:3px;padding-top:2px;">Fees once paid are non-refundable.</div>
    <div style="display:flex;justify-content:space-between;margin-top:2mm;font-size:10px;font-weight:700;border-top:1px solid #000;padding-top:2px;">
        <span>Student Sign</span><span>Auth. Sign</span>
    </div>
</div>
</div>
@endif

{{-- ═══════════════════════════════════════ --}}
{{-- ADMISSION SLIP — THERMAL               --}}
{{-- ═══════════════════════════════════════ --}}
<div id="slipView" style="display:none; margin-top:20px;">
<div class="slip-sheet">
    <div class="app-header" style="margin-bottom:12px;">
        <div class="logo-box">
            @if($institute?->image)
                <img src="{{ Storage::url($institute->image) }}" alt="">
            @else
                <span style="font-size:8px;color:#999;">Logo</span>
            @endif
        </div>
        <div class="inst-info">
            <div class="inst-name">{{ $institute->name ?? '' }}</div>
            <div class="inst-sub">{{ $institute->address ?? '' }}@if($institute?->city), {{ $institute->city }}@endif</div>
            @if($institute?->mobile)<div class="inst-sub">Phone: {{ $institute->mobile }}@if($institute?->email) | Web: {{ $institute->email }}@endif</div>@endif
        </div>
        <div class="photo-box">
            @if($student->photo)
                <img src="{{ Storage::url($student->photo) }}" alt="">
            @else
                <div class="photo-placeholder"><div style="font-size:18px;color:#ccc;">PHOTO</div><div>Photo</div></div>
            @endif
        </div>
    </div>

    <div class="slip-banner">
        <div>
            <h2>Admission Confirmed</h2>
            <p>Student registration and fee collection have been successfully completed.</p>
        </div>
        <div class="slip-status">CONFIRMED</div>
    </div>

    <div class="slip-grid">
        <div class="slip-card">
            <div class="slip-card-title">Student Details</div>
            <div class="slip-meta">
                <div class="slip-meta-item"><span class="label">Student ID</span><span class="value">{{ $student->student_uid }}</span></div>
                <div class="slip-meta-item"><span class="label">Admission Date</span><span class="value">{{ $student->admission_date?->format('d-M-Y') }}</span></div>
                <div class="slip-meta-item"><span class="label">Student Name</span><span class="value">{{ $student->name }}</span></div>
                <div class="slip-meta-item"><span class="label">Mobile</span><span class="value">{{ $student->mobile ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Father Name</span><span class="value">{{ $student->father_name ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Session</span><span class="value">{{ $student->session->name ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Course</span><span class="value">{{ $student->stream->course->name ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Year / Semester</span><span class="value">{{ $student->coursePart?->year_label ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Stream</span><span class="value">{{ $student->stream->name ?? '-' }}</span></div>
                <div class="slip-meta-item"><span class="label">Admission Source</span><span class="value">{{ ucfirst(str_replace('_', ' ', $student->admission_source ?? 'direct')) }}{{ $admissionSourceName ? ' — ' . $admissionSourceName : '' }}</span></div>
            </div>
        </div>
        <div class="slip-card">
            <div class="slip-card-title">Confirmation Note</div>
            <div class="slip-note">
                This slip confirms that the student's admission has been saved in the institute records.
                The initial payment details are printed in the receipt section below.
            </div>
            @if($invoice)
            <table class="ftbl" style="margin-top:8px;">
                <tr>
                    <td class="lc">Invoice No</td><td class="vc">{{ $invoice->invoice_no }}</td>
                    <td class="lc">Payment Date & Time</td><td class="vc">{{ $invoice->payment_date?->format('d-m-Y') }} {{ now()->format('h:i A') }}</td>
                </tr>
                <tr>
                    <td class="lc">Payment Mode</td><td class="vc">{{ strtoupper($invoice->payment_mode ?? '') }}</td>
                    <td class="lc">Collected By</td><td class="vc">{{ $invoice->collected_by ?? 'Admin' }}</td>
                </tr>
                @if($invoice->transaction_ref)
                <tr>
                    <td class="lc">Transaction Ref</td><td class="vc" colspan="3">{{ $invoice->transaction_ref }}</td>
                </tr>
                @endif
            </table>
            @endif
        </div>
    </div>

    @if($invoice)
    <div class="slip-card">
        <div class="slip-receipt-head">
            <div>
                <div class="slip-card-title" style="margin-bottom:2px;">Fee Receipt Summary</div>
                <div class="inst-sub">Admission slip ke sath first receipt details</div>
            </div>
            <div class="slip-total-box">
                <span class="label">Paid Amount</span>
                <span class="value">Rs {{ number_format($grandPaid, 2) }}</span>
            </div>
        </div>

        <table class="rfee-tbl">
            <tbody>
                {{-- <tr class="total-row">
                    <td><b>Total Fee</b></td>
                    <td class="amt"><b>₹{{ number_format($grandTotalFee, 2) }}</b></td>
                </tr> --}}
                <tr class="total-row">
                    <td><b>Total Paid</b></td>
                    <td class="amt"><b>₹{{ number_format($grandPaid, 2) }}</b></td>
                </tr>
                @if($grandFine > 0)
                <tr>
                    <td style="font-size:10px;">Total Fine</td>
                    <td class="amt" style="font-size:10px;">+₹{{ number_format($grandFine, 2) }}</td>
                </tr>
                @endif
                @if($grandDisc > 0)
                <tr>
                    <td style="font-size:10px;color:#dc2626;">Total Discount</td>
                    <td class="amt" style="font-size:10px;color:#dc2626;">-₹{{ number_format($grandDisc, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><b>Remaining Due</b></td>
                    <td class="amt"><b>₹{{ number_format($grandBalance, 2) }}</b></td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="slip-sign-row">
        <div class="slip-sign">Student Signature</div>
        <div class="slip-sign">Authorized Signature</div>
    </div>
</div>
</div>

<div id="slipThermalView" style="display:none; margin-top:20px;">
<div class="thermal-sheet" style="font-size:10px;line-height:1.25;padding:1.5mm;">
    @php $sr = 'display:flex;justify-content:space-between;margin-bottom:1px;font-size:10px;font-weight:600;'; @endphp

    {{-- Header --}}
    <div style="text-align:center;font-size:13px;font-weight:700;border-bottom:1.5px solid #000;padding-bottom:3px;margin-bottom:3px;">
        {{ $institute->name ?? '' }}
    </div>
    @if($institute?->address)
    <div style="text-align:center;font-size:9px;">{{ $institute->address }}@if($institute?->city), {{ $institute->city }}@endif</div>
    @endif
    @if($institute?->mobile)
    <div style="text-align:center;font-size:9px;">Ph: {{ $institute->mobile }}</div>
    @endif

    {{-- Title --}}
    <div style="text-align:center;font-size:11px;font-weight:700;border:1px solid #000;margin:3px 0;padding:2px;">ADMISSION SLIP</div>

    {{-- Student rows --}}
    <div style="{{ $sr }}"><span>Student ID:</span><span>{{ $student->student_uid }}</span></div>
    <div style="{{ $sr }}"><span>Name:</span><span style="text-align:right;max-width:40mm;word-break:break-word;">{{ $student->name }}</span></div>
    @if($student->father_name)
    <div style="{{ $sr }}"><span>Father:</span><span style="text-align:right;max-width:40mm;word-break:break-word;">{{ $student->father_name }}</span></div>
    @endif
    @if($student->mobile)
    <div style="{{ $sr }}"><span>Mobile:</span><span>{{ $student->mobile }}</span></div>
    @endif
    <div style="{{ $sr }}"><span>Course:</span><span style="text-align:right;max-width:40mm;word-break:break-word;">{{ $student->stream->course->name ?? '' }}</span></div>
    <div style="{{ $sr }}"><span>Year/Sem:</span><span>{{ $student->coursePart?->year_label ?? '' }}</span></div>
    <div style="{{ $sr }}"><span>Session:</span><span>{{ $student->session->name ?? '' }}</span></div>
    @if($student->admission_date)
    <div style="{{ $sr }}"><span>Adm. Date:</span><span>{{ $student->admission_date?->format('d-M-Y') }}</span></div>
    @endif

    @if($invoice)
    {{-- Fee section --}}
    <div style="border-top:1px dashed #333;border-bottom:1px dashed #333;text-align:center;font-size:10px;font-weight:700;margin:3px 0;padding:1px 0;">FEE RECEIPT</div>
    <div style="{{ $sr }}"><span>Receipt No:</span><span>{{ $invoice->invoice_no }}</span></div>
    <div style="{{ $sr }}"><span>Date:</span><span>{{ $invoice->payment_date?->format('d/m/Y') }} {{ now()->format('h:i A') }}</span></div>
    <div style="{{ $sr }}"><span>Pay Mode:</span><span>{{ strtoupper($invoice->payment_mode ?? '') }}</span></div>
    @if($invoice->transaction_ref)
    <div style="{{ $sr }}"><span>Ref:</span><span>{{ $invoice->transaction_ref }}</span></div>
    @endif

    {{-- Totals --}}
    <div style="display:flex;justify-content:space-between;border-top:1.5px solid #000;margin-top:3px;padding-top:2px;font-size:12px;font-weight:700;">
        <span>Total Paid</span><span>₹{{ number_format($grandPaid, 2) }}</span>
    </div>
    @if($grandFine > 0)
    <div style="{{ $sr }}"><span>Total Fine</span><span>+₹{{ number_format($grandFine, 2) }}</span></div>
    @endif
    @if($grandDisc > 0)
    <div style="{{ $sr }}"><span>Total Discount</span><span>-₹{{ number_format($grandDisc, 2) }}</span></div>
    @endif
    <div style="{{ $sr }}"><span>Remaining Due</span><span>₹{{ number_format($grandBalance, 2) }}</span></div>
    @endif

    <div style="border-top:1.5px solid #000;margin-top:3px;text-align:center;font-size:9px;padding-top:2px;">Keep this slip for record. &nbsp;<b>Thank You!</b></div>
</div>
</div>

</div>{{-- preview-pane --}}
</div>{{-- workspace --}}
</div>{{-- page-shell --}}
<script>
const queryParams = new URLSearchParams(window.location.search);

function showTab(tab) {
    const viewIds = {app:'appView', rcpt:'rcptView', thermal:'thermalView', slip:'slipView', slipThermal:'slipThermalView'};
    const sbIds   = {app:'sbApp',  rcpt:'sbRcpt',   thermal:'sbThermal',   slip:'sbSlip',  slipThermal:'sbSlipThermal'};
    ['app','rcpt','thermal','slip','slipThermal'].forEach(t => {
        const el = document.getElementById(viewIds[t]);
        const sb = document.getElementById(sbIds[t]);
        if (el) el.style.display = (t === tab) ? 'block' : 'none';
        if (sb) sb.classList.toggle('active', t === tab);
    });
    const pane = document.getElementById('previewPane');
    if (pane) pane.scrollTop = 0;
}
function setViews(app, rcpt, thermal) {
    document.getElementById('appView').style.display   = app;
    document.getElementById('slipView').style.display  = 'none';
    const stv = document.getElementById('slipThermalView');
    if (stv) stv.style.display = 'none';
    const rv = document.getElementById('rcptView');
    const tv = document.getElementById('thermalView');
    if (rv) rv.style.display = rcpt;
    if (tv) tv.style.display = thermal;
}
function injectPageStyle(css) {
    let s = document.getElementById('_ps');
    if (!s) { s = document.createElement('style'); s.id = '_ps'; document.head.appendChild(s); }
    s.textContent = css;
}
function thermalPageCss(viewId) {
    const view = document.getElementById(viewId);
    const sheet = view?.querySelector('.thermal-sheet');
    const heightMm = sheet
        ? Math.max(70, Math.ceil(sheet.scrollHeight * 25.4 / 96) + 10)
        : 140;

    return `@page { size: 80mm ${heightMm}mm; margin: 0mm; }
@media print { html, body { width:80mm; height:${heightMm}mm; margin:0 !important; padding:0 !important; overflow:hidden !important; } }`;
}
function printWithoutBrowserTitle(callback, thermal = false) {
    const oldTitle = document.title;
    document.title = '';
    if (thermal) document.body.classList.add('thermal-print-active');
    const restore = () => {
        document.title = oldTitle;
        document.body.classList.remove('thermal-print-active');
        window.removeEventListener('afterprint', restore);
    };
    window.addEventListener('afterprint', restore);
    callback();
}
function printApp() {
    injectPageStyle('@page { size: A4 portrait; margin: 10mm; }');
    setViews('block','none','none');
    setTimeout(() => window.print(), 150);
}
function printA4() {
    injectPageStyle('@page { size: A4 portrait; margin: 5mm; }');
    setViews('none','block','none');
    setTimeout(() => window.print(), 150);
}
function printThermal() {
    setViews('none','none','block');
    setTimeout(() => {
        injectPageStyle(thermalPageCss('thermalView'));
        printWithoutBrowserTitle(() => window.print(), true);
    }, 150);
}
function printBoth() {
    injectPageStyle('@page { size: A4 portrait; margin: 10mm; }');
    setViews('block','block','none');
    setTimeout(() => window.print(), 150);
}
function printSlip() {
    injectPageStyle('@page { size: A4 portrait; margin: 10mm; }');
    document.getElementById('appView').style.display     = 'none';
    const rv = document.getElementById('rcptView');   if (rv) rv.style.display = 'none';
    const tv = document.getElementById('thermalView'); if (tv) tv.style.display = 'none';
    const stv = document.getElementById('slipThermalView'); if (stv) stv.style.display = 'none';
    document.getElementById('slipView').style.display   = 'block';
    setTimeout(() => window.print(), 150);
}
function printSlipThermal() {
    document.getElementById('appView').style.display = 'none';
    const rv = document.getElementById('rcptView'); if (rv) rv.style.display = 'none';
    const tv = document.getElementById('thermalView'); if (tv) tv.style.display = 'none';
    document.getElementById('slipView').style.display = 'none';
    document.getElementById('slipThermalView').style.display = 'block';
    setTimeout(() => {
        injectPageStyle(thermalPageCss('slipThermalView'));
        printWithoutBrowserTitle(() => window.print(), true);
    }, 150);
}

document.addEventListener('DOMContentLoaded', () => {
    const requestedView = queryParams.get('view');
    const shouldAutoPrint = queryParams.get('autoprint') === '1';

    if (!requestedView) {
        return;
    }

    if (requestedView === 'thermal') {
        if (shouldAutoPrint) {
            printThermal();
            return;
        }

        showTab('thermal');
        return;
    }

    if (requestedView === 'slipThermal') {
        if (shouldAutoPrint) {
            printSlipThermal();
            return;
        }

        showTab('slipThermal');
        return;
    }

    if (requestedView === 'slip') {
        if (shouldAutoPrint) {
            printSlip();
            return;
        }

        showTab('slip');
        return;
    }

    if (requestedView === 'rcpt') {
        if (shouldAutoPrint) {
            printA4();
            return;
        }

        showTab('rcpt');
        return;
    }

    if (shouldAutoPrint) {
        printApp();
        return;
    }

    showTab('app');
});
</script>
</body>
</html>
