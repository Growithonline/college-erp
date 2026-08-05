<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee History — {{ $student->student_uid }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { font-family:Arial,sans-serif; background:#e2e8f0; color:#000; font-size:12px; }

.toolbar {
    position:sticky; top:0; z-index:10;
    background:#1e293b; padding:10px 16px;
    display:flex; align-items:center; justify-content:space-between; gap:10px;
}
.toolbar .title { color:#e2e8f0; font-size:12px; font-weight:600; }
.toolbar .actions { display:flex; gap:8px; }
.tb-btn {
    padding:7px 16px; border-radius:5px; font-size:12px; font-weight:700;
    border:none; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;
}
.tb-print { background:#0f766e; color:#fff; }
.tb-close { background:#334155; color:#e2e8f0; }

.sheet-wrap { padding:22px 0 40px; }
.sheet {
    width:210mm; min-height:297mm; background:#fff; margin:0 auto;
    padding:14mm 12mm; box-shadow:0 0 10px rgba(0,0,0,.15);
}

/* Letterhead */
.letterhead { text-align:center; border-bottom:3px solid #0f766e; padding-bottom:8px; margin-bottom:12px; }
.letterhead .inst-name { font-size:20px; font-weight:800; color:#000; letter-spacing:.2px; }
.letterhead .inst-addr { font-size:11px; font-weight:600; color:#000; margin-top:2px; }
.letterhead .doc-title {
    display:inline-block; margin-top:8px; font-size:12px; font-weight:700; color:#000;
    text-transform:uppercase; letter-spacing:1.5px; background:#f0fdfa; border:1px solid #99f6e4;
    border-radius:20px; padding:3px 16px;
}

/* Meta line */
.print-meta { display:flex; justify-content:space-between; font-size:10.5px; font-weight:700; color:#000; margin:8px 0 12px; }

/* Student info box */
.student-box {
    border:1.5px solid #cbd5e1; border-radius:8px; padding:10px 14px; margin-bottom:14px;
    display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px 18px; background:#f8fafc;
}
.student-box .full { grid-column:1 / -1; font-size:15px; font-weight:800; color:#000; margin-bottom:2px; }
.sb-item .lbl { font-size:9px; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:.06em; }
.sb-item .val { font-size:12px; font-weight:700; color:#000; }

/* Summary strip */
.summary-strip { display:flex; gap:10px; margin-bottom:14px; }
.sum-box {
    flex:1; border:1.5px solid #cbd5e1; border-radius:8px; text-align:center; padding:8px 6px;
}
.sum-box .lbl { font-size:9px; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:.06em; }
.sum-box .val { font-size:16px; font-weight:800; color:#000; margin-top:2px; }
.sum-paid { background:#f0fdf4; border-color:#86efac; }
.sum-due  { background:#fef2f2; border-color:#fca5a5; }
.sum-fine { background:#fffbeb; border-color:#fcd34d; }
.sum-disc { background:#f5f3ff; border-color:#c4b5fd; }

/* Table */
table.hist { width:100%; border-collapse:collapse; margin-bottom:10px; }
table.hist thead { display:table-header-group; }
table.hist th {
    background:#0f766e; color:#fff; font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.04em; padding:6px 7px; text-align:left; border:1px solid #0f766e;
}
table.hist td {
    font-size:10.5px; font-weight:700; color:#000; padding:5px 7px;
    border:1px solid #cbd5e1; vertical-align:top;
}
table.hist tbody tr:nth-child(even) { background:#f8fafc; }
table.hist tbody tr.cancelled td { color:#000; background:#fef2f2; }
table.hist .num { text-align:right; white-space:nowrap; }
table.hist tfoot td {
    font-size:11px; font-weight:800; color:#000; background:#f1f5f9;
    border:1px solid #cbd5e1; padding:6px 7px;
}
.fee-tag {
    display:inline-block; font-size:9px; font-weight:700; color:#000;
    background:#f1f5f9; border:1px solid #cbd5e1; border-radius:3px;
    padding:1px 5px; margin:1px 3px 1px 0;
}
.cancel-tag {
    font-size:9px; font-weight:800; color:#000; background:#fecaca; border:1px solid #f87171;
    border-radius:3px; padding:1px 6px; display:inline-block; margin-top:2px;
}

/* Footer */
.doc-footer { display:flex; justify-content:space-between; align-items:flex-end; margin-top:26px; }
.doc-footer .note { font-size:9.5px; font-weight:700; color:#000; font-style:italic; max-width:60%; }
.doc-footer .sign { text-align:center; }
.doc-footer .sign .line { width:130px; border-top:1.5px solid #000; margin-bottom:3px; }
.doc-footer .sign .lbl { font-size:10px; font-weight:700; color:#000; }

@media print {
    html, body { background:#fff !important; height:auto !important; }
    .toolbar { display:none !important; }
    .sheet-wrap { padding:0; background:#fff; }
    .sheet { box-shadow:none; margin:0; width:auto; min-height:0; background:#fff; }
    @page { size: A4 portrait; margin:12mm; }
    table.hist { page-break-inside:auto; }
    table.hist tr { page-break-inside:avoid; page-break-after:auto; }
}
</style>
</head>
<body>

@php
    $inst = \App\Models\Institute::find($student->institute_id);
    $instituteAddress = trim(collect([
        $inst->address ?? null, $inst->city ?? null, $inst->state ?? null, $inst->pincode ?? null,
    ])->filter()->implode(', '));

    $identity = $student->currentAcademicIdentity;
    $rollNo   = $identity?->roll_no ?? $student->roll_no ?? null;
    $enrollNo = $identity?->enrollment_no_snapshot ?? $student->enrollment_no ?? null;

    $totalFine     = $invoices->where('is_cancelled', false)->sum(fn($i) => $i->items->sum('fine'));
    $totalDiscount = $invoices->where('is_cancelled', false)->sum(fn($i) => (float) ($i->discount ?? 0));
    $overallDue    = $sessionBalances->sum(fn($sb) => (float) $sb->main_b < 0 ? abs((float) $sb->main_b) : 0);

    $totalCharged  = $totalPaid + $overallDue;
    $runningDueMap = [];
    $rd = $totalCharged;
    foreach ($invoices->where('is_cancelled', false)->sortBy(fn($i) => $i->payment_date->timestamp * 1000000 + $i->id) as $_inv) {
        $rd -= (float) $_inv->paid_amount;
        $runningDueMap[$_inv->id] = max(0, round($rd, 2));
    }

    $authUser = auth()->guard('staff')->user()
        ?? auth()->guard('center')->user()
        ?? auth()->guard('partner')->user()
        ?? auth()->guard('web')->user();
@endphp

<div class="toolbar no-print">
    <span class="title">Fee History — {{ $student->name }} ({{ $student->student_uid }})</span>
    <div class="actions">
        <button type="button" class="tb-btn tb-print" onclick="doPrint()">🖨️ Print</button>
        <button type="button" class="tb-btn tb-close" onclick="window.close()">✕ Close</button>
    </div>
</div>

<div class="sheet-wrap">
<div class="sheet">

    <div class="letterhead">
        <div class="inst-name">{{ $inst->name ?? 'Institute Name' }}</div>
        @if($instituteAddress)
        <div class="inst-addr">{{ $instituteAddress }}</div>
        @endif
        <div class="doc-title">Fee Collection History</div>
    </div>

    <div class="print-meta">
        <span>Printed On: {{ now()->format('d M Y, h:i A') }}</span>
        <span>Printed By: {{ $authUser->name ?? '—' }}</span>
    </div>

    <div class="student-box">
        <div class="full">{{ $student->name }}</div>
        <div class="sb-item"><div class="lbl">Application No</div><div class="val">{{ $student->student_uid }}</div></div>
        @if($rollNo)
        <div class="sb-item"><div class="lbl">Roll No</div><div class="val">{{ $rollNo }}</div></div>
        @endif
        @if($enrollNo)
        <div class="sb-item"><div class="lbl">Enrollment No</div><div class="val">{{ $enrollNo }}</div></div>
        @endif
        <div class="sb-item">
            <div class="lbl">Course</div>
            <div class="val">{{ $student->stream->course->name ?? '—' }}@if($student->stream?->name) ({{ $student->stream->name }})@endif</div>
        </div>
        @if($student->session?->name)
        <div class="sb-item"><div class="lbl">Session</div><div class="val">{{ $student->session->name }}</div></div>
        @endif
        @if($student->mobile)
        <div class="sb-item"><div class="lbl">Mobile</div><div class="val">{{ $student->mobile }}</div></div>
        @endif
        @if($student->father_name)
        <div class="sb-item"><div class="lbl">Father's Name</div><div class="val">{{ $student->father_name }}</div></div>
        @endif
        @if($student->mother_name)
        <div class="sb-item"><div class="lbl">Mother's Name</div><div class="val">{{ $student->mother_name }}</div></div>
        @endif
    </div>

    <div class="summary-strip">
        <div class="sum-box sum-paid"><div class="lbl">Total Paid</div><div class="val">₹ {{ number_format($totalPaid, 2) }}</div></div>
        @if($totalFine > 0)
        <div class="sum-box sum-fine"><div class="lbl">Total Fine</div><div class="val">₹ {{ number_format($totalFine, 2) }}</div></div>
        @endif
        @if($totalDiscount > 0)
        <div class="sum-box sum-disc"><div class="lbl">Total Discount</div><div class="val">₹ {{ number_format($totalDiscount, 2) }}</div></div>
        @endif
        <div class="sum-box sum-due"><div class="lbl">Outstanding Due</div><div class="val">₹ {{ number_format($overallDue, 2) }}</div></div>
    </div>

    <table class="hist">
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Date</th>
                <th>Sem</th>
                <th>Fee Items</th>
                <th>Mode</th>
                <th>Collected By</th>
                <th class="num">Collection</th>
                <th class="num">Fine</th>
                <th class="num">Discount</th>
                <th class="num">Due</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
            @php
                $invFine = (float) $inv->items->sum('fine');
                $invDisc = (float) ($inv->discount ?? 0);
                $invDue  = $inv->is_cancelled ? null : ($inv->remaining_due !== null ? (float) $inv->remaining_due : ($runningDueMap[$inv->id] ?? null));
            @endphp
            <tr class="{{ $inv->is_cancelled ? 'cancelled' : '' }}">
                <td>
                    {{ $inv->invoice_no }}
                    <div style="font-size:9px;font-weight:600;">{{ $inv->created_at?->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                    @if($inv->is_cancelled)<span class="cancel-tag">CANCELLED</span>@endif
                </td>
                <td>{{ $inv->payment_date->format('d M Y') }}</td>
                <td>{{ $inv->semester ? 'S'.$inv->semester : '—' }}</td>
                <td>
                    @foreach($inv->items as $item)
                        <span class="fee-tag">{{ $item->fee_name }}: ₹{{ number_format($item->amount) }}</span>
                    @endforeach
                </td>
                <td>{{ strtoupper($inv->payment_mode) }}</td>
                <td>{{ $inv->collected_by ?? '—' }}</td>
                <td class="num">₹ {{ number_format($inv->paid_amount, 2) }}</td>
                <td class="num">{{ $invFine > 0 ? '₹ '.number_format($invFine, 2) : '—' }}</td>
                <td class="num">{{ $invDisc > 0 ? '₹ '.number_format($invDisc, 2) : '—' }}</td>
                <td class="num">
                    @if($inv->is_cancelled) — @elseif($invDue > 0) ₹ {{ number_format($invDue, 2) }} @else Cleared @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center;padding:20px;">No fee collection records found.</td></tr>
        @endforelse
        </tbody>
        @if($invoices->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;">TOTAL</td>
                <td class="num">₹ {{ number_format($totalPaid, 2) }}</td>
                <td class="num">{{ $totalFine > 0 ? '₹ '.number_format($totalFine, 2) : '—' }}</td>
                <td class="num">{{ $totalDiscount > 0 ? '₹ '.number_format($totalDiscount, 2) : '—' }}</td>
                <td class="num">₹ {{ number_format($overallDue, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="doc-footer">
        <div class="note">This is a computer-generated statement and does not require a physical signature unless issued as an official record.</div>
        <div class="sign">
            <div class="line"></div>
            <div class="lbl">Authorized Signatory</div>
        </div>
    </div>

</div>
</div>

<script>
function doPrint() {
    const oldTitle = document.title;
    document.title = '';
    const restore = () => { document.title = oldTitle; window.removeEventListener('afterprint', restore); };
    window.addEventListener('afterprint', restore);
    window.print();
}
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(doPrint, 200);
    }
});
</script>
</body>
</html>
