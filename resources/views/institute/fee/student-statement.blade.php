<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fee Statement — {{ $student->name }}</title>
@php
    $money = fn($v) => number_format((float) $v, 2);
    $logoData = null;
    try {
        if (!empty($institute?->image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($institute->image)) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($institute->image);
            $logoData = 'data:' . (mime_content_type($path) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($path));
        }
    } catch (\Throwable $e) {
        $logoData = null;
    }
    $identity = $identity ?? null;
    $rollNo   = $identity?->roll_no_snapshot ?: ($identity?->roll_no ?: $student->roll_no);
    $formNo   = $student->institute_form_no ?: $identity?->form_no;
    // Text size scales with the amount of content so the statement fills one A4 page (like the printed receipt)
    $rowsEstimate = 12 + 7;
    foreach ($blocks as $b) {
        $rowsEstimate += max($b['items']->count() + 3 + ($b['fine'] > 0 ? 1 : 0), $b['transactions']->count() + 3) + 3;
    }
    $fs  = $fsOverride ?? max(8.5, min(11.5, round((480 / max($rowsEstimate, 24)), 1)));
    $pad = round($fs * 0.17, 1);
    $gap = round($fs * 0.55, 1);
    $classTitle = $blocks->first()['title'] ?? ($student->stream?->course?->name ?? '');
