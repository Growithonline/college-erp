<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; margin:0; padding:0; }

        /* Header */
        .header { border-bottom: 2.5px solid #0f766e; padding-bottom: 7px; margin-bottom: 8px; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }
        .logo-box { width:48px; height:48px; border:1px solid #d1d5db; border-radius:8px;
                    text-align:center; line-height:48px; font-size:16px; font-weight:700;
                    color:#0f766e; overflow:hidden; }
        .logo-box img { width:48px; height:48px; object-fit:cover; }
        .inst-name { font-size:16px; font-weight:800; color:#0f172a; }
        .inst-sub  { font-size:9.5px; color:#0f766e; font-weight:600; margin-top:2px; }
        .meta-right { text-align:right; font-size:8px; color:#475569; line-height:1.8; }

        /* Summary chips */
        .chips-table { width:100%; margin-bottom:8px; }
        .chip { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:5px;
                padding:4px 8px; display:inline-block; }
        .chip-label { font-size:7.5px; color:#6b7280; text-transform:uppercase; letter-spacing:.4px; }
        .chip-value { font-size:12px; font-weight:700; color:#065f46; margin-top:1px; }

        /* Table */
        table.data { width:100%; border-collapse:collapse; }
        table.data thead th {
            background:#0f766e; color:#fff; font-size:7.5px; font-weight:700;
            padding:4px 4px; text-align:left; white-space:nowrap;
        }
        table.data tbody td {
            padding:3.5px 4px; font-size:8px; border-bottom:1px solid #e5e7eb; vertical-align:top;
        }
        table.data tbody tr:nth-child(even) { background:#f8fafc; }
        table.data tbody tr:last-child td { border-bottom:none; }
        .num { text-align:center; color:#6b7280; }
        .fw  { font-weight:700; }
        .muted { color:#6b7280; font-size:7.5px; }
        .badge { display:inline-block; padding:1px 5px; border-radius:3px;
                 font-size:7px; font-weight:700; white-space:nowrap; }
        .badge-active   { background:#dcfce7; color:#166534; }
        .badge-pending  { background:#fef9c3; color:#854d0e; }
        .badge-inactive { background:#f3f4f6; color:#374151; }
        .badge-detained { background:#fee2e2; color:#991b1b; }
        .badge-default  { background:#f3f4f6; color:#374151; }
        .uid-badge { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
                     border-radius:3px; padding:1px 4px; font-size:7.5px; font-weight:700; }

        /* Footer */
        .footer-table { width:100%; margin-top:6px; border-top:1px solid #e5e7eb; padding-top:5px; }
        .footer-table td { font-size:7.5px; color:#9ca3af; }
        .footer-table td:last-child { text-align:right; }
    </style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:58px; padding-right:10px;">
                <div class="logo-box">
                    @if(!empty($institute->image) && file_exists(public_path($institute->image)))
                        <img src="{{ public_path($institute->image) }}" alt="Logo">
                    @else
                        {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="inst-name">{{ $institute->name }}</div>
                <div class="inst-sub">My Students Report &mdash; {{ $center->name }}</div>
            </td>
            <td class="meta-right">
                <div>Session: <strong>{{ $sessionName }}</strong></div>
                <div>Total Students: <strong>{{ $students->count() }}</strong></div>
                <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- Summary --}}
@php
    $activeCount   = $students->where('status','active')->count();
    $pendingCount  = $students->where('status','pending')->count();
    $inactiveCount = $students->whereIn('status',['inactive','detained','cancelled'])->count();
@endphp
<table class="chips-table" cellpadding="0" cellspacing="4">
    <tr>
        <td><div class="chip"><div class="chip-label">Total</div><div class="chip-value">{{ $students->count() }}</div></div></td>
        <td><div class="chip"><div class="chip-label">Active</div><div class="chip-value">{{ $activeCount }}</div></div></td>
        <td><div class="chip"><div class="chip-label">Pending</div><div class="chip-value">{{ $pendingCount }}</div></div></td>
        <td><div class="chip"><div class="chip-label">Other</div><div class="chip-value">{{ $inactiveCount }}</div></div></td>
        <td></td><td></td><td></td>
    </tr>
</table>

{{-- Table --}}
<table class="data" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="num" style="width:20px;">#</th>
            <th style="width:85px;">Student Name</th>
            <th style="width:72px;">Father Name</th>
            <th style="width:72px;">Mother Name</th>
            <th style="width:60px;">Mobile</th>
            <th style="width:75px;">Student ID</th>
            <th style="width:75px;">Course</th>
            <th style="width:60px;">Stream</th>
            <th style="width:40px;">Year</th>
            <th style="width:28px;">Sem</th>
            <th style="width:38px;">Session</th>
            <th style="width:65px;">Admitted By</th>
            <th style="width:42px;">Status</th>
            <th style="width:48px;">Adm. Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $i => $student)
        @php
            $badgeClass = match($student->status) {
                'active'    => 'badge-active',
                'pending'   => 'badge-pending',
                'inactive','detained','cancelled' => 'badge-inactive',
                default     => 'badge-default',
            };
        @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>
                <div class="fw">{{ $student->name }}</div>
                <div class="muted">{{ $student->mobile }}</div>
            </td>
            <td>{{ $student->father_name ?: '—' }}</td>
            <td>{{ $student->mother_name ?: '—' }}</td>
            <td class="muted">{{ $student->mobile }}</td>
            <td><span class="uid-badge">{{ $student->student_uid }}</span></td>
            <td style="font-size:7.5px;">{{ $student->stream->course->name ?? '—' }}</td>
            <td class="muted">{{ $student->stream->name ?? '—' }}</td>
            <td class="muted">{{ $student->coursePart?->year_label ?? '—' }}</td>
            <td class="num muted">{{ $student->current_semester ? 'S'.$student->current_semester : '—' }}</td>
            <td class="muted">{{ $student->session?->name ?? '—' }}</td>
            <td style="font-size:7.5px;">{{ $student->admittedBy?->name ?? '—' }}</td>
            <td><span class="badge {{ $badgeClass }}">{{ ucfirst($student->status ?? 'pending') }}</span></td>
            <td class="muted">{{ $student->admission_date?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Footer --}}
<table class="footer-table" cellpadding="0" cellspacing="0">
    <tr>
        <td>{{ $institute->name }} &mdash; {{ $center->name }} | Students Report | Session: {{ $sessionName }}</td>
        <td>Generated on {{ now()->format('d M Y, h:i A') }}</td>
    </tr>
</table>

</body>
</html>
