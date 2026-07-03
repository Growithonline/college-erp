<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Receipt — {{ $student->student_uid }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:Arial,sans-serif; background:#f1f5f9; font-size:11px; }

/* ── Page Shell ── */
.page-shell  { display:flex; flex-direction:column; height:100dvh; }
.workspace   { display:flex; flex:1; min-height:0; overflow:hidden; }
.preview-pane { flex:1; overflow-y:auto; padding:20px; min-width:0; background:#f1f5f9; }

/* ── Top Bar ── */
.top-bar { background:#1e293b; flex-shrink:0; }
.top-bar-inner { display:flex; align-items:center; gap:6px; padding:7px 14px; flex-wrap:wrap; }
.student-tag { color:#94a3b8; font-size:11px; font-weight:600; white-space:nowrap; }
.student-tag span { color:#e2e8f0; }
.top-links { display:flex; flex-wrap:wrap; gap:5px; margin-left:auto; }
.tl-btn { padding:5px 12px; border-radius:5px; font-size:11px; font-weight:600;
          text-decoration:none; border:none; cursor:pointer; display:inline-block;
          white-space:nowrap; line-height:1.4; transition:opacity .15s; }
.tl-btn:hover { opacity:.85; }
.tl-blue   { background:#1d4ed8; color:#fff; }
.tl-green  { background:#16a34a; color:#fff; }
.tl-violet { background:#7c3aed; color:#fff; }
.tl-slate  { background:#475569; color:#fff; }
.tl-ghost  { background:transparent; color:#94a3b8; border:1px solid #475569; }

/* ── Left Sidebar ── */
.sidebar { width:186px; flex-shrink:0; background:#0f172a; overflow-y:auto;
           padding:10px 7px; display:flex; flex-direction:column; }
.sb-section { margin-bottom:14px; }
.sb-label { font-size:8px; font-weight:700; color:#475569; text-transform:uppercase;
            letter-spacing:.8px; padding:0 4px 5px; border-bottom:1px solid #1e293b;
            margin-bottom:5px; }
.sb-btn { display:flex; align-items:center; gap:7px; width:100%; text-align:left;
          padding:8px 10px; border:none; border-radius:5px; font-size:11px; font-weight:600;
          cursor:pointer; margin-bottom:2px; transition:background .12s, color .12s;
          text-decoration:none; }
.sb-view { background:#1e293b; color:#94a3b8; }
.sb-view:hover, .sb-view.active { background:#1d4ed8; color:#fff; }
.sb-action { background:#134e4a; color:#6ee7b7; }
.sb-action:hover { background:#0f766e; color:#fff; }
.sb-nav { background:#1e293b; color:#94a3b8; }
.sb-nav:hover { background:#334155; color:#e2e8f0; }
.sb-danger { background:#1e293b; color:#f87171; }
.sb-danger:hover { background:#7f1d1d; color:#fca5a5; }

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

/* ══════════════════════════════════════
   A4 Receipt
══════════════════════════════════════ */
.a4-sheet {
    width: 210mm;
    min-height: 148mm;
    background: white;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    overflow: hidden;
}
.receipt {
    padding: 7mm 6mm 6mm;
    border-right: 2px dashed #94a3b8;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.receipt:last-child { border-right: none; }

/* Copy badge */
.copy-badge {
    display:inline-block; font-size:7.5px; font-weight:700;
    padding:2px 7px; border-radius:20px; text-transform:uppercase;
    letter-spacing:.6px; margin-bottom:5px; align-self:flex-end;
}
.copy-student   { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.copy-institute { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }

/* Header */
.r-header {
    text-align:center; padding-bottom:5px; margin-bottom:6px;
    border-bottom:2.5px solid #0f766e; position:relative;
}
.r-header::before {
    content:''; display:block; height:3px;
    background:linear-gradient(90deg,#0f766e,#0891b2);
    border-radius:2px; margin-bottom:5px;
}
.r-header .inst  { font-size:13.5px; font-weight:800; color:#0f172a; letter-spacing:.2px; }
.r-header .title { font-size:8.5px; color:#64748b; text-transform:uppercase;
                   letter-spacing:1.5px; margin-top:2px; }
.r-header .session-tag { font-size:8px; color:#0f766e; font-weight:600;
                          background:#f0fdfa; border:1px solid #99f6e4;
                          border-radius:10px; padding:1px 7px; display:inline-block; margin-top:3px; }

/* Invoice meta bar */
.r-meta-bar {
    display:flex; justify-content:space-between; align-items:center;
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:5px;
    padding:4px 7px; margin-bottom:6px; font-size:8.5px;
}
.r-meta-bar .inv-no  { font-weight:700; color:#0f172a; font-size:9px; }
.r-meta-bar .inv-date { color:#475569; text-align:right; line-height:1.5; }
.r-meta-bar .inv-date b { color:#0f172a; }

/* Info grids */
.r-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 6px; margin-bottom:6px; }
.r-row { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row .val { font-weight:600; color:#1e293b; flex:1; }
.r-row-full { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row-full .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row-full .val { font-weight:600; color:#1e293b; }

/* Section divider */
.r-divider { border:none; border-top:1px dashed #e2e8f0; margin:5px 0; }

/* Payment summary box */
.pay-box {
    background:linear-gradient(135deg,#f0fdf4,#f0fdfa);
    border:1.5px solid #6ee7b7; border-radius:6px; overflow:hidden; margin:5px 0;
}
.pay-box-head {
    background:linear-gradient(90deg,#0f766e,#0891b2);
    color:white; font-size:8.5px; font-weight:700; padding:3px 8px; letter-spacing:.3px;
}
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
.mode-chip {
    display:inline-block; font-size:7.5px; font-weight:700;
    padding:2px 8px; border-radius:20px; text-transform:uppercase; letter-spacing:.5px;
    background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;
}

/* Footer */
.r-note { font-size:7.5px; color:#94a3b8; font-style:italic; margin-top:3px; }
.r-footer { display:flex; justify-content:space-between; margin-top:auto;
            padding-top:5px; border-top:1px dashed #e2e8f0; }
.r-sign { border-top:1px solid #94a3b8; width:65px; text-align:center;
          font-size:7.5px; padding-top:3px; color:#64748b; }

/* Watermarks */
.watermark { position:absolute; top:50%; left:50%;
             transform:translate(-50%,-50%) rotate(-25deg);
             font-size:48px; font-weight:900;
             color:rgba(15,118,110,0.04); pointer-events:none; white-space:nowrap; }
.watermark-cancelled { position:absolute; top:50%; left:50%;
             transform:translate(-50%,-50%) rotate(-25deg);
             font-size:52px; font-weight:900;
             color:rgba(220,38,38,0.1); pointer-events:none; white-space:nowrap; }
.cancelled-bar { background:#fef2f2; border:1.5px solid #fca5a5;
                 border-radius:4px; padding:4px 7px; margin:5px 0;
                 font-size:8px; color:#dc2626; }
.cancelled-bar b { font-weight:700; }

/* ══════════════════════════════════════
   Thermal Layout — 80mm roll
══════════════════════════════════════ */
.thermal-sheet {
    width:76mm; max-width:76mm; background:white; margin:0 auto;
    padding:1.5mm; border:1px solid #ccc; font-family:Verdana,sans-serif;
    font-size:10px; font-weight:600; line-height:1.3; color:#000;
}

/* ══════════════════════════════════════
   Print Media
══════════════════════════════════════ */
@media print {
    body { background:white; }
    .page-shell  { height:auto; display:block; }
    .workspace   { display:block; }
    .sidebar, .top-bar { display:none !important; }
    .preview-pane { padding:0; overflow:visible; }

    body.print-a4 #a4-view      { display:block !important; }
    body.print-a4 #thermal-view { display:none  !important; }
    body.print-a4 .a4-sheet     { width:210mm; min-height:148mm; height:auto; border:none; margin:0; }

    body.print-thermal #thermal-view { display:block !important; }
    body.print-thermal #a4-view      { display:none  !important; }
    body.print-thermal .thermal-sheet { border:none; width:76mm; max-width:76mm; margin:0; padding:2mm 2.6mm; }
    body.print-thermal { width:80mm; margin:0 !important; padding:0 !important; }

    body.print-pdf #a4-view      { display:block !important; }
    body.print-pdf #thermal-view { display:none  !important; }
}
</style>
</head>
<body id="printBody" class="print-a4">

@php
    $panel = auth()->guard('staff')->check()
        ? 'staff'
        : (auth()->guard('center')->check()
            ? 'center'
            : (auth()->guard('partner')->check() ? 'partner' : 'institute'));

    $dashboardUrl = match($panel) {
        'staff'   => route('staff.dashboard'),
        'center'  => route('center.dashboard'),
        'partner' => route('partner.dashboard'),
        default   => route('institute.dashboard'),
    };
    $feeHistoryUrl = $panel === 'staff'
        ? route('staff.fee.index')
        : ($panel === 'institute'
            ? route('fee.student-history', $student->id)
            : route(($panel === 'center' ? 'center.fee.create' : 'partner.fee.create'), ['student_id' => $student->id]));
    $studentProfileUrl = $panel === 'center'
        ? route('center.students.show', $student->id)
        : ($panel === 'partner'
            ? route('partner.students.show', $student->id)
            : ($panel === 'institute' ? route('admissions.show', $student->id) : route('staff.admissions.index')));
    $collectMoreUrl = match($panel) {
        'staff'   => route('staff.fee.create'),
        'center'  => route('center.fee.create'),
        'partner' => route('partner.fee.create'),
        default   => route('fee.create'),
    };
    $authUser = auth()->guard($panel === 'institute' ? 'web' : $panel)->user();
@endphp

<div class="page-shell">

    {{-- ── Top Bar ── --}}
    <div class="top-bar">
        <div class="top-bar-inner">
            <div class="student-tag">
                Fee Receipt &nbsp;/&nbsp; <span>{{ $student->name }}</span>
                &nbsp;<span style="color:#64748b;">{{ $student->student_uid }}</span>
            </div>
            <div class="top-links">
                <a href="{{ $collectMoreUrl }}" class="tl-btn tl-green">+ Collect More Fee</a>
                <a href="{{ $feeHistoryUrl }}" class="tl-btn tl-blue">← Fee History</a>
                <a href="{{ $studentProfileUrl }}" class="tl-btn tl-slate">Student Profile</a>
                <a href="{{ $dashboardUrl }}" class="tl-btn tl-ghost">Dashboard</a>
            </div>
        </div>
    </div>

    <div class="workspace">

        {{-- ── Left Sidebar ── --}}
        <div class="sidebar">

            <div class="sb-section">
                <div class="sb-label">Print Mode</div>
                <button class="sb-btn sb-view active" id="tabA4" onclick="setMode('a4')">
                    📄 A4 (2 per page)
                </button>
                <button class="sb-btn sb-view" id="tabThermal" onclick="setMode('thermal')">
                    🖨️ Thermal (80mm)
                </button>
            </div>

            <div class="sb-section">
                <div class="sb-label">Actions</div>
                <button class="sb-btn sb-action" onclick="printReceipt()">
                    🖨️ Print
                </button>
                <button class="sb-btn sb-action" onclick="savePDF()">
                    📥 Save as PDF
                </button>
            </div>

            <div class="sb-section">
                <div class="sb-label">Navigate</div>
                <a href="{{ $collectMoreUrl }}" class="sb-btn sb-nav">₹ Collect More Fee</a>
                <a href="{{ $feeHistoryUrl }}" class="sb-btn sb-nav">← Fee History</a>
                <a href="{{ $studentProfileUrl }}" class="sb-btn sb-nav">👤 Student Profile</a>
                <a href="{{ $dashboardUrl }}" class="sb-btn sb-nav">🏠 Dashboard</a>
            </div>

        </div>

        {{-- ── Preview Pane ── --}}
        <div class="preview-pane">

            @php
                $inst      = \App\Models\Institute::find($student->institute_id);
                $instituteAddress = trim(collect([
                    $inst->address ?? null,
                    $inst->city ?? null,
                    $inst->state ?? null,
                    $inst->pincode ?? null,
                ])->filter()->implode(', '));
                $rNo       = $receipt->invoice_no ?? ('RCPT/'.date('Y').'/'.str_pad($student->id,5,'0',STR_PAD_LEFT));
                $rDate     = $receipt->payment_date ?? now();
                $rAmount   = $receipt->paid_amount  ?? 0;
                $rTotal    = $receipt->total_amount ?? $rAmount;
                $payMode   = ucfirst($receipt->payment_mode ?? 'Cash');
                $collBy    = $receipt->collected_by ?? ($authUser->name ?? $student->name);
                $footNote  = 'Fees once paid are non-refundable.';
                $discount  = $receipt->discount ?? 0;

                $admTypeLabel = match($student->admission_type ?? '') {
                    'fresh'        => 'Fresh Admission',
                    'lateral'      => 'Lateral Entry',
                    'transfer'     => 'Transfer',
                    're-admission' => 'Re-Admission',
                    default        => ucfirst($student->admission_type ?? 'Fresh Admission'),
                };

                $subjectsByRole     = ($studentSubjects ?? collect())->groupBy(fn($ss) => $ss->subject_role ?? 'compulsory');
                $majorSubjects      = $subjectsByRole->get('major',      collect());
                $minorSubjects      = $subjectsByRole->get('minor',      collect());
                $compulsorySubjects = $subjectsByRole->get('compulsory', collect());

                $identity = $student->currentAcademicIdentity;
                $rollNo   = $identity?->roll_no ?? $student->roll_no ?? null;
                $enrollNo = $identity?->enrollment_no_snapshot ?? $student->enrollment_no ?? null;
                $uinNo    = $identity?->uin_no_snapshot ?? $student->uin_no ?? null;
                $formNo   = $identity?->form_no ?? $student->institute_form_no ?? null;
                $srNo     = $identity?->sr_no_snapshot ?? $student->sr_no ?? null;

                $slots = [
                    ['label' => 'Student Copy', 'class' => 'copy-student'],
                    ['label' => 'Office Copy',  'class' => 'copy-institute'],
                ];

                $normalizeFeeItem = function (array $item): array {
                    $collected     = (float) ($item['amount'] ?? 0);
                    $fine          = (float) ($item['fine'] ?? 0);
                    $disc          = (float) ($item['discount'] ?? 0);
                    $totalFee      = (float) ($item['total_fee'] ?? 0);
                    $actualBalance = isset($item['actual_balance']) && $item['actual_balance'] >= 0
                        ? (float) $item['actual_balance']
                        : null;
                    if ($totalFee <= 0) $totalFee = $collected;
                    $paid    = $collected;
                    $balance = $actualBalance ?? max(0, $totalFee + $fine - $disc - $collected);
                    return ['total_fee' => $totalFee, 'fine' => $fine, 'discount' => $disc, 'paid' => $paid, 'balance' => $balance];
                };

                $grandTotalFee = 0; $grandPaid = 0; $grandDisc = 0; $grandFine = 0;
                foreach ($feeItems ?? [] as $fi) {
                    $n = $normalizeFeeItem($fi);
                    $grandTotalFee += $n['total_fee'];
                    $grandPaid     += $n['paid'];
                    $grandDisc     += $n['discount'];
                    $grandFine     += $n['fine'];
                }
                $grandBalance = (float) $remainingDue > 0
                    ? (float) $remainingDue
                    : max(0, $grandTotalFee + $grandFine - $grandDisc - $grandPaid);
            @endphp

            {{-- ════ A4 VIEW ════ --}}
            <div id="a4-view">
                <div class="a4-sheet">
                @foreach($slots as $slot)
                <div class="receipt">
                    @if($receipt->is_cancelled)
                        <div class="watermark-cancelled">CANCELLED</div>
                    @else
                        <div class="watermark">{{ $inst->short_name ?? 'PAID' }}</div>
                    @endif

                    <span class="copy-badge {{ $slot['class'] }}">{{ $slot['label'] }}</span>

                    <div class="r-header">
                        <div class="inst">{{ $inst->name ?? 'Institute Name' }}</div>
                        <div class="title">Fee Receipt{{ $receipt->is_cancelled ? ' — CANCELLED' : '' }}</div>
                        @if($receipt->session?->name)
                        <span class="session-tag">Session: {{ $receipt->session->name }}</span>
                        @endif
                    </div>

                    <div class="r-meta-bar">
                        <div>
                            <div style="font-size:7.5px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Invoice No</div>
                            <div class="inv-no">{{ $rNo }}</div>
                        </div>
                        <div class="inv-date">
                            <b>{{ \Carbon\Carbon::parse($rDate)->format('d M Y') }}</b><br>
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
                            @if($receiptConfig['receipt_mobile']['enabled'] ?? true)
                            <div class="r-row"><span class="lbl">Mobile:</span><span class="val">{{ $student->mobile }}</span></div>
                            @endif
                        </div>
                        <div>
                            <div class="r-row"><span class="lbl">App No:</span><span class="val">{{ $student->student_uid }}</span></div>
                            @if($formNo)
                            <div class="r-row"><span class="lbl">Form No:</span><span class="val">{{ $formNo }}</span></div>
                            @endif
                            @if($enrollNo)
                            <div class="r-row"><span class="lbl">Enroll No:</span><span class="val">{{ $enrollNo }}</span></div>
                            @endif
                            @if($rollNo)
                            <div class="r-row"><span class="lbl">Roll No:</span><span class="val">{{ $rollNo }}</span></div>
                            @endif
                            @if($uinNo)
                            <div class="r-row"><span class="lbl">UIN:</span><span class="val">{{ $uinNo }}</span></div>
                            @endif
                        </div>
                    </div>

                    <hr class="r-divider">

                    @if($receiptConfig['receipt_course']['enabled'] ?? true)
                    <div class="r-row-full"><span class="lbl">Course:</span><span class="val">{{ $student->stream->course->name ?? '—' }}</span></div>
                    @endif
                    <div class="r-row-full">
                        <span class="lbl">Year / Sem:</span>
                        <span class="val">{{ $student->coursePart?->year_label ?? '1st Year' }}@if($student->current_semester) / Sem {{ $student->current_semester }}@endif</span>
                    </div>
                    @if($majorSubjects->count())
                    <div class="r-row-full"><span class="lbl">Major:</span><span class="val" style="font-size:8px;">{{ $majorSubjects->map(fn($ss)=>$ss->subject?->name)->filter()->implode(', ') }}</span></div>
                    @endif
                    @if($minorSubjects->count())
                    <div class="r-row-full"><span class="lbl">Minor:</span><span class="val" style="font-size:8px;">{{ $minorSubjects->map(fn($ss)=>$ss->subject?->name)->filter()->implode(', ') }}</span></div>
                    @endif
                    @if($compulsorySubjects->count())
                    <div class="r-row-full"><span class="lbl">Compulsory:</span><span class="val" style="font-size:8px;">{{ $compulsorySubjects->map(fn($ss)=>$ss->subject?->name)->filter()->implode(', ') }}</span></div>
                    @endif

                    <hr class="r-divider">
                    <div class="r-info-grid">
                        <div>
                            @if($student->admission_date)
                            <div class="r-row"><span class="lbl">Adm. Date:</span><span class="val">{{ \Carbon\Carbon::parse($student->admission_date)->format('d/m/Y') }}</span></div>
                            @endif
                            <div class="r-row"><span class="lbl">Adm. Type:</span><span class="val">{{ $admTypeLabel }}</span></div>
                        </div>
                        <div>
                            <div class="r-row"><span class="lbl">Source:</span><span class="val">{{ $admissionSourceLabel ?? 'Direct / Walk-in' }}</span></div>
                            @if(!empty($admissionSourceDetail))
                            <div class="r-row"><span class="lbl">Center:</span><span class="val">{{ $admissionSourceDetail }}</span></div>
                            @endif
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
                                <span class="pay-val amount-big">₹ {{ number_format($grandPaid, 2) }}</span>
                            </div>
                            @if($grandFine > 0)
                            <div class="pay-row">
                                <span class="pay-lbl">Fine Charged</span>
                                <span class="pay-val" style="color:#dc2626;">+ ₹ {{ number_format($grandFine, 2) }}</span>
                            </div>
                            @endif
                            @if($grandDisc > 0)
                            <div class="pay-row">
                                <span class="pay-lbl">Discount</span>
                                <span class="pay-val" style="color:#d97706;">- ₹ {{ number_format($grandDisc, 2) }}</span>
                            </div>
                            @endif
                            <hr class="pay-divider">
                            <div class="pay-row">
                                <span class="pay-lbl">Remaining Due</span>
                                @php $due = $remainingDue ?? $grandBalance; @endphp
                                <span class="pay-val amount-due {{ $due > 0 ? 'due-pos' : 'due-zero' }}">
                                    ₹ {{ number_format($due, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($receipt->transaction_ref)
                    <div class="r-row-full"><span class="lbl">Ref No:</span><span class="val">{{ $receipt->transaction_ref }}</span></div>
                    @endif
                    @if($receipt->remarks)
                    <div class="r-row-full"><span class="lbl">Note:</span><span class="val">{{ $receipt->remarks }}</span></div>
                    @endif
                    @if($receiptConfig['receipt_collected_by']['enabled'] ?? true)
                    <div class="r-row-full"><span class="lbl">Collected By:</span><span class="val">{{ $collBy }}</span></div>
                    @endif

                    @if($receipt->is_cancelled)
                    <div class="cancelled-bar">
                        <b>⚠ RECEIPT CANCELLED</b> —
                        {{ \Carbon\Carbon::parse($receipt->cancelled_at)->format('d/m/Y h:i A') }}
                        @if($receipt->cancel_reason) | Reason: {{ $receipt->cancel_reason }} @endif
                    </div>
                    @endif

                    @if($receiptConfig['receipt_footer_note']['enabled'] ?? true)
                    <div class="r-note">{{ $footNote }}</div>
                    @endif

                    <div class="r-footer">
                        <div class="r-sign">Student Sign</div>
                        <div class="r-sign">Authorized Sign</div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>

            {{-- ════ THERMAL VIEW ════ --}}
            <div id="thermal-view" style="display:none;">
                <div class="thermal-sheet">
                    @php $fr = 'display:flex;justify-content:space-between;margin-bottom:2px;font-size:10px;font-weight:600;'; @endphp

                    <div style="text-align:center;font-size:16px;font-weight:700;line-height:1.15;">{{ $inst->name ?? 'Institute Name' }}</div>

                    <div style="text-align:center;font-size:11px;font-weight:700;border:1px solid #000;padding:2px;margin:3px 0;">
                        Fee Receipt{{ $receipt->is_cancelled ? ' - CANCELLED' : '' }}{{ $receipt->session?->name ? ' (' . $receipt->session->name . ')' : '' }}
                    </div>

                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Date:</span><span>{{ \Carbon\Carbon::parse($rDate)->format('d/m/Y') }} {{ now()->format('h:i A') }}</span></div>
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Receipt No:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $rNo }}</span></div>
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Mode:</span><span>{{ strtoupper($payMode) }}</span></div>
                    @if($receipt->transaction_ref ?? null)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Ref:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $receipt->transaction_ref }}</span></div>
                    @endif

                    <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Name:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->name) }}</span></div>
                    @if($student->father_name)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Father:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->father_name) }}</span></div>
                    @endif
                    @if($student->mother_name)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Mother:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($student->mother_name) }}</span></div>
                    @endif
                    <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
                    @if($formNo)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Form No:</span><span>{{ $formNo }}</span></div>
                    @endif
                    @if($enrollNo)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Enroll No:</span><span>{{ $enrollNo }}</span></div>
                    @endif
                    @if($rollNo)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Roll No:</span><span>{{ $rollNo }}</span></div>
                    @endif
                    @if($uinNo)
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">UIN:</span><span>{{ $uinNo }}</span></div>
                    @endif

                    <div style="text-align:center;font-size:10px;font-weight:700;border-top:0.8px dashed #555;padding-top:3px;margin-top:3px;margin-bottom:2px;">{{ $student->stream->course->name ?? '' }} | {{ $student->coursePart?->year_label ?? '' }}@if($student->current_semester) / Sem {{ $student->current_semester }}@endif</div>
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Adm Type:</span><span>{{ $admTypeLabel }}</span></div>
                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Source:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $admissionSourceLabel ?? 'Direct / Walk-in' }}@if(!empty($admissionSourceDetail)) / {{ $admissionSourceDetail }}@endif</span></div>

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

                    <div style="{{ $fr }}"><span style="white-space:nowrap;">Cashier:</span><span>{{ $collBy }}</span></div>
                    <div style="text-align:center;font-size:9px;padding:1px 0;">Printed: {{ now()->format('d/m/Y h:i A') }}</div>

                    @if($receipt->is_cancelled)
                    <div style="text-align:center;font-size:9px;font-weight:700;border:1px solid #000;padding:2px;margin:3px 0;">*** RECEIPT CANCELLED ***</div>
                    <div style="text-align:center;font-size:9px;">{{ \Carbon\Carbon::parse($receipt->cancelled_at)->format('d/m/Y h:i A') }}</div>
                    @if($receipt->cancel_reason)
                    <div style="text-align:center;font-size:9px;">Reason: {{ $receipt->cancel_reason }}</div>
                    @endif
                    @else
                    <div style="text-align:center;font-size:9px;padding:3px 0 1px;">नोट - कृपया रसीद को सुरक्षित रखें।</div>
                    @endif

                    <div style="text-align:center;font-size:9px;border-top:1.5px solid #000;margin-top:3px;padding-top:2px;">Fees once paid are non-refundable.</div>
                    <div style="display:flex;justify-content:flex-end;margin-top:2mm;font-size:10px;font-weight:700;border-top:1px solid #000;padding-top:2px;">
                        <span>Auth. Sign</span>
                    </div>
                </div>
            </div>

        </div>{{-- end preview-pane --}}
    </div>{{-- end workspace --}}
</div>{{-- end page-shell --}}

<script>
let currentMode = 'a4';
const queryParams = new URLSearchParams(window.location.search);

function syncPageStyle(mode) {
    let style = document.getElementById('_printPageStyle');
    if (!style) {
        style = document.createElement('style');
        style.id = '_printPageStyle';
        document.head.appendChild(style);
    }
    style.textContent = mode === 'thermal' ? thermalPageCss() : '';
}

function thermalPageCss() {
    const sheet = document.querySelector('#thermal-view .thermal-sheet');
    const heightMm = sheet ? Math.max(70, Math.ceil(sheet.scrollHeight * 25.4 / 96) + 10) : 140;
    return `@page { size: 80mm ${heightMm}mm; margin: 0mm; }
@media print { html, body { width:80mm; height:${heightMm}mm; margin:0 !important; padding:0 !important; overflow:hidden !important; } }`;
}

function setMode(mode) {
    currentMode = mode;
    document.getElementById('printBody').className = mode === 'thermal' ? 'print-thermal' : 'print-a4';
    document.getElementById('a4-view').style.display      = mode === 'a4'      ? 'block' : 'none';
    document.getElementById('thermal-view').style.display = mode === 'thermal' ? 'block' : 'none';
    document.getElementById('tabA4').classList.toggle('active',      mode === 'a4');
    document.getElementById('tabThermal').classList.toggle('active', mode === 'thermal');
    syncPageStyle(mode);
}

function printReceipt() {
    document.getElementById('printBody').className = currentMode === 'thermal' ? 'print-thermal' : 'print-a4';
    syncPageStyle(currentMode);
    if (currentMode === 'thermal') {
        printWithoutBrowserTitle(() => window.print());
        return;
    }
    window.print();
}

function savePDF() {
    document.getElementById('printBody').className = 'print-pdf';
    syncPageStyle('a4');
    setTimeout(() => window.print(), 100);
}

function printWithoutBrowserTitle(callback) {
    const oldTitle = document.title;
    document.title = '';
    const restore = () => { document.title = oldTitle; window.removeEventListener('afterprint', restore); };
    window.addEventListener('afterprint', restore);
    callback();
}

document.addEventListener('DOMContentLoaded', () => {
    const requestedMode = queryParams.get('mode');
    setMode(requestedMode === 'thermal' ? 'thermal' : 'a4');
    if (queryParams.get('autoprint') === '1') {
        setTimeout(() => printReceipt(), 180);
    }
});
</script>
</body>
</html>
