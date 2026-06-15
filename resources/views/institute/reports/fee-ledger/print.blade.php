<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fee Ledger Report — {{ $institute?->name }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

    .report-header { text-align: center; padding: 12px 0 8px; border-bottom: 2px solid #1e40af; margin-bottom: 10px; }
    .report-header h1 { font-size: 18px; font-weight: bold; color: #1e40af; margin-bottom: 2px; }
    .report-header h2 { font-size: 13px; font-weight: normal; color: #374151; }
    .report-header .meta { font-size: 11px; color: #6b7280; margin-top: 4px; }

    .summary-row { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
    .summary-card { flex: 1; min-width: 120px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 10px; }
    .summary-card .label { font-size: 10px; color: #6b7280; }
    .summary-card .value { font-size: 14px; font-weight: bold; color: #111827; margin-top: 2px; }
    .summary-card.due .value { color: #dc2626; }
    .summary-card.paid .value { color: #16a34a; }

    .course-section { margin-bottom: 20px; page-break-inside: avoid; }
    .course-header {
        background: #1e40af; color: #fff;
        padding: 6px 10px; font-size: 12px; font-weight: bold;
        display: flex; justify-content: space-between; align-items: center;
        border-radius: 4px 4px 0 0;
    }
    .course-header .course-stats { font-size: 10px; font-weight: normal; opacity: 0.9; }

    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    thead tr { background: #f1f5f9; }
    th { padding: 5px 6px; text-align: left; font-weight: bold; color: #374151; border: 1px solid #d1d5db; white-space: nowrap; }
    td { padding: 4px 6px; border: 1px solid #e5e7eb; vertical-align: top; }
    tr:nth-child(even) td { background: #f9fafb; }
    tr.due-row td { background: #fef2f2; }

    td.text-right, th.text-right { text-align: right; }
    td.text-center, th.text-center { text-align: center; }

    .status-due    { color: #dc2626; font-weight: bold; }
    .status-paid   { color: #16a34a; font-weight: bold; }
    .status-none   { color: #9ca3af; }

    .course-total td { background: #f1f5f9 !important; font-weight: bold; border-top: 2px solid #1e40af; }

    .grand-total-section { margin-top: 16px; border: 2px solid #1e40af; border-radius: 4px; padding: 10px 14px; }
    .grand-total-section h3 { font-size: 13px; color: #1e40af; margin-bottom: 8px; }
    .gt-grid { display: flex; gap: 20px; flex-wrap: wrap; }
    .gt-item { }
    .gt-item .gt-label { font-size: 10px; color: #6b7280; }
    .gt-item .gt-val { font-size: 14px; font-weight: bold; }

    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        .no-print { display: none !important; }
        .course-section { page-break-inside: avoid; }
        .course-section:not(:last-child) { page-break-after: auto; }
    }
</style>
</head>
<body>

{{-- Print button --}}
<div class="no-print" style="padding:10px; background:#f9fafb; border-bottom:1px solid #e5e7eb; display:flex; gap:8px; align-items:center;">
    <button onclick="window.print()" style="padding:6px 14px; background:#1e40af; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px;">
        &#128438; Print
    </button>
    <button onclick="window.close()" style="padding:6px 14px; background:#6b7280; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px;">
        Close
    </button>
    <span style="font-size:12px; color:#6b7280;">Total Students: <strong>{{ number_format($summary->total_students) }}</strong></span>
</div>

<div style="padding: 12px 16px;">

    {{-- Header --}}
    <div class="report-header">
        <h1>{{ $institute?->name ?? 'College ERP' }}</h1>
        <h2>Fee Ledger Report — Course Wise</h2>
        <div class="meta">
            Generated: {{ now()->format('d M Y, h:i A') }}
            @if(!empty($filters['session_id'])) &nbsp;|&nbsp; Session filtered @endif
            @if(!empty($filters['due_only'])) &nbsp;|&nbsp; Due students only @endif
        </div>
    </div>

    {{-- Summary --}}
    <div class="summary-row">
        <div class="summary-card">
            <div class="label">Total Students</div>
            <div class="value">{{ number_format($summary->total_students) }}</div>
        </div>
        <div class="summary-card paid">
            <div class="label">Total Paid</div>
            <div class="value">₹ {{ number_format($summary->total_paid) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Discount</div>
            <div class="value">₹ {{ number_format($summary->total_discount) }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Fine</div>
            <div class="value">₹ {{ number_format($summary->total_fine) }}</div>
        </div>
        <div class="summary-card" style="border-color:#0891b2;">
            <div class="label">Library Fine Due</div>
            <div class="value" style="color:#0891b2;">₹ {{ number_format($summary->total_library_fine ?? 0) }}</div>
        </div>
        <div class="summary-card due">
            <div class="label">Total Due</div>
            <div class="value">₹ {{ number_format($summary->total_due) }}</div>
        </div>
        <div class="summary-card due">
            <div class="label">Students with Due</div>
            <div class="value">{{ number_format($summary->due_count) }}</div>
        </div>
    </div>

    {{-- Course-wise sections --}}
    @foreach($grouped as $courseName => $rows)
    @php
        $coursePaid        = $rows->sum('total_paid');
        $courseDiscount    = $rows->sum('total_discount');
        $courseFine        = $rows->sum('total_fine');
        $courseLibFine     = $rows->sum('library_fine_due');
        $courseInvoiced    = $rows->sum('total_invoiced');
        $courseDue         = $rows->sum(fn($r) => $r->wallet_balance < 0 ? abs($r->wallet_balance) : 0);
        $courseDueCount    = $rows->filter(fn($r) => $r->wallet_balance < 0 || $r->library_fine_due > 0)->count();
    @endphp
    <div class="course-section">
        <div class="course-header">
            <span>{{ strtoupper($courseName) }}</span>
            <span class="course-stats">
                {{ $rows->count() }} Students &nbsp;|&nbsp;
                Paid: ₹{{ number_format($coursePaid) }} &nbsp;|&nbsp;
                Due: ₹{{ number_format($courseDue) }} ({{ $courseDueCount }} students)
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:28px;">#</th>
                    <th style="min-width:120px;">Student Name</th>
                    <th style="width:85px;">Student ID</th>
                    <th style="width:65px;">Roll No</th>
                    <th style="width:90px;">Mobile</th>
                    <th style="width:110px;">Father Name</th>
                    <th style="width:110px;">Mother Name</th>
                    <th style="width:95px;">Course</th>
                    <th style="width:90px;">Stream</th>
                    <th style="width:55px;" class="text-center">Yr/Sem</th>
                    <th style="width:80px;">Session</th>
                    <th style="width:75px;" class="text-right">Invoiced</th>
                    <th style="width:68px;" class="text-right">Paid</th>
                    <th style="width:60px;" class="text-right">Discount</th>
                    <th style="width:50px;" class="text-right">Fine</th>
                    <th style="width:60px;" class="text-right">Lib Fine</th>
                    <th style="width:65px;" class="text-right">Due</th>
                    <th style="width:55px;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $idx => $row)
                @php
                    $libFine = (float) ($row->library_fine_due ?? 0);
                    $due     = $row->wallet_balance < 0 ? abs($row->wallet_balance) : 0;
                    $yearSem = $row->year_number
                        ? 'Yr ' . $row->year_number
                        : ($row->current_semester ? 'S' . $row->current_semester : '—');
                @endphp
                <tr class="{{ ($due > 0 || $libFine > 0) ? 'due-row' : '' }}">
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $row->name }}</strong></td>
                    <td>{{ $row->student_uid }}</td>
                    <td>{{ $row->roll_no ?? '—' }}</td>
                    <td>{{ $row->mobile ?? '—' }}</td>
                    <td>{{ $row->father_name ?? '—' }}</td>
                    <td>{{ $row->mother_name ?? '—' }}</td>
                    <td>{{ $row->course_name }}</td>
                    <td>{{ $row->stream_name }}</td>
                    <td class="text-center">{{ $yearSem }}</td>
                    <td>{{ $row->session_name ?? '—' }}</td>
                    <td class="text-right">₹{{ number_format($row->total_invoiced) }}</td>
                    <td class="text-right" style="color:#16a34a;">₹{{ number_format($row->total_paid) }}</td>
                    <td class="text-right" style="color:#0284c7;">{{ $row->total_discount > 0 ? '₹'.number_format($row->total_discount) : '—' }}</td>
                    <td class="text-right" style="color:#d97706;">{{ $row->total_fine > 0 ? '₹'.number_format($row->total_fine) : '—' }}</td>
                    <td class="text-right" style="color:#0891b2;">{{ $libFine > 0 ? '₹'.number_format($libFine) : '—' }}</td>
                    <td class="text-right">
                        @if($due > 0)
                            <span class="status-due">₹{{ number_format($due) }}</span>
                        @else
                            <span class="status-none">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($due > 0 && $libFine > 0)
                            <span class="status-due">Due+Lib</span>
                        @elseif($due > 0)
                            <span class="status-due">Due</span>
                        @elseif($libFine > 0)
                            <span style="color:#0891b2;font-weight:bold;">Lib Fine</span>
                        @elseif($row->total_paid > 0)
                            <span class="status-paid">Paid</span>
                        @else
                            <span class="status-none">None</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="course-total">
                    <td colspan="11" class="text-right">{{ $courseName }} Total ({{ $rows->count() }} students):</td>
                    <td class="text-right">₹{{ number_format($courseInvoiced) }}</td>
                    <td class="text-right" style="color:#16a34a;">₹{{ number_format($coursePaid) }}</td>
                    <td class="text-right" style="color:#0284c7;">₹{{ number_format($courseDiscount) }}</td>
                    <td class="text-right" style="color:#d97706;">₹{{ number_format($courseFine) }}</td>
                    <td class="text-right" style="color:#0891b2;">₹{{ number_format($courseLibFine) }}</td>
                    <td class="text-right" style="color:#dc2626;">₹{{ number_format($courseDue) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endforeach

    {{-- Grand Total --}}
    <div class="grand-total-section">
        <h3>Grand Total — All Courses</h3>
        <div class="gt-grid">
            <div class="gt-item">
                <div class="gt-label">Total Students</div>
                <div class="gt-val">{{ number_format($summary->total_students) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Total Invoiced</div>
                <div class="gt-val">₹ {{ number_format($grouped->flatten()->sum('total_invoiced')) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Total Paid</div>
                <div class="gt-val" style="color:#16a34a;">₹ {{ number_format($summary->total_paid) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Total Discount</div>
                <div class="gt-val">₹ {{ number_format($summary->total_discount) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Total Fine</div>
                <div class="gt-val">₹ {{ number_format($summary->total_fine) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Library Fine Due</div>
                <div class="gt-val" style="color:#0891b2;">₹ {{ number_format($summary->total_library_fine ?? 0) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Total Due</div>
                <div class="gt-val" style="color:#dc2626;">₹ {{ number_format($summary->total_due) }}</div>
            </div>
            <div class="gt-item">
                <div class="gt-label">Students with Due</div>
                <div class="gt-val" style="color:#dc2626;">{{ number_format($summary->due_count) }}</div>
            </div>
        </div>
    </div>

</div>

<script>
window.addEventListener('load', function () {
    // Auto-open print dialog if desired
    // window.print();
});
</script>
</body>
</html>
