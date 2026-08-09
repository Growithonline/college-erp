<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Wallet — {{ $student->student_uid }}</title>
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
.summary-strip { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
.sum-box {
    flex:1; min-width:110px; border:1.5px solid #cbd5e1; border-radius:8px; text-align:center; padding:8px 6px;
}
.sum-box .lbl { font-size:9px; font-weight:700; color:#000; text-transform:uppercase; letter-spacing:.06em; }
.sum-box .val { font-size:15px; font-weight:800; color:#000; margin-top:2px; }
.sum-bal  { background:#eff6ff; border-color:#93c5fd; }
.sum-paid { background:#f0fdf4; border-color:#86efac; }
.sum-due  { background:#fef2f2; border-color:#fca5a5; }
.sum-fine { background:#fffbeb; border-color:#fcd34d; }
.sum-disc { background:#f5f3ff; border-color:#c4b5fd; }

h2.sec-title { font-size:12.5px; font-weight:800; color:#000; margin:16px 0 6px; text-transform:uppercase; letter-spacing:.04em; }

/* Tables */
table.wp { width:100%; border-collapse:collapse; margin-bottom:10px; }
table.wp thead { display:table-header-group; }
table.wp th {
    background:#0f766e; color:#fff; font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.04em; padding:6px 7px; text-align:left; border:1px solid #0f766e;
}
table.wp td {
    font-size:10.5px; font-weight:700; color:#000; padding:5px 7px;
    border:1px solid #cbd5e1; vertical-align:top;
}
table.wp tbody tr:nth-child(even) { background:#f8fafc; }
table.wp .num { text-align:right; white-space:nowrap; }
table.wp tfoot td {
    font-size:11px; font-weight:800; color:#000; background:#f1f5f9;
    border:1px solid #cbd5e1; padding:6px 7px;
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
    table.wp { page-break-inside:auto; }
    table.wp tr { page-break-inside:avoid; page-break-after:auto; }
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

    $authUser = auth()->guard('staff')->user()
        ?? auth()->guard('center')->user()
        ?? auth()->guard('partner')->user()
        ?? auth()->guard('web')->user();

    $pendingRows = $pendingFees->where('pending', '>', 0);
@endphp

<div class="toolbar no-print">
    <span class="title">Student Wallet — {{ $student->name }} ({{ $student->student_uid }})</span>
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
        <div class="doc-title">Student Wallet Statement</div>
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
        @if($selectedSession?->name)
        <div class="sb-item"><div class="lbl">Session</div><div class="val">{{ $selectedSession->name }}</div></div>
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

    {{-- ── Summary ── --}}
    <div class="summary-strip">
        <div class="sum-box sum-bal">
            <div class="lbl">{{ $selectedSession->name ?? '' }} Balance</div>
            <div class="val">₹ {{ number_format(abs($summary['balance']), 2) }} {{ $summary['balance'] < 0 ? '(Due)' : ($summary['balance'] > 0 ? '(Adv)' : '') }}</div>
        </div>
        <div class="sum-box"><div class="lbl">Total Charged</div><div class="val">₹ {{ number_format($summary['total_charged'], 2) }}</div></div>
        <div class="sum-box sum-paid"><div class="lbl">Total Paid</div><div class="val">₹ {{ number_format($summary['total_paid'], 2) }}</div></div>
        <div class="sum-box sum-disc"><div class="lbl">Total Discount</div><div class="val">₹ {{ number_format($summary['total_discount'], 2) }}</div></div>
        <div class="sum-box sum-fine"><div class="lbl">Total Fine</div><div class="val">₹ {{ number_format($summary['total_fine'] ?? 0, 2) }}</div></div>
        <div class="sum-box sum-due"><div class="lbl">Pending Due</div><div class="val">₹ {{ number_format($summary['total_due'], 2) }}</div></div>
    </div>

    {{-- ── Pending Fees ── --}}
    @if($pendingRows->isNotEmpty())
    <h2 class="sec-title">Pending Fees — {{ $selectedSession->name ?? '' }}</h2>
    <table class="wp">
        <thead>
            <tr>
                <th>Fee Type</th>
                <th class="num">Charged</th>
                <th class="num">Cash Paid</th>
                <th class="num">Discount</th>
                <th class="num">Fine</th>
                <th class="num">Pending</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingRows as $fee)
            <tr>
                <td>{{ $fee['name'] }}</td>
                <td class="num">₹ {{ number_format($fee['charged'], 2) }}</td>
                <td class="num">₹ {{ number_format($fee['collection'] ?? 0, 2) }}</td>
                <td class="num">{{ ($fee['discount'] ?? 0) > 0 ? '₹ '.number_format($fee['discount'], 2) : '—' }}</td>
                <td class="num">{{ ($fee['fine'] ?? 0) > 0 ? '₹ '.number_format($fee['fine'], 2) : '—' }}</td>
                <td class="num">₹ {{ number_format($fee['pending'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ── Transaction Ledger ── --}}
    <h2 class="sec-title">Transaction Ledger — {{ $selectedSession->name ?? '' }}</h2>
    <table class="wp">
        <thead>
            <tr>
                <th>#</th><th>Date</th><th>Description</th><th>Type</th>
                <th class="num">Debit</th><th class="num">Credit</th>
                <th class="num">Op. Bal</th><th class="num">Cl. Bal</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBal = 0; @endphp
            @forelse($transactions as $i => $txn)
            @php
                $opBal = $runningBal;
                if ($txn->type == 1) { $runningBal -= (float) $txn->debit; } else { $runningBal += (float) $txn->credit; }
                $clBal = $runningBal;
                $isDebit = $txn->type == 1;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $txn->date->format('d M Y') }}</td>
                <td>{{ $txn->des }}</td>
                <td>{{ $isDebit ? 'Debit' : 'Credit' }}</td>
                <td class="num">{{ $txn->debit > 0 ? '₹ '.number_format($txn->debit, 2) : '—' }}</td>
                <td class="num">{{ $txn->credit > 0 ? '₹ '.number_format($txn->credit, 2) : '—' }}</td>
                <td class="num">₹ {{ number_format($opBal, 2) }}</td>
                <td class="num">₹ {{ number_format($clBal, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:20px;">No transactions in this session.</td></tr>
            @endforelse
        </tbody>
        @if($transactions->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">TOTAL</td>
                <td class="num">₹ {{ number_format($transactions->sum('debit'), 2) }}</td>
                <td class="num">₹ {{ number_format($transactions->sum('credit'), 2) }}</td>
                <td></td>
                <td class="num">₹ {{ number_format($clBal ?? 0, 2) }}</td>
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
