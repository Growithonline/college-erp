<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Library Fine Receipt — {{ $receiptNo }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; background:#f1f5f9; }

/* ── Screen wrapper ──────────────────────────── */
.screen-wrapper { max-width:900px; margin:20px auto; padding:20px; }
.screen-actions { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center; }
.btn { padding:8px 18px; border:none; border-radius:6px; cursor:pointer; font-size:13px;
       font-weight:600; display:inline-flex; align-items:center; gap:6px;
       text-decoration:none; transition:opacity .15s; }
.btn:hover { opacity:.85; }
.btn-primary   { background:#1d4ed8; color:white; }
.btn-success   { background:#16a34a; color:white; }
.btn-secondary { background:#64748b; color:white; }
.btn-outline   { background:white; color:#475569; border:1px solid #cbd5e1; }

.print-mode-tabs { display:flex; gap:8px; margin-bottom:12px; }
.tab-btn { padding:6px 16px; border:2px solid #cbd5e1; border-radius:6px; cursor:pointer;
           font-size:12px; font-weight:600; background:white; color:#64748b; }
.tab-btn.active { border-color:#1d4ed8; background:#eff6ff; color:#1d4ed8; }

/* ════════════════════════════════════════════
   A4 Layout — 1×2 grid (2 receipts per page)
   ════════════════════════════════════════════ */
#a4-view  { display:block; }
#thermal-view { display:none; }

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
    display:inline-block;
    font-size:7.5px; font-weight:700;
    padding:2px 7px; border-radius:20px; text-transform:uppercase;
    letter-spacing:.6px; margin-bottom:5px; align-self:flex-end;
}
.copy-student   { background:#dbeafe; color:#1d4ed8; border:1px solid #bfdbfe; }
.copy-institute { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }

/* Header */
.r-header {
    text-align:center;
    padding-bottom:5px;
    margin-bottom:6px;
    border-bottom: 2.5px solid #0f766e;
    position: relative;
}
.r-header::before {
    content:'';
    display:block;
    height:3px;
    background: linear-gradient(90deg,#0f766e,#0891b2);
    border-radius:2px;
    margin-bottom:5px;
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
.r-meta-bar .inv-no { font-weight:700; color:#0f172a; font-size:9px; }
.r-meta-bar .inv-date { color:#475569; text-align:right; line-height:1.5; }
.r-meta-bar .inv-date b { color:#0f172a; }

/* Info grid */
.r-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 6px; margin-bottom:6px; }
.r-row { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row .val { font-weight:600; color:#1e293b; flex:1; }
.r-row-full { display:flex; font-size:8.5px; margin-bottom:2.5px; }
.r-row-full .lbl { color:#64748b; min-width:62px; flex-shrink:0; font-size:8px; }
.r-row-full .val { font-weight:600; color:#1e293b; }

/* Section divider */
.r-divider { border:none; border-top:1px dashed #e2e8f0; margin:5px 0; }

/* Fine items table */
.fine-tbl { width:100%; border-collapse:collapse; font-size:7.5px; margin:4px 0; }
.fine-tbl thead th {
    background:#f8fafc; border-bottom:1.5px solid #e2e8f0;
    padding:3px 4px; text-align:center; font-weight:700;
    color:#64748b; text-transform:uppercase; letter-spacing:.3px;
}
.fine-tbl thead th:first-child { text-align:left; }
.fine-tbl tbody td {
    padding:3px 4px; border-bottom:1px solid #f1f5f9;
    color:#334155; vertical-align:top;
}
.fine-tbl tfoot td {
    padding:3px 4px; font-weight:700; font-size:8px;
    border-top:1.5px solid #e2e8f0; background:#f8fafc;
}

/* Payment summary box */
.pay-box {
    background: linear-gradient(135deg,#f0fdf4,#f0fdfa);
    border: 1.5px solid #6ee7b7;
    border-radius: 6px;
    overflow: hidden;
    margin: 5px 0;
}
.pay-box-head {
    background: linear-gradient(90deg,#0f766e,#0891b2);
    color:white; font-size:8.5px; font-weight:700;
    padding:3px 8px; letter-spacing:.3px;
}
.pay-box-body { padding:5px 8px; }
.pay-row { display:flex; justify-content:space-between; align-items:center;
           font-size:8.5px; padding:2px 0; }
.pay-row .pay-lbl { color:#475569; }
.pay-row .pay-val { font-weight:700; color:#0f172a; }
.pay-divider { border:none; border-top:1px solid #d1fae5; margin:3px 0; }
.amount-big { font-size:14px; font-weight:800; color:#15803d; }
.mode-chip {
    display:inline-block; font-size:7.5px; font-weight:700;
    padding:2px 8px; border-radius:20px; text-transform:uppercase; letter-spacing:.5px;
    background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;
}

/* Watermark */
.watermark { position:absolute; top:50%; left:50%;
             transform:translate(-50%,-50%) rotate(-25deg);
             font-size:48px; font-weight:900;
             color:rgba(15,118,110,0.04); pointer-events:none; white-space:nowrap; }

/* Footer */
.r-note { font-size:7.5px; color:#94a3b8; font-style:italic; margin-top:3px; }
.r-footer { display:flex; justify-content:space-between; margin-top:auto;
            padding-top:5px; border-top:1px dashed #e2e8f0; }
.r-sign { border-top:1px solid #94a3b8; width:65px; text-align:center;
          font-size:7.5px; padding-top:3px; color:#64748b; }

/* ════════════════════════════════════════════
   Thermal Layout — 80mm roll
   ════════════════════════════════════════════ */
.thermal-sheet {
    width: 76mm; max-width: 76mm;
    background: white; margin: 0 auto; padding: 1.5mm;
    border: 1px solid #ccc;
    font-family: Verdana, sans-serif;
    font-size: 10px; font-weight: 600; line-height: 1.3; color: #000;
}

/* ════════════════════════════════════════════
   Print Media
   ════════════════════════════════════════════ */
@media print {
    body { background:white; }
    .screen-wrapper { margin:0; padding:0; max-width:100%; }
    .screen-actions, .print-mode-tabs { display:none !important; }

    body.print-a4 #a4-view      { display:block !important; }
    body.print-a4 #thermal-view { display:none  !important; }
    body.print-a4 .a4-sheet     { width:210mm; min-height:148mm; height:auto; border:none; margin:0; }

    body.print-thermal #thermal-view { display:block !important; }
    body.print-thermal #a4-view      { display:none  !important; }
    body.print-thermal .thermal-sheet { border:none; width:76mm; max-width:76mm; margin:0; padding:5mm 2.6mm; }
    body.print-thermal { width:80mm; margin:0 !important; padding:0 !important; }
}
</style>
</head>
<body id="printBody" class="print-a4">
<div class="screen-wrapper">

@php
    $inst = \App\Models\Institute::find($member->institute_id);
    $sessionName = $member->student?->session?->name
        ?? \App\Models\AcademicSession::where('institute_id', $member->institute_id)
               ->where('is_active', true)
               ->value('name')
        ?? '';
    $slots = [
        ['label' => 'Student Copy', 'class' => 'copy-student'],
        ['label' => 'Office Copy',  'class' => 'copy-institute'],
    ];
    $fr = 'display:flex;justify-content:space-between;margin-bottom:2px;font-size:10px;font-weight:600;';
@endphp

{{-- ── Action Buttons ── --}}
<div class="screen-actions">
    <div class="print-mode-tabs">
        <button class="tab-btn active" id="tabA4"      onclick="setMode('a4')">📄 A4 (2 per page)</button>
        <button class="tab-btn"        id="tabThermal" onclick="setMode('thermal')">🖨️ Thermal (80mm)</button>
    </div>
    <button onclick="printReceipt()" class="btn btn-primary">🖨️ Print</button>
    <a href="{{ route($libraryRoutePrefix . '.fines.index') }}" class="btn btn-secondary">← Fine Collection</a>
</div>

{{-- ════ A4 VIEW — 2 copies ════ --}}
<div id="a4-view">
    <div class="a4-sheet">
    @foreach($slots as $slot)
    <div class="receipt">
        <div class="watermark">FINE PAID</div>

        {{-- Copy badge --}}
        <span class="copy-badge {{ $slot['class'] }}">{{ $slot['label'] }}</span>

        {{-- Header --}}
        <div class="r-header">
            <div class="inst">{{ $inst->name ?? 'Library' }}</div>
            <div class="title">Library Fine Receipt</div>
            @if($sessionName)
                <span class="session-tag">Session: {{ $sessionName }}</span>
            @endif
        </div>

        {{-- Meta bar --}}
        <div class="r-meta-bar">
            <div>
                <div style="font-size:7.5px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Receipt No</div>
                <div class="inv-no">{{ $receiptNo }}</div>
            </div>
            <div class="inv-date">
                <b>{{ optional($firstPayment->payment_date)->format('d M Y') }}</b><br>
                @if($firstPayment->payment_datetime)
                    <span style="font-size:8px;">{{ $firstPayment->payment_datetime->format('h:i A') }}</span>
                @endif
            </div>
        </div>

        {{-- Member info —  2-col grid --}}
        <div class="r-info-grid">
            <div>
                <div class="r-row"><span class="lbl">Name:</span><span class="val">{{ $member->name }}</span></div>
                <div class="r-row"><span class="lbl">Code:</span><span class="val">{{ $member->member_code }}</span></div>
                <div class="r-row"><span class="lbl">Type:</span><span class="val">{{ ucfirst($member->member_type) }}</span></div>
                @if($member->student)
                    <div class="r-row"><span class="lbl">Father:</span><span class="val">{{ $member->student->father_name ?: '—' }}</span></div>
                    <div class="r-row"><span class="lbl">Mother:</span><span class="val">{{ $member->student->mother_name ?: '—' }}</span></div>
                @elseif($member->staffMember)
                    <div class="r-row"><span class="lbl">Role:</span><span class="val">{{ $member->staffMember->role->name ?? '—' }}</span></div>
                @endif
            </div>
            <div>
                @if($member->student)
                    @if($member->student->roll_no)
                        <div class="r-row"><span class="lbl">Roll No:</span><span class="val">{{ $member->student->roll_no }}</span></div>
                    @endif
                    @if($member->student->uin_no)
                        <div class="r-row"><span class="lbl">UIN:</span><span class="val">{{ $member->student->uin_no }}</span></div>
                    @endif
                    <div class="r-row"><span class="lbl">Course:</span><span class="val">{{ $member->student->stream->course->name ?? '—' }}</span></div>
                @endif
                <div class="r-row"><span class="lbl">Mobile:</span><span class="val">{{ $member->mobile ?: '—' }}</span></div>
            </div>
        </div>

        <hr class="r-divider">

        {{-- Fine items table --}}
        <table class="fine-tbl">
            <thead>
                <tr>
                    <th style="text-align:left;">#  Book</th>
                    <th>Acc No</th>
                    <th>Due</th>
                    <th>Returned</th>
                    <th style="text-align:right;">Fine</th>
                    <th style="text-align:right;">Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $idx => $payment)
                <tr>
                    <td>{{ $idx+1 }}. {{ $payment->transaction->copy->book->title ?? '—' }}</td>
                    <td style="text-align:center;">{{ $payment->transaction->copy->accession_no ?? '—' }}</td>
                    <td style="text-align:center;">{{ optional($payment->transaction->due_on)->format('d-m-Y') }}</td>
                    <td style="text-align:center;">{{ optional($payment->transaction->returned_on)->format('d-m-Y') ?: '—' }}</td>
                    <td style="text-align:right;">{{ number_format((float)$payment->transaction->fine_amount,2) }}</td>
                    <td style="text-align:right;color:#15803d;font-weight:700;">{{ number_format((float)$payment->amount,2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">Total Collected</td>
                    <td style="text-align:right;color:#15803d;font-size:9px;">Rs {{ number_format($totalCollected,2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Payment summary box --}}
        <div class="pay-box">
            <div class="pay-box-head">Payment Summary</div>
            <div class="pay-box-body">
                <div class="pay-row">
                    <span class="pay-lbl">Mode</span>
                    <span class="mode-chip">{{ strtoupper($firstPayment->payment_mode) }}</span>
                </div>
                @if($firstPayment->transaction_ref)
                <hr class="pay-divider">
                <div class="pay-row">
                    <span class="pay-lbl">{{ $firstPayment->payment_mode === 'cheque' ? 'Cheque No' : ($firstPayment->payment_mode === 'dd' ? 'DD No' : 'Txn Ref / UTR') }}</span>
                    <span class="pay-val" style="font-size:8px;">{{ $firstPayment->transaction_ref }}</span>
                </div>
                @endif
                @if($firstPayment->bank_name)
                <div class="pay-row"><span class="pay-lbl">Bank</span><span class="pay-val">{{ $firstPayment->bank_name }}</span></div>
                @endif
                <hr class="pay-divider">
                <div class="pay-row">
                    <span class="pay-lbl">Total Fine Paid</span>
                    <span class="pay-val amount-big">Rs {{ number_format($totalCollected,2) }}</span>
                </div>
            </div>
        </div>

        <div class="r-row-full"><span class="lbl">Collected By:</span><span class="val">{{ $firstPayment->collected_by }}</span></div>
        @if($firstPayment->remarks)
            <div class="r-row-full"><span class="lbl">Note:</span><span class="val">{{ $firstPayment->remarks }}</span></div>
        @endif

        <div class="r-note">Library fine once paid is non-refundable.</div>

        <div class="r-footer">
            <div class="r-sign">Member Sign</div>
            <div class="r-sign">Auth. Sign</div>
        </div>
    </div>
    @endforeach
    </div>
</div>

{{-- ════ THERMAL VIEW ════ --}}
<div id="thermal-view">
    <div class="thermal-sheet">
        @php $fr = 'display:flex;justify-content:space-between;margin-bottom:2px;font-size:10px;font-weight:600;'; @endphp

        <div style="text-align:center;font-size:13px;font-weight:700;line-height:1.15;">{{ $inst->name ?? 'Library' }}</div>
        @if($sessionName)
            <div style="text-align:center;font-size:9px;margin-bottom:1px;">Session: {{ $sessionName }}</div>
        @endif
        <div style="text-align:center;font-size:11px;font-weight:700;border:1px solid #000;padding:2px;margin:3px 0;">Library Fine Receipt</div>

        <div style="{{ $fr }}"><span>Date:</span><span>{{ optional($firstPayment->payment_date)->format('d/m/Y') }}@if($firstPayment->payment_datetime) {{ $firstPayment->payment_datetime->format('h:i A') }}@endif</span></div>
        <div style="{{ $fr }}"><span>Receipt No:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $receiptNo }}</span></div>
        <div style="{{ $fr }}"><span>Mode:</span><span>{{ strtoupper($firstPayment->payment_mode) }}</span></div>
        @if($firstPayment->transaction_ref)
        <div style="{{ $fr }}"><span>Ref:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ $firstPayment->transaction_ref }}</span></div>
        @endif

        <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
        <div style="{{ $fr }}"><span>Name:</span><span style="text-align:right;max-width:44mm;word-break:break-word;">{{ strtoupper($member->name) }}</span></div>
        <div style="{{ $fr }}"><span>Code:</span><span>{{ $member->member_code }}</span></div>
        <div style="{{ $fr }}"><span>Type:</span><span>{{ ucfirst($member->member_type) }}</span></div>
        @if($member->student)
            @if($member->student->father_name)<div style="{{ $fr }}"><span>Father:</span><span>{{ $member->student->father_name }}</span></div>@endif
            @if($member->student->roll_no)<div style="{{ $fr }}"><span>Roll No:</span><span>{{ $member->student->roll_no }}</span></div>@endif
            @if($member->student->uin_no)<div style="{{ $fr }}"><span>UIN:</span><span>{{ $member->student->uin_no }}</span></div>@endif
            <div style="text-align:center;font-size:10px;font-weight:700;border-top:0.8px dashed #555;padding-top:3px;margin-top:3px;">{{ $member->student->stream->course->name ?? '' }}</div>
        @endif

        <div style="border-top:0.8px dashed #555;margin:3px 0 2px;"></div>
        @foreach($payments as $idx => $payment)
        <div style="font-size:10px;font-weight:700;margin-bottom:1px;">{{ $idx+1 }}. {{ $payment->transaction->copy->book->title ?? '—' }}</div>
        <div style="{{ $fr }}"><span style="opacity:.7;">Acc No:</span><span>{{ $payment->transaction->copy->accession_no ?? '—' }}</span></div>
        <div style="{{ $fr }}"><span style="opacity:.7;">Due:</span><span>{{ optional($payment->transaction->due_on)->format('d/m/Y') }}</span></div>
        <div style="{{ $fr }}"><span style="opacity:.7;">Fine:</span><span>Rs {{ number_format((float)$payment->transaction->fine_amount,2) }}</span></div>
        <div style="{{ $fr }}"><span style="opacity:.7;">Paid:</span><span>Rs {{ number_format((float)$payment->amount,2) }}</span></div>
        @if(!$loop->last)<div style="border-top:0.5px dashed #ccc;margin:2px 0;"></div>@endif
        @endforeach

        <div style="display:flex;justify-content:space-between;border-top:1.5px solid #000;margin-top:4px;padding-top:3px;font-size:13px;font-weight:700;">
            <span>Total Paid:</span><span>Rs {{ number_format($totalCollected,2) }}</span>
        </div>
        <div style="{{ $fr }}"><span>Cashier:</span><span>{{ $firstPayment->collected_by }}</span></div>
        <div style="text-align:center;font-size:9px;padding:1px 0;">Printed: {{ now()->format('d/m/Y h:i A') }}</div>
        <div style="text-align:center;font-size:9px;border-top:1.5px solid #000;margin-top:3px;padding-top:2px;">Library fine once paid is non-refundable.</div>
        <div style="display:flex;justify-content:space-between;margin-top:2mm;font-size:10px;font-weight:700;border-top:1px solid #000;padding-top:2px;">
            <span>Member Sign</span><span>Auth. Sign</span>
        </div>
    </div>
</div>

</div>

<script>
let currentMode = 'a4';

function syncPageStyle(mode) {
    let style = document.getElementById('_printPageStyle');
    if (!style) { style = document.createElement('style'); style.id = '_printPageStyle'; document.head.appendChild(style); }
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
    window.print();
}

document.addEventListener('DOMContentLoaded', () => setMode('a4'));
</script>
</body>
</html>
