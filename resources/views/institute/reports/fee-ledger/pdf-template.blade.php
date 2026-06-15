<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Ledger Report</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #1a1a1a; }

    .report-header { text-align: center; padding: 6px 0 5px; border-bottom: 2px solid #1e40af; margin-bottom: 7px; }
    .report-header h1 { font-size: 14px; font-weight: bold; color: #1e40af; }
    .report-header h2 { font-size: 10px; color: #374151; margin-top: 2px; }
    .report-header .meta { font-size: 8px; color: #6b7280; margin-top: 2px; }

    .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .summary-table td { padding: 3px 6px; border: 1px solid #e5e7eb; font-size: 9px; }
    .summary-table .lbl { background: #f1f5f9; font-weight: bold; color: #374151; width: 14%; }
    .summary-table .val { color: #111827; }

    .course-section { margin-bottom: 12px; }
    .course-header {
        background: #1e40af; color: #ffffff;
        padding: 3px 6px; font-size: 9px; font-weight: bold;
    }
    .course-stats { font-size: 8px; font-weight: normal; }

    table.data {
        width: 100%; border-collapse: collapse; font-size: 7.5px;
        table-layout: fixed;
    }
    table.data th {
        background: #e2e8f0; padding: 2px 3px;
        text-align: left; border: 1px solid #cbd5e1;
        font-weight: bold; overflow: hidden;
        word-wrap: break-word;
    }
    table.data td { padding: 2px 3px; border: 1px solid #e2e8f0; overflow: hidden; word-wrap: break-word; }
    table.data tr.even td { background: #f8fafc; }
    table.data tr.due td { background: #fef2f2; }
    table.data tfoot td { background: #e2e8f0; font-weight: bold; border-top: 1.5px solid #1e40af; }

    .tr { text-align: right; }
    .tc { text-align: center; }
    .due-amt { color: #dc2626; font-weight: bold; }
    .paid-amt { color: #16a34a; }
    .disc-amt { color: #0284c7; }
    .fine-amt { color: #d97706; }

    .grand-total { border: 1.5px solid #1e40af; padding: 6px 8px; margin-top: 10px; }
    .grand-total h3 { font-size: 10px; color: #1e40af; margin-bottom: 5px; font-weight: bold; }
    .gt-table { width: 100%; border-collapse: collapse; }
    .gt-table td { padding: 2px 8px 2px 0; vertical-align: top; }
    .gt-lbl { font-size: 8px; color: #6b7280; }
    .gt-val { font-size: 11px; font-weight: bold; }

    .page-break { page-break-after: always; }
</style>
</head>
<body>

<div class="report-header">
    <h1>{{ $instituteName }}</h1>
    <h2>Fee Ledger Report &mdash; Course Wise</h2>
    <div class="meta">Generated: {{ $generatedAt }}</div>
</div>

{{-- Summary --}}
<table class="summary-table">
    <tr>
        <td class="lbl">Total Students</td>
        <td class="val">{{ number_format($summary->total_students) }}</td>
        <td class="lbl">Total Paid</td>
        <td class="val paid-amt">Rs {{ number_format($summary->total_paid) }}</td>
        <td class="lbl">Discount</td>
        <td class="val disc-amt">Rs {{ number_format($summary->total_discount) }}</td>
    </tr>
    <tr>
        <td class="lbl">Due Students</td>
        <td class="val due-amt">{{ number_format($summary->due_count) }}</td>
        <td class="lbl">Total Due</td>
        <td class="val due-amt">Rs {{ number_format($summary->total_due) }}</td>
        <td class="lbl">Fine</td>
        <td class="val fine-amt">Rs {{ number_format($summary->total_fine) }}</td>
    </tr>
    <tr>
        <td class="lbl">Library Fine Due</td>
        <td class="val" style="color:#0891b2;font-weight:bold;">Rs {{ number_format($summary->total_library_fine ?? 0) }}</td>
        <td colspan="4"></td>
    </tr>
</table>

{{-- Course-wise data --}}
@foreach($grouped as $courseName => $rows)
@php
    $coursePaid     = $rows->sum('total_paid');
    $courseDiscount = $rows->sum('total_discount');
    $courseFine     = $rows->sum('total_fine');
    $courseLibFine  = $rows->sum('library_fine_due');
    $courseInvoiced = $rows->sum('total_invoiced');
    $courseDue      = $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0);
@endphp
<div class="course-section">
    <div class="course-header">
        {{ strtoupper($courseName) }} &nbsp;
        <span class="course-stats">
            | {{ $rows->count() }} Students
            | Paid: Rs{{ number_format($coursePaid) }}
            | Due: Rs{{ number_format($courseDue) }}
        </span>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:2%;">#</th>
                <th style="width:10%;">Student Name</th>
                <th style="width:7%;">Student ID</th>
                <th style="width:4%;">Roll No</th>
                <th style="width:6%;">Mobile</th>
                <th style="width:7%;">Father Name</th>
                <th style="width:7%;">Mother Name</th>
                <th style="width:6%;">Course</th>
                <th style="width:5%;">Stream</th>
                <th style="width:4%;" class="tc">Yr/Sem</th>
                <th style="width:5%;">Session</th>
                <th style="width:5%;" class="tr">Invoiced</th>
                <th style="width:5%;" class="tr">Paid</th>
                <th style="width:4%;" class="tr">Discount</th>
                <th style="width:3%;" class="tr">Fine</th>
                <th style="width:5%;" class="tr">Lib Fine</th>
                <th style="width:5%;" class="tr">Due</th>
                <th style="width:5%;" class="tc">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $row)
            @php
                $libFine = (float) ($row->library_fine_due ?? 0);
                $due     = $row->wallet_balance < 0 ? abs($row->wallet_balance) : 0;
                $yearSm  = $row->year_number
                    ? 'Yr' . $row->year_number
                    : ($row->current_semester ? 'S' . $row->current_semester : '-');
                $cls     = ($due > 0 || $libFine > 0) ? 'due' : ($idx % 2 === 0 ? '' : 'even');
            @endphp
            <tr class="{{ $cls }}">
                <td class="tc">{{ $idx + 1 }}</td>
                <td><strong>{{ $row->name }}</strong></td>
                <td>{{ $row->student_uid }}</td>
                <td>{{ $row->roll_no ?? '-' }}</td>
                <td>{{ $row->mobile ?? '-' }}</td>
                <td>{{ $row->father_name ?? '-' }}</td>
                <td>{{ $row->mother_name ?? '-' }}</td>
                <td>{{ $row->course_name }}</td>
                <td>{{ $row->stream_name }}</td>
                <td class="tc">{{ $yearSm }}</td>
                <td>{{ $row->session_name ?? '-' }}</td>
                <td class="tr">{{ number_format($row->total_invoiced) }}</td>
                <td class="tr paid-amt">{{ number_format($row->total_paid) }}</td>
                <td class="tr disc-amt">{{ $row->total_discount > 0 ? number_format($row->total_discount) : '-' }}</td>
                <td class="tr fine-amt">{{ $row->total_fine > 0 ? number_format($row->total_fine) : '-' }}</td>
                <td class="tr" style="color:#0891b2;">{{ $libFine > 0 ? number_format($libFine) : '-' }}</td>
                <td class="tr">
                    @if($due > 0)
                        <span class="due-amt">{{ number_format($due) }}</span>
                    @else <span style="color:#9ca3af;">-</span>
                    @endif
                </td>
                <td class="tc">
                    @if($due > 0 && $libFine > 0)
                        <span class="due-amt">Due+Lib</span>
                    @elseif($due > 0)
                        <span class="due-amt">Due</span>
                    @elseif($libFine > 0)
                        <span style="color:#0891b2;font-weight:bold;">LibFine</span>
                    @elseif($row->total_paid > 0)
                        <span class="paid-amt">Paid</span>
                    @else
                        <span style="color:#9ca3af;">-</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" class="tr">Total ({{ $rows->count() }}):</td>
                <td class="tr">{{ number_format($courseInvoiced) }}</td>
                <td class="tr paid-amt">{{ number_format($coursePaid) }}</td>
                <td class="tr disc-amt">{{ number_format($courseDiscount) }}</td>
                <td class="tr fine-amt">{{ number_format($courseFine) }}</td>
                <td class="tr" style="color:#0891b2;">{{ number_format($courseLibFine) }}</td>
                <td class="tr due-amt">{{ number_format($courseDue) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endforeach

{{-- Grand Total --}}
<div class="grand-total">
    <h3>Grand Total — All Courses</h3>
    <table class="gt-table">
        <tr>
            <td><div class="gt-lbl">Total Students</div><div class="gt-val">{{ number_format($summary->total_students) }}</div></td>
            <td><div class="gt-lbl">Total Paid</div><div class="gt-val paid-amt">Rs {{ number_format($summary->total_paid) }}</div></td>
            <td><div class="gt-lbl">Discount</div><div class="gt-val disc-amt">Rs {{ number_format($summary->total_discount) }}</div></td>
            <td><div class="gt-lbl">Fine</div><div class="gt-val fine-amt">Rs {{ number_format($summary->total_fine) }}</div></td>
            <td><div class="gt-lbl">Lib Fine Due</div><div class="gt-val" style="color:#0891b2;font-size:11px;font-weight:bold;">Rs {{ number_format($summary->total_library_fine ?? 0) }}</div></td>
            <td><div class="gt-lbl">Total Due</div><div class="gt-val due-amt">Rs {{ number_format($summary->total_due) }}</div></td>
            <td><div class="gt-lbl">Due Students</div><div class="gt-val due-amt">{{ number_format($summary->due_count) }}</div></td>
        </tr>
    </table>
</div>

</body>
</html>
