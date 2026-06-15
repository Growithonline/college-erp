<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Due List</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #111; background: #fff; }

    .page-header { display: flex; justify-content: space-between; align-items: flex-start;
                   border-bottom: 2px solid #1e293b; padding-bottom: 8px; margin-bottom: 10px; }
    .page-header .inst-name { font-size: 16px; font-weight: 700; color: #1e293b; }
    .page-header .report-title { font-size: 12px; font-weight: 600; color: #1d4ed8; margin-top: 2px; }
    .page-header .meta { text-align: right; font-size: 9px; color: #475569; line-height: 1.6; }

    .filter-info { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;
                   padding: 5px 10px; margin-bottom: 10px; font-size: 9px; color: #555;
                   display: flex; flex-wrap: wrap; gap: 14px; }
    .filter-info strong { color: #1e293b; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    thead th { background: #1e293b; color: #fff; padding: 4px 5px; font-size: 8.5px;
               font-weight: 700; text-transform: uppercase; border: 1px solid #334155; text-align: left; }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }
    tbody td { padding: 3px 5px; border: 1px solid #e5e7eb; font-size: 9px; vertical-align: middle; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tfoot td { padding: 4px 5px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 9.5px;
               background: #f1f5f9; }
    tfoot td.r { text-align: right; }

    .t-success { color: #16a34a; font-weight: 600; }
    .t-danger  { color: #dc2626; font-weight: 600; }
    .t-purple  { color: #7c3aed; font-weight: 600; }
    .t-warning { color: #d97706; font-weight: 600; }
    .t-muted   { color: #94a3b8; }

    .grand-total { background: #1e293b; color: #fff; padding: 7px 12px; border-radius: 4px;
                   font-size: 11px; font-weight: 700; display: flex; justify-content: space-between;
                   align-items: center; margin-bottom: 10px; }
    .grand-total span { font-size: 10px; font-weight: 400; margin-left: 16px; }

    .footer { margin-top: 8px; display: flex; justify-content: space-between;
              font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }

    @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>

@php
    $grandPayable     = 0;
    $grandPaid        = 0;
    $grandDiscount    = 0;
    $grandFine        = 0;
    $grandLibraryFine = 0;
    $grandDue         = 0;
    $dueCount         = 0;
    foreach ($allStudents as $student) {
        $d = $allDueData[$student->id] ?? [];
        if (($d['due'] ?? 0) > 0) $dueCount++;
        $grandPayable     += $d['payable']      ?? 0;
        $grandPaid        += $d['paid']         ?? 0;
        $grandDiscount    += $d['discount']     ?? 0;
        $grandFine        += $d['fine']         ?? 0;
        $grandLibraryFine += $d['library_fine'] ?? 0;
        $grandDue         += $d['due']          ?? 0;
    }
@endphp

<div class="page-header">
    <div>
        <div class="inst-name">{{ $instituteName }}</div>
        <div class="report-title">Fee Due List</div>
    </div>
    <div class="meta">
        <div><strong>Session:</strong> {{ $sessionObj?->name ?? '—' }}</div>
        <div><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        <div><strong>Students with Due:</strong> {{ $dueCount }} &nbsp;|&nbsp; <strong>Total:</strong> {{ $allStudents->count() }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            <th style="width:90px;">Student Name</th>
            <th style="width:60px;">Student ID</th>
            <th style="width:50px;">Roll No</th>
            <th style="width:75px;">Father Name</th>
            <th style="width:75px;">Mother Name</th>
            <th style="width:75px;">Course</th>
            <th style="width:55px;">Stream</th>
            <th style="width:40px;" class="c">Year</th>
            <th style="width:40px;" class="c">Sem</th>
            <th class="r" style="width:48px;">Payable (₹)</th>
            <th class="r" style="width:42px;">Paid (₹)</th>
            <th class="r" style="width:38px;">Disc (₹)</th>
            <th class="r" style="width:35px;">Fine (₹)</th>
            <th class="r" style="width:38px;">Lib Fine (₹)</th>
            <th class="r" style="width:48px;">Due (₹)</th>
        </tr>
    </thead>
    <tbody>
        @php $serial = 0; @endphp
        @foreach($allStudents as $student)
        @php
            $d        = $allDueData[$student->id] ?? [];
            $payable  = $d['payable']      ?? 0;
            $paid     = $d['paid']         ?? 0;
            $discount = $d['discount']     ?? 0;
            $fine     = $d['fine']         ?? 0;
            $libFine  = $d['library_fine'] ?? 0;
            $due      = $d['due']          ?? 0;
            if ($due <= 0) continue;
            $serial++;
        @endphp
        <tr>
            <td class="t-muted">{{ $serial }}</td>
            <td style="font-weight:600;">{{ $student->name }}</td>
            <td class="t-muted">{{ $student->student_uid ?? '—' }}</td>
            <td class="t-muted">{{ $student->roll_no ?: '—' }}</td>
            <td>{{ $student->father_name ?: '—' }}</td>
            <td>{{ $student->mother_name ?: '—' }}</td>
            <td>{{ $student->stream->course->name ?? '—' }}</td>
            <td class="t-muted">{{ $student->stream->name ?? '—' }}</td>
            <td class="c t-muted">{{ $student->resolved_year_label ?? ('Year ' . ($student->coursePart?->year_number ?? '—')) }}</td>
            <td class="c t-muted">{{ $student->current_semester ? 'S'.$student->current_semester : '—' }}</td>
            <td class="r">{{ $payable > 0 ? number_format($payable, 2) : '—' }}</td>
            <td class="r t-success">{{ $paid > 0 ? number_format($paid, 2) : '0.00' }}</td>
            <td class="r t-purple">{{ $discount > 0 ? number_format($discount, 2) : '—' }}</td>
            <td class="r t-warning">{{ $fine > 0 ? number_format($fine, 2) : '—' }}</td>
            <td class="r" style="color:#0891b2;">{{ $libFine > 0 ? number_format($libFine, 2) : '—' }}</td>
            <td class="r t-danger" style="font-weight:700;">{{ number_format($due, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="10" class="r">Total ({{ $dueCount }} students with due)</td>
            <td class="r">{{ number_format($grandPayable, 2) }}</td>
            <td class="r t-success">{{ number_format($grandPaid, 2) }}</td>
            <td class="r t-purple">{{ number_format($grandDiscount, 2) }}</td>
            <td class="r t-warning">{{ number_format($grandFine, 2) }}</td>
            <td class="r" style="color:#0891b2;">{{ number_format($grandLibraryFine, 2) }}</td>
            <td class="r t-danger">{{ number_format($grandDue, 2) }}</td>
        </tr>
    </tfoot>
</table>

<div class="grand-total">
    <div>
        TOTAL DUE &nbsp;—&nbsp; {{ $dueCount }} Student(s)
        <span>Session: {{ $sessionObj?->name ?? '—' }}</span>
    </div>
    <div>₹ {{ number_format($grandDue, 2) }}</div>
</div>

<div class="footer">
    <span>Fee Due List — {{ $instituteName }} — Session: {{ $sessionObj?->name ?? '—' }}</span>
    <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
