<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $printTitle }}</title>
<style>
    @page { size: A4 landscape; margin: 14mm 12mm 12mm 12mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #000; background: #fff; font-weight: 600; line-height: 1.3; padding: 5mm 4mm 4mm 4mm; }

    /* ── Print / Close buttons ── */
    .no-print { margin-bottom: 10px; display: flex; gap: 8px; }
    .no-print button { padding: 5px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 700; }
    .btn-print { background: #1e3a5f; color: #fff; }
    .btn-close-btn { background: #e2e8f0; color: #334155; }

    /* ── Header ── */
    .hdr { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:4px; margin-bottom:5px; }
    .hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
    .hdr-l { width:44px; padding-right:7px; }
    .logo-box {
        width:38px; height:38px; border:1.5px solid #000; border-radius:4px;
        text-align:center; line-height:38px; font-size:15px; font-weight:800;
        color:#000; overflow:hidden; background:#e8e8e8;
    }
    .logo-box img { width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; }
    .inst-name  { font-size:16px; font-weight:800; color:#000; letter-spacing:0.2px; }
    .inst-sub   { font-size:9px; color:#000; font-weight:600; margin-top:1px; }
    .hdr-r { text-align:right; font-size:8.5px; color:#000; font-weight:600; width:230px; }
    .hdr-r div  { margin-bottom:2px; }
    .hdr-r strong { font-weight:800; color:#000; }

    /* ── Table ── */
    table.data { width: 100%; border-collapse: collapse; table-layout:fixed; }
    table.data thead th {
        background:#1e3a5f;
        color:#fff;
        font-size:8.5px;
        font-weight:800;
        padding:4px 3px;
        text-align:left;
        white-space:nowrap;
        overflow:hidden;
        border: 0.5px solid #0d2540;
    }
    table.data thead th.c { text-align:center; }
    table.data tbody tr:nth-child(even) td { background:#efefef; }
    table.data tbody td.c { text-align:center; }
    table.data tbody td {
        padding:3px 3px;
        font-size:8.5px;
        font-weight:600;
        color:#000;
        border-bottom:0.5px solid #bbb;
        border-right:0.5px solid #ddd;
        vertical-align:middle;
        white-space:normal;
        word-wrap:break-word;
        overflow-wrap:break-word;
    }

    .fw { font-weight: 800; }
    .sub { font-size:7px; font-weight:600; color:#000; display:block; margin-top:1px; }
    .uid { font-weight: 700; }

    /* ── Badges ── */
    .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: 700; }
    .badge-pending   { background: #fef3c7; color: #92400e; }
    .badge-active    { background: #dcfce7; color: #166534; }
    .badge-cancelled { background: #fee2e2; color: #991b1b; }
    .badge-inactive  { background: #f1f5f9; color: #475569; }
    .badge-other     { background: #e2e8f0; color: #334155; }

    /* ── Footer ── */
    .ftr {
        margin-top:5px;
        border-top:1.5px solid #000;
        padding-top:3px;
        display:table;
        width:100%;
    }
    .ftr-l, .ftr-r { display:table-cell; font-size:8px; color:#000; font-weight:600; }
    .ftr-r { text-align:right; }

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

@php
    $totalCount   = $exportStudents->count();
    $pendingCount = $exportStudents->where('status', 'pending')->count();
    $activeCount  = $exportStudents->where('status', 'active')->count();
@endphp

{{-- ── HEADER ─────────────────────────────────────────────── --}}
<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">
            @if(!empty($institute->image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($institute->image))
                <img src="{{ \Illuminate\Support\Facades\Storage::url($institute->image) }}" alt="Logo">
            @else
                {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
            @endif
        </div>
    </div>
    <div class="hdr-m">
        <div class="inst-name">{{ $institute->name }}</div>
        <div class="inst-sub">Admission Approval Queue &mdash; {{ $filterLabel }}</div>
    </div>
    <div class="hdr-r">
        <div>Total: <strong>{{ $totalCount }}</strong> &nbsp;|&nbsp; Pending: <strong>{{ $pendingCount }}</strong> &nbsp;|&nbsp; Approved: <strong>{{ $activeCount }}</strong></div>
        <div>Generated: <strong>{{ now()->format('d M Y, h:i A') }}</strong></div>
    </div>
</div>

{{-- Data Table --}}
<table class="data" cellspacing="0" cellpadding="0">
    <colgroup>
        <col style="width:2%;">
        <col style="width:9%;">
        <col style="width:12%;">
        <col style="width:9%;">
        <col style="width:9%;">
        <col style="width:7%;">
        <col style="width:10%;">
        <col style="width:7%;">
        <col style="width:6%;">
        <col style="width:8%;">
        <col style="width:6%;">
        <col style="width:5%;">
        <col style="width:8%;">
        <col style="width:6%;">
    </colgroup>
    <thead>
        <tr>
            <th class="c">#</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Father Name</th>
            <th>Mother Name</th>
            <th>Mobile</th>
            <th>Course</th>
            <th>Stream</th>
            <th>Adm. Date</th>
            <th>Admitted By</th>
            <th>Source</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Appr. Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($exportStudents as $i => $student)
            @php
                $pdfSrc = $student->admission_source ?? 'direct';
                if ($pdfSrc === 'center') {
                    $pdfSrcName  = $centers->firstWhere('id', $student->admission_source_id)?->name ?? 'Center';
                    $sourceLabel = 'Center: ' . $pdfSrcName;
                } elseif ($pdfSrc === 'channel_partner') {
                    $pdfSrcName  = $channelPartners->firstWhere('id', $student->admission_source_id)?->name ?? 'Partner';
                    $sourceLabel = 'Partner: ' . $pdfSrcName;
                } else {
                    $pdfSrcName  = null;
                    $sourceLabel = ucfirst($pdfSrc);
                }

                $pdfAdmittedByType = $student->admitted_by_type ?? 'admin';
                if ($pdfAdmittedByType === 'staff') {
                    $admittedBy = 'Staff: ' . ($student->admittedBy?->name ?? 'Staff');
                } elseif ($pdfAdmittedByType === 'center') {
                    $admittedBy = 'Center: ' . ($pdfSrcName ?? ($centers->firstWhere('id', $student->admission_source_id)?->name ?? 'Center'));
                } elseif ($pdfAdmittedByType === 'channel_partner') {
                    $admittedBy = 'Partner: ' . ($pdfSrcName ?? ($channelPartners->firstWhere('id', $student->admission_source_id)?->name ?? 'Partner'));
                } else {
                    $admittedBy = 'Admin';
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
                <td class="c">{{ $i + 1 }}</td>
                <td><span class="uid">{{ $student->student_uid ?? '-' }}</span></td>
                <td><span class="fw">{{ $student->name }}</span></td>
                <td>{{ $student->father_name ?? '-' }}</td>
                <td>{{ $student->mother_name ?? '-' }}</td>
                <td>{{ $student->mobile ?? '-' }}</td>
                <td>{{ $student->stream?->course?->name ?? '-' }}</td>
                <td>{{ $student->stream?->name ?? '-' }}</td>
                <td style="white-space:nowrap;">{{ $student->admission_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $admittedBy }}</td>
                <td>{{ $sourceLabel }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_',' ',$student->status ?? '-')) }}</span></td>
                <td>{{ $student->approved_by_name ?? ($student->approvedByStaff?->name ?? '-') }}</td>
                <td style="white-space:nowrap;">{{ $student->approved_at?->format('d/m/Y') ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="14" style="text-align:center; padding:16px; font-weight:700; font-size:8px;">
                    No records found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ──────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Admission Approval Queue &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Total: {{ $exportStudents->count() }} records</div>
</div>

</body>
</html>
