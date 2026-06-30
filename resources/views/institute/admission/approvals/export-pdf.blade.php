<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $printTitle }}</title>
<style>
    @page { size: A4 landscape; margin: 8mm 10mm 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8.5px; color: #1e293b; background: #fff; }

    /* ── Print / Close buttons ── */
    .no-print { margin-bottom: 10px; display: flex; gap: 8px; }
    .no-print button { padding: 5px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-print { background: #0f766e; color: #fff; }
    .btn-close-btn { background: #e2e8f0; color: #334155; }

    /* ── Institute Header ── */
    .inst-header { border-bottom: 2.5px solid #0f766e; padding-bottom: 8px; margin-bottom: 8px; }
    .inst-header table { width: 100%; border-collapse: collapse; }
    .inst-header td { vertical-align: middle; padding: 0; }

    .logo-box {
        width: 50px; height: 50px; border: 1px solid #d1d5db; border-radius: 6px;
        text-align: center; line-height: 50px; font-size: 16px; font-weight: 700;
        color: #0f766e; overflow: hidden; background: #f0fdf4;
    }
    .logo-box img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }

    .inst-name  { font-size: 15px; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .inst-addr  { font-size: 8px; color: #64748b; margin-top: 2px; line-height: 1.5; }
    .report-tag { font-size: 10px; font-weight: 700; color: #0f766e; margin-top: 3px; }

    .meta-right { text-align: right; font-size: 8px; color: #475569; line-height: 1.8; }
    .meta-right strong { color: #1e293b; }

    /* ── Summary boxes ── */
    .summary-table { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 8px; }
    .summary-table td { width: 33%; }
    .summary-box {
        border: 1px solid #e2e8f0; border-radius: 5px; padding: 5px 8px;
        text-align: center;
    }
    .summary-box .s-label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
    .summary-box .s-value { font-size: 14px; font-weight: 700; margin-top: 1px; }
    .box-total   { border-left: 3px solid #2563eb; }
    .box-total   .s-value { color: #2563eb; }
    .box-pending { border-left: 3px solid #f59e0b; }
    .box-pending .s-value { color: #d97706; }
    .box-active  { border-left: 3px solid #16a34a; }
    .box-active  .s-value { color: #16a34a; }

    /* ── Data Table ── */
    table.data { width: 100%; border-collapse: collapse; }
    table.data thead tr { background: #0f766e; color: #fff; }
    table.data thead th {
        padding: 5px 4px; font-size: 7.5px; font-weight: 700;
        text-align: left; white-space: nowrap; letter-spacing: 0.3px;
    }
    table.data tbody tr:nth-child(even) { background: #f8fafc; }
    table.data tbody tr { border-bottom: 1px solid #e2e8f0; }
    table.data tbody td { padding: 4px 4px; font-size: 8px; vertical-align: top; }
    table.data tbody tr:last-child td { border-bottom: none; }

    .fw { font-weight: 700; }
    .muted { font-size: 7px; color: #64748b; margin-top: 1px; }
    .uid { font-size: 7.5px; font-weight: 700; color: #1d4ed8; }

    /* ── Badges ── */
    .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: 700; }
    .badge-pending   { background: #fef3c7; color: #92400e; }
    .badge-active    { background: #dcfce7; color: #166534; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .badge-inactive  { background: #f1f5f9; color: #475569; }
    .badge-other     { background: #e2e8f0; color: #334155; }

    /* ── Footer ── */
    .report-footer {
        margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 5px;
        font-size: 7.5px; color: #94a3b8;
    }
    .report-footer table { width: 100%; }
    .report-footer td:last-child { text-align: right; }

    @media print {
        .no-print { display: none !important; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        thead { display: table-header-group; }
    }
</style>
</head>
<body>

{{-- Print / Close (screen only) --}}
<div class="no-print">
    <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
    <button class="btn-close-btn" onclick="window.close()">Close</button>
</div>

{{-- Institute Header --}}
<div class="inst-header">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:58px; padding-right:10px;">
                <div class="logo-box">
                    @if(!empty($institute->image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($institute->image))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($institute->image) }}" alt="Logo">
                    @else
                        {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                    @endif
                </div>
            </td>
            <td>
                <div class="inst-name">{{ $institute->name }}</div>
                @if(!empty($institute->address) || !empty($institute->city))
                <div class="inst-addr">
                    {{ implode(', ', array_filter([$institute->address ?? null, $institute->city ?? null, $institute->state ?? null])) }}
                    @if(!empty($institute->mobile)) &nbsp;|&nbsp; Mobile: {{ $institute->mobile }} @endif
                    @if(!empty($institute->email)) &nbsp;|&nbsp; {{ $institute->email }} @endif
                </div>
                @endif
                <div class="report-tag">Admission Approval Queue — Detailed Report</div>
            </td>
            <td class="meta-right" style="width:160px;">
                <div>Session: <strong>{{ $filterLabel }}</strong></div>
                <div>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
                <div>Total Records: <strong>{{ $exportStudents->count() }}</strong></div>
                @php
                    $pCount = $exportStudents->where('status', 'pending')->count();
                    $aCount = $exportStudents->where('status', 'active')->count();
                @endphp
                <div>Pending: <strong>{{ $pCount }}</strong> &nbsp;|&nbsp; Approved: <strong>{{ $aCount }}</strong></div>
            </td>
        </tr>
    </table>
</div>

{{-- Summary --}}
@php
    $totalCount   = $exportStudents->count();
    $pendingCount = $exportStudents->where('status', 'pending')->count();
    $activeCount  = $exportStudents->where('status', 'active')->count();
@endphp
<table class="summary-table" cellpadding="0" cellspacing="0">
    <tr>
        <td><div class="summary-box box-total"><div class="s-label">Total Admissions</div><div class="s-value">{{ $totalCount }}</div></div></td>
        <td><div class="summary-box box-pending"><div class="s-label">Pending Approval</div><div class="s-value">{{ $pendingCount }}</div></div></td>
        <td><div class="summary-box box-active"><div class="s-label">Approved / Active</div><div class="s-value">{{ $activeCount }}</div></div></td>
    </tr>
</table>

{{-- Data Table --}}
<table class="data">
    <thead>
        <tr>
            <th style="width:18px;">#</th>
            <th style="width:75px;">Student ID</th>
            <th style="width:110px;">Name</th>
            <th style="width:70px;">Father Name</th>
            <th style="width:70px;">Mother Name</th>
            <th style="width:60px;">Mobile</th>
            <th style="width:80px;">Course</th>
            <th style="width:55px;">Stream</th>
            <th style="width:50px;">Adm. Date</th>
            <th style="width:55px;">Admitted By</th>
            <th style="width:50px;">Source</th>
            <th style="width:38px;">Status</th>
            <th style="width:60px;">Approved By</th>
            <th style="width:45px;">Appr. Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($exportStudents as $i => $student)
            @php
                $admittedBy = $student->admittedBy?->name
                    ? 'Staff: ' . $student->admittedBy->name
                    : 'Admin/Direct';

                if ($student->admission_source === 'center') {
                    $sourceLabel = 'Center: ' . (\App\Models\Center::find($student->admission_source_id)?->name ?? 'Center');
                } elseif ($student->admission_source === 'channel_partner') {
                    $sourceLabel = 'Partner: ' . (\App\Models\ChannelPartner::find($student->admission_source_id)?->name ?? 'Partner');
                } else {
                    $sourceLabel = ucfirst($student->admission_source ?? 'direct');
                }
                $badgeClass = match($student->status) {
                    'pending'   => 'badge-pending',
                    'active'    => 'badge-active',
                    'cancelled' => 'badge-cancelled',
                    'inactive'  => 'badge-inactive',
                    default     => 'badge-other',
                };
            @endphp
            <tr>
                <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                <td><span class="uid">{{ $student->student_uid ?? '-' }}</span></td>
                <td><span class="fw">{{ $student->name }}</span></td>
                <td>{{ $student->father_name ?? '-' }}</td>
                <td>{{ $student->mother_name ?? '-' }}</td>
                <td>{{ $student->mobile ?? '-' }}</td>
                <td>{{ $student->stream?->course?->name ?? '-' }}</td>
                <td>{{ $student->stream?->name ?? '-' }}</td>
                <td>{{ $student->admission_date?->format('d M Y') ?? '-' }}</td>
                <td>{{ $admittedBy }}</td>
                <td>{{ $sourceLabel }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_',' ',$student->status ?? '-')) }}</span></td>
                <td>{{ $student->approved_by_name ?? ($student->approvedByStaff?->name ?? '-') }}</td>
                <td>{{ $student->approved_at?->format('d M Y') ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="14" style="text-align:center; padding:16px; color:#94a3b8; font-style:italic;">
                    No records found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Footer --}}
<div class="report-footer">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td>{{ $institute->name }} &mdash; Admission Approval Queue</td>
            <td>Generated: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Total: {{ $exportStudents->count() }} records</td>
        </tr>
    </table>
</div>

</body>
</html>