@endphp
<style>
    @page { size: A4 portrait; margin: 8mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: {{ $fs }}px; color: #000; margin: 0; background: {{ $pdf ? '#fff' : '#eef1f5' }}; }
    .toolbar { background: #1e293b; color: #fff; padding: 10px 16px; font-size: 13px; }
    .toolbar a, .toolbar button, .toolbar select { font-size: 12px; padding: 5px 12px; border-radius: 4px; border: 1px solid #94a3b8; background: #fff; color: #1e293b; text-decoration: none; cursor: pointer; margin-right: 6px; }
    .toolbar .primary { background: #16a34a; border-color: #16a34a; color: #fff; }
    .page { background: #fff; width: 194mm; max-width: 100%; margin: 12px auto; padding: {{ $pdf ? '0' : '8mm' }}; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .head td { border: 0; padding: 0; }
    .inst-name { font-size: {{ $fs + 6 }}px; font-weight: 700; }
    .inst-sub { font-size: {{ $fs - 0.5 }}px; margin-top: 1px; }
    .meta td { border: 0; padding: 0; font-size: {{ $fs - 0.5 }}px; }
    .info { margin-top: {{ $gap }}px; }
    .info td { border: 1px solid #000; padding: {{ $pad }}px 4px; font-size: {{ $fs - 0.5 }}px; }
    .info .k { font-weight: 700; white-space: nowrap; background: #f1f5f9; width: 15%; }
    .block { margin-top: {{ $gap + 1 }}px; page-break-inside: avoid; }
    .block-head { width: 100%; border: 1px solid #000; background: #e2e8f0; }
    .block-head td { padding: {{ $pad + 1 }}px 6px; font-weight: 700; font-size: {{ $fs + 0.5 }}px; border: 0; }
    .cols { width: 100%; border: 1px solid #000; border-top: 0; }
    .cols > tbody > tr > td { padding: 0; border-right: 1px solid #000; }
    .cols > tbody > tr > td:last-child { border-right: 0; }
    .inner th { background: #f1f5f9; font-size: {{ $fs - 1 }}px; padding: {{ $pad }}px 4px; border-bottom: 1px solid #000; text-align: left; }
    .inner td { padding: {{ $pad }}px 4px; font-size: {{ $fs - 1 }}px; border-bottom: 1px dotted #94a3b8; }
    .inner .r { text-align: right; white-space: nowrap; }
    .inner .tot td { border-top: 1px solid #000; border-bottom: 0; font-weight: 700; }
    .block-foot { width: 100%; border: 1px solid #000; border-top: 0; }
    .block-foot td { padding: {{ $pad + 1 }}px 6px; font-weight: 700; font-size: {{ $fs }}px; border: 0; }
    .muted { color: #475569; }
    .red { color: #b91c1c; }
    .green { color: #15803d; }
    .summary { margin-top: {{ $gap + 2 }}px; page-break-inside: avoid; }
    .summary th { background: #e2e8f0; border: 1px solid #000; padding: {{ $pad + 1 }}px 6px; font-size: {{ $fs + 0.5 }}px; text-align: left; }
    .summary td { border: 1px solid #000; padding: {{ $pad + 0.5 }}px 8px; font-size: {{ $fs }}px; }
    .summary td.r { text-align: right; font-weight: 700; width: 28%; }
    .summary tr.balance td { font-size: {{ $fs + 1 }}px; font-weight: 700; background: #f8fafc; }
    .foot { margin-top: 4px; font-size: {{ max(8, $fs - 2) }}px; color: #475569; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none !important; }
        .page { margin: 0; width: 194mm; max-width: none; padding: 0; }
    }
</style>
</head>
<body>

@unless($pdf)
<div class="toolbar">
    <a href="{{ $backUrl ?: 'javascript:history.back()' }}">&larr; Back</a>
    <form method="GET" action="{{ route($selfRoute, $student->id) }}" style="display:inline;">
        <select name="session_id" onchange="this.form.submit()" title="Show years up to this session">
            @foreach($sessions->reverse() as $sess)
                <option value="{{ $sess->id }}" {{ (int) ($as_of_session?->id ?? 0) === (int) $sess->id ? 'selected' : '' }}>
                    Up to {{ $sess->name }}
                </option>
            @endforeach
        </select>
    </form>
    <button type="button" class="primary" onclick="window.print()">Print</button>
    <a href="{{ route($pdfRoute, ['student' => $student->id, 'session_id' => $as_of_session?->id]) }}">Download PDF</a>
</div>
@endunless

<div class="page">

    {{-- Institute header --}}
    <table class="head">
        <tr>
            @if($logoData)
            <td style="width:62px;"><img src="{{ $logoData }}" alt="" style="width:54px;height:54px;object-fit:contain;"></td>
            @endif
            <td>
                <div class="inst-name">{{ $institute?->name }}</div>
                @if(!empty($institute?->address))<div class="inst-sub">{{ $institute->address }}</div>@endif
                <div class="inst-sub"><strong>Fee Statement ({{ $as_of_session?->name }})</strong></div>
            </td>
            <td style="width:34%;">
                <table class="meta">
                    <tr><td><strong>Print Date:</strong></td><td>{{ $printedAt }}</td></tr>
                    <tr><td><strong>UIN No.:</strong></td><td>{{ $student->uin_no ?: '—' }}</td></tr>
                    <tr><td><strong>Exam Form No.:</strong></td><td>{{ $student->exam_form_no ?: '—' }}</td></tr>
                    <tr><td><strong>Form No.:</strong></td><td>{{ $formNo ?: '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Student details --}}
    <table class="info">
        <tr>
            <td class="k">Student Name</td><td>{{ $student->name }}</td>
            <td class="k">Student ID</td><td>{{ $student->student_uid }}</td>
        </tr>
        <tr>
            <td class="k">Father's Name</td><td>{{ $student->father_name ?: '—' }}</td>
            <td class="k">Mother's Name</td><td>{{ $student->mother_name ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Class Name</td><td>{{ $classTitle ?: '—' }}</td>
            <td class="k">Roll No.</td><td>{{ $rollNo ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Subject Name</td>
            <td colspan="3">
                @forelse($subject_names as $i => $subjectName)({{ $i + 1 }}) {{ strtoupper($subjectName) }}&nbsp; @empty — @endforelse
            </td>
        </tr>
        <tr>
            <td class="k">Mobile No.</td><td>{{ $student->mobile ?: '—' }}</td>
            <td class="k">Guardian Mobile No.</td><td>{{ $student->guardian_mobile ?: ($student->father_mobile ?: '—') }}</td>
        </tr>
        <tr>
            <td class="k">Date of Birth</td><td>{{ $student->dob?->format('d-M-Y') ?: '—' }}</td>
            <td class="k">Admission Date</td><td>{{ $student->admission_date?->format('d-M-Y') ?: '—' }} ({{ ucfirst((string) ($student->admission_type ?: 'new')) }})</td>
        </tr>
        <tr>
            <td class="k">Address</td><td colspan="3">{{ $address ?: '—' }}</td>
        </tr>
    </table>

    {{-- Year-wise blocks (newest first) --}}
    @forelse($blocks as $block)
    <div class="block">
        <table class="block-head">
            <tr>
                <td>{{ $block['title'] ?: 'FEE' }} ({{ $block['session_name'] }})
                    @if($block['year_label']) &nbsp;<span class="muted" style="font-weight:400;">{{ $block['year_label'] }}{{ $block['semester'] ? ' / Sem ' . $block['semester'] : '' }}</span>@endif
                </td>
                <td style="text-align:center;font-weight:400;">Admission Place: <strong>{{ $block['admission_place'] }}</strong></td>
                <td style="text-align:right;">Session Fee: ₹{{ $money($block['session_fee']) }}</td>
            </tr>
        </table>
        <table class="cols">
            <tr>
                <td style="width:36%;border-right:1px solid #000;">
                    <table class="inner">
                        <tr><th>College Fee Details</th><th class="r" style="text-align:right;">Amount</th></tr>
                        @foreach($block['items'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}@if($item['carried_in']) <span class="muted">(brought forward)</span>@endif</td>
                            <td class="r">{{ $money($item['charged']) }}</td>
                        </tr>
                        @endforeach
                        <tr class="tot"><td>College Fee Total</td><td class="r">{{ $money($block['college_fee'] + $block['carried_in']) }}</td></tr>
                        @if($block['fine'] > 0)
                        <tr><td>Fine</td><td class="r">{{ $money($block['fine']) }}</td></tr>
                        @endif
                    </table>
                </td>
                <td>
                    <table class="inner">
                        <tr>
                            <th colspan="7">Student Submitted Fee</th>
                        </tr>
                        <tr>
                            <th>Date</th><th class="r" style="text-align:right;">Paid</th><th class="r" style="text-align:right;">Discount</th>
                            <th class="r" style="text-align:right;">Fine</th><th>Receipt No.</th><th>Fee Centre</th><th>Mode</th>
                        </tr>
                        @forelse($block['transactions'] as $txn)
                        <tr>
                            <td style="white-space:nowrap;">{{ $txn['date']?->format('d-M-Y') }}</td>
                            <td class="r">{{ $money($txn['paid']) }}</td>
                            <td class="r">{{ $money($txn['discount']) }}</td>
                            <td class="r">{{ $money($txn['fine']) }}</td>
                            <td style="white-space:nowrap;">{{ $txn['receipt_no'] }}</td>
                            <td>{{ $txn['fee_centre'] }}</td>
                            <td>{{ $txn['mode'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:8px;">No fee submitted</td></tr>
                        @endforelse
                        <tr class="tot">
                            <td>Total</td>
                            <td class="r">{{ $money($block['paid']) }}</td>
                            <td class="r">{{ $money($block['discount']) }}</td>
                            <td class="r">{{ $money($block['transactions']->sum('fine')) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="block-foot">
            <tr>
                <td>College Fee: ₹{{ $money($block['college_fee'] + $block['carried_in']) }}</td>
                <td style="text-align:center;">Submitted: ₹{{ $money($block['paid']) }}</td>
                <td style="text-align:right;" class="{{ $block['balance'] > 0.009 ? 'red' : 'green' }}">
                    Balance: ₹{{ $money($block['balance']) }}
                    @if($block['carried_forward'])<span class="muted" style="font-weight:400;"> (carried forward)</span>@endif
                </td>
            </tr>
        </table>
    </div>
    @empty
    <p class="muted" style="margin-top:14px;">No fee records found for this student up to the selected session.</p>
    @endforelse

    {{-- Overall summary --}}
    @if($blocks->isNotEmpty())
    <table class="summary">
        <tr><th colspan="2">Overall Fee Summary</th></tr>
        <tr><td>Total College Fee</td><td class="r">₹{{ $money($totals['college_fee']) }}</td></tr>
        <tr><td>Total Submitted Fee</td><td class="r">₹{{ $money($totals['paid']) }}</td></tr>
        <tr><td>Total Discount</td><td class="r">₹{{ $money($totals['discount']) }}</td></tr>
        <tr><td>Total Fine</td><td class="r">₹{{ $money($totals['fine']) }}</td></tr>
        <tr class="balance"><td>OVERALL BALANCE</td><td class="r {{ $totals['balance'] > 0.009 ? 'red' : 'green' }}">₹{{ $money($totals['balance']) }}</td></tr>
    </table>
    <div class="foot">
        Overall Balance = Total College Fee + Total Fine − Total Submitted Fee − Total Discount.
        A year's unpaid balance that was brought forward into the next year is counted once.
    </div>
    @endif
</div>

@unless($pdf)
<script>
// Print: scale the statement so it fills exactly one A4 page (as on the printed receipt) —
// larger text when there is little data, shrunk to fit when there is a lot.
(function () {
    var MM = 96 / 25.4, BASE_W = 194, AVAIL_H = (297 - 16) * MM;
    function fit() {
        var page = document.querySelector('.page');
        if (!page) { return; }
        page.style.padding = '0';
        page.style.zoom = '';
        page.style.width = BASE_W + 'mm';
        var s = Math.max(0.6, Math.min(1.25, AVAIL_H / page.scrollHeight));
        for (var i = 0; i < 16; i++) {
            page.style.width = (BASE_W / s) + 'mm';
            page.style.zoom = s;
            if (page.scrollHeight * s <= AVAIL_H || s <= 0.6) { break; }
            s *= 0.97;
        }
    }
    function reset() {
        var page = document.querySelector('.page');
        if (!page) { return; }
        page.style.zoom = '';
        page.style.width = '';
        page.style.padding = '';
    }
    window.addEventListener('beforeprint', fit);
    window.addEventListener('afterprint', reset);
})();
</script>
@endunless

@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
