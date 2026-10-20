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
    $classTitle = $blocks->first()['title'] ?? ($student->stream?->course?->name ?? '');
@endphp
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; margin: 0; background: {{ $pdf ? '#fff' : '#eef1f5' }}; }
    .toolbar { background: #1e293b; color: #fff; padding: 10px 16px; font-size: 13px; }
    .toolbar a, .toolbar button, .toolbar select { font-size: 12px; padding: 5px 12px; border-radius: 4px; border: 1px solid #94a3b8; background: #fff; color: #1e293b; text-decoration: none; cursor: pointer; margin-right: 6px; }
    .toolbar .primary { background: #16a34a; border-color: #16a34a; color: #fff; }
    .page { background: #fff; width: 210mm; max-width: 100%; margin: 12px auto; padding: {{ $pdf ? "0" : "7mm 8mm" }}; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .head td { border: 0; padding: 0; }
    .inst-name { font-size: 15px; font-weight: 700; }
    .inst-sub { font-size: 9.5px; margin-top: 1px; }
    .meta td { border: 0; padding: 0; font-size: 9.5px; }
    .info { margin-top: 4px; }
    .info td { border: 1px solid #000; padding: 1.5px 4px; font-size: 9.5px; }
    .info .k { font-weight: 700; white-space: nowrap; background: #f1f5f9; width: 15%; }
    .block { margin-top: 5px; page-break-inside: avoid; }
    .block-head { width: 100%; border: 1px solid #000; background: #e2e8f0; }
    .block-head td { padding: 2.5px 6px; font-weight: 700; font-size: 10.5px; border: 0; }
    .block-sub td { border: 1px solid #000; border-top: 0; padding: 3px 6px; font-size: 10.5px; }
    .cols { width: 100%; border: 1px solid #000; border-top: 0; }
    .cols > tbody > tr > td { padding: 0; border-right: 1px solid #000; }
    .cols > tbody > tr > td:last-child { border-right: 0; }
    .inner th { background: #f1f5f9; font-size: 9px; padding: 2px 4px; border-bottom: 1px solid #000; text-align: left; }
    .inner td { padding: 1.5px 4px; font-size: 9px; border-bottom: 1px dotted #94a3b8; }
    .inner .r { text-align: right; white-space: nowrap; }
    .inner .tot td { border-top: 1px solid #000; border-bottom: 0; font-weight: 700; }
    .block-foot { width: 100%; border: 1px solid #000; border-top: 0; }
    .block-foot td { padding: 2.5px 6px; font-weight: 700; font-size: 10px; border: 0; }
    .muted { color: #475569; }
    .red { color: #b91c1c; }
    .green { color: #15803d; }
    .summary { margin-top: 6px; page-break-inside: avoid; }
    .summary th { background: #e2e8f0; border: 1px solid #000; padding: 2.5px 6px; font-size: 10.5px; text-align: left; }
    .summary td { border: 1px solid #000; padding: 2px 8px; font-size: 10px; }
    .summary td.r { text-align: right; font-weight: 700; width: 28%; }
    .summary tr.balance td { font-size: 11px; font-weight: 700; background: #f8fafc; }
    .foot { margin-top: 4px; font-size: 8.5px; color: #475569; }
    @media print {
        body { background: #fff; }
        .toolbar { display: none !important; }
        .page { margin: 0; width: auto; padding: 0; }
        @page { size: A4 portrait; margin: 9mm; }
    }
</style>
</head>
<body>

@unless($pdf)
<div class="toolbar">
    <a href="{{ $backUrl ?: 'javascript:history.back()' }}">&larr; Back</a>
    <form method="GET" action="{{ route($selfRoute, $student->id) }}" style="display:inline;">
        <select name="session_id" onchange="this.form.submit()" title="Show years up to this session">
            @foreach($sessions as $sess)
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

@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
