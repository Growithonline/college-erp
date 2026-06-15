<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student List Report</title>
<style>
    @page { size: A4 landscape; margin: 10mm 8mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1f2937; margin:0; padding:0; }
    .header { border-bottom: 2.5px solid #7c3aed; padding-bottom: 7px; margin-bottom: 8px; }
    .header-table { width:100%; }
    .header-table td { vertical-align:middle; }
    .logo-box { width:46px; height:46px; border:1px solid #ddd6fe; border-radius:8px;
                text-align:center; line-height:46px; font-size:15px; font-weight:800; color:#7c3aed; }
    .logo-box img { width:46px; height:46px; object-fit:cover; border-radius:8px; }
    .report-title { font-size:17px; font-weight:800; color:#0f172a; }
    .report-sub   { font-size:9px; color:#7c3aed; font-weight:600; margin-top:2px; }
    .meta-right   { text-align:right; font-size:8px; color:#475569; line-height:1.9; }
    .meta-right strong { color:#0f172a; }
    .filter-bar { background:#f5f3ff; border:1px solid #ddd6fe; border-radius:5px; padding:5px 8px; margin-bottom:8px; }
    .f-chip { display:inline; font-size:7.5px; color:#5b21b6; margin-right:12px; }
    .f-chip strong { color:#4c1d95; }
    .s-chip { display:inline-block; background:#fff; border:1px solid #e2e8f0; border-radius:5px; padding:4px 10px; margin-right:6px; }
    .s-chip .lbl { font-size:7px; color:#94a3b8; text-transform:uppercase; letter-spacing:.4px; }
    .s-chip .val { font-size:12px; font-weight:800; }
    table.data { width:100%; border-collapse:collapse; }
    table.data thead th { background:#6d28d9; color:#fff; font-size:7.5px; font-weight:700; padding:4.5px 4px; text-align:left; white-space:nowrap; }
    table.data tbody td { padding:3.5px 4px; font-size:8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    table.data tbody tr:nth-child(even) { background:#f5f3ff; }
    .num { text-align:center; color:#94a3b8; }
    .fw  { font-weight:700; color:#0f172a; }
    .muted { color:#6b7280; font-size:7.5px; }
    .uid { background:#f5f3ff; color:#5b21b6; border:1px solid #ddd6fe; border-radius:3px; padding:1px 4px; font-size:7.5px; font-weight:700; }
    .badge { display:inline-block; padding:1.5px 5px; border-radius:3px; font-size:7px; font-weight:700; }
    .b-active      { background:#dcfce7; color:#166534; }
    .b-inactive    { background:#f3f4f6; color:#374151; }
    .b-detained    { background:#fee2e2; color:#991b1b; }
    .b-passed_out  { background:#e0f2fe; color:#0369a1; }
    .b-transferred { background:#fef3c7; color:#92400e; }
    .b-cancelled   { background:#fce7f3; color:#9d174d; }
    .b-default     { background:#f3f4f6; color:#374151; }
    .footer-line { margin-top:7px; border-top:1px solid #e2e8f0; padding-top:5px; width:100%; }
    .footer-line td { font-size:7px; color:#94a3b8; }
    .footer-line td:last-child { text-align:right; }
</style>
</head>
<body>

<div class="header">
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:56px; padding-right:10px;">
                <div class="logo-box">
                    @if(!empty($institute->image) && file_exists(public_path($institute->image)))
                        <img src="{{ public_path($institute->image) }}" alt="">
                    @else
                        {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="report-title">Student List Report</div>
                <div class="report-sub">{{ $institute->name }} &nbsp;|&nbsp; Partner: {{ $partner->name }}</div>
            </td>
            <td class="meta-right">
                <div>Total Records: <strong>{{ $students->count() }}</strong></div>
                <div>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
            </td>
        </tr>
    </table>
</div>

@if(!empty($filterSummary))
<div class="filter-bar">
    <span style="font-size:7.5px; color:#4c1d95; font-weight:700; margin-right:8px;">FILTERS:</span>
    @foreach($filterSummary as $label => $value)
        <span class="f-chip"><strong>{{ $label }}:</strong> {{ $value }}</span>
    @endforeach
</div>
@endif

@php $byStatus = $students->groupBy('status'); $genderGroups = $students->groupBy('gender'); @endphp
<div style="margin-bottom:8px;">
    <span class="s-chip"><span class="lbl">Total</span><br><span class="val" style="color:#0f172a;">{{ $students->count() }}</span></span>
    @foreach($byStatus as $status => $group)
    <span class="s-chip"><span class="lbl">{{ ucfirst(str_replace('_',' ',$status)) }}</span><br><span class="val" style="color:#5b21b6;">{{ $group->count() }}</span></span>
    @endforeach
    @foreach($genderGroups as $g => $grp)
    <span class="s-chip"><span class="lbl">{{ ucfirst($g ?: 'N/A') }}</span><br><span class="val" style="color:#0369a1;">{{ $grp->count() }}</span></span>
    @endforeach
</div>

<table class="data" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th class="num" style="width:18px;">#</th>
            <th style="width:55px;">Student ID</th>
            <th style="width:90px;">Name</th>
            <th style="width:72px;">Father Name</th>
            <th style="width:60px;">Mobile</th>
            <th style="width:30px;">Gender</th>
            <th style="width:80px;">Course</th>
            <th style="width:55px;">Stream</th>
            <th style="width:50px;">Session</th>
            <th style="width:50px;">Adm. Date</th>
            <th style="width:42px;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $i => $s)
        @php $st = $s->status ?? 'active'; $bCls = 'b-'.(in_array($st,['active','inactive','detained','passed_out','transferred','cancelled'])?$st:'default'); @endphp
        <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td><span class="uid">{{ $s->student_uid ?? '—' }}</span></td>
            <td><div class="fw">{{ $s->name }}</div><div class="muted">{{ $s->email ?: '' }}</div></td>
            <td class="muted">{{ $s->father_name ?: '—' }}</td>
            <td class="muted">{{ $s->mobile ?? '—' }}</td>
            <td class="muted" style="text-align:center;">{{ ucfirst(substr($s->gender ?? '—', 0, 1)) }}</td>
            <td style="font-size:7.5px;">{{ $s->stream->course->name ?? '—' }}</td>
            <td class="muted">{{ $s->stream->name ?? '—' }}</td>
            <td class="muted">{{ $s->session?->name ?? '—' }}</td>
            <td class="muted">{{ $s->admission_date?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="badge {{ $bCls }}">{{ ucfirst(str_replace('_',' ',$st)) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center; padding:16px; color:#94a3b8;">No records found</td></tr>
        @endforelse
    </tbody>
</table>

<table class="footer-line" cellpadding="0" cellspacing="0">
    <tr>
        <td>{{ $institute->name }} &mdash; Partner: {{ $partner->name }} &nbsp;|&nbsp; Student List</td>
        <td>Generated on {{ now()->format('d M Y, h:i A') }}</td>
    </tr>
</table>
</body>
</html>
