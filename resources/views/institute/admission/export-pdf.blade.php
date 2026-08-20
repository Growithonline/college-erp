<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admissions Export Report</title>
    <style>
        @page { size: A4 landscape; margin: 14mm 12mm 12mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 4mm 2mm 2mm 2mm;
            line-height: 1.3;
            font-weight: 600;
        }

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
        table.t { width:100%; border-collapse:collapse; table-layout:fixed; }

        table.t thead th {
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
        table.t thead th.c { text-align:center; }

        table.t tbody td {
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
        table.t tbody tr:nth-child(even) td { background:#efefef; }
        table.t tbody td.c { text-align:center; }
        .sub { font-size:7px; font-weight:600; color:#000; display:block; margin-top:1px; }

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
    </style>
</head>
<body>

@php
    $logoPath = null;
    if (!empty($institute->image)) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoPath = public_path('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoPath = public_path($institute->image);
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));

    $filterSummary = !empty($appliedFilters)
        ? implode(' | ', array_map(fn($label, $value) => "{$label}: {$value}", array_keys($appliedFilters), $appliedFilters))
        : 'Default listing filters';
@endphp

{{-- ── HEADER ─────────────────────────────────────────────── --}}
<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">
            @if($logoPath)
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                {{ $initials }}
            @endif
        </div>
    </div>
    <div class="hdr-m">
        <div class="inst-name">{{ $institute->name }}</div>
        <div class="inst-sub">Admissions Export Report</div>
    </div>
    <div class="hdr-r">
        <div>Total Records: <strong>{{ number_format($students->count()) }}</strong></div>
        <div>Generated: <strong>{{ $generatedAt }}</strong></div>
        <div>Filtered By: <strong>{{ $filterSummary }}</strong></div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────── --}}
<table class="t" cellspacing="0" cellpadding="0">
    <colgroup>
        <col style="width:2%;">    {{-- # --}}
        <col style="width:4%;">    {{-- Session --}}
        <col style="width:9%;">    {{-- Student ID --}}
        <col style="width:12%;">   {{-- Student Name --}}
        <col style="width:9%;">    {{-- Father Name --}}
        <col style="width:9%;">    {{-- Mother Name --}}
        <col style="width:5%;">    {{-- Roll No --}}
        <col style="width:6%;">    {{-- Enroll No --}}
        <col style="width:5%;">    {{-- UIN No --}}
        <col style="width:10%;">   {{-- Course --}}
        <col style="width:5%;">    {{-- Year/Sem --}}
        <col style="width:8%;">    {{-- Admitted By --}}
        <col style="width:7%;">    {{-- Source --}}
        <col style="width:5%;">    {{-- Adm. Date --}}
        <col style="width:4%;">    {{-- Status --}}
    </colgroup>
    <thead>
        <tr>
            <th class="c">#</th>
            <th>Session</th>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Father Name</th>
            <th>Mother Name</th>
            <th>Roll No</th>
            <th>Enroll No</th>
            <th>UIN No</th>
            <th>Course / Stream</th>
            <th>Year/Sem</th>
            <th>Admitted By</th>
            <th>Source</th>
            <th>Adm. Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $i => $student)
        @php
            $pdfSrc = $student->admission_source ?? 'direct';
            $pdfSourceName = match($pdfSrc) {
                'center'  => ($centersMap[$student->admission_source_id] ?? null)
                                ? 'Ctr: ' . $centersMap[$student->admission_source_id]
                                : 'Center',
                'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                ? 'Prt: ' . $partnersMap[$student->admission_source_id]
                                : 'Partner',
                default   => 'Direct',
            };
            $pdfAdmittedBy = $student->admittedBy?->name
                ?? match($pdfSrc) {
                    'center'                     => $pdfSourceName,
                    'partner', 'channel_partner' => $pdfSourceName,
                    default                      => 'Admin / Direct',
                };
        @endphp
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $student->session?->name ?? '—' }}</td>
            <td style="font-weight:700;">{{ $student->student_uid ?? '—' }}</td>
            <td>
                {{ $student->name }}
                @if($student->mobile)
                    <span class="sub">{{ $student->mobile }}</span>
                @endif
            </td>
            <td>{{ $student->father_name ?: '—' }}</td>
            <td>{{ $student->mother_name ?: '—' }}</td>
            <td>{{ $student->roll_no ?: '—' }}</td>
            <td>{{ $student->enrollment_no ?: '—' }}</td>
            <td>{{ $student->uin_no ?: '—' }}</td>
            <td>
                {{ $student->stream?->course?->name ?? '—' }}
                @if($student->stream?->name)
                    <span class="sub">{{ $student->stream->name }}</span>
                @endif
            </td>
            <td>
                {{ $student->coursePart?->year_label ?? '—' }}@if($student->current_semester) / S{{ $student->current_semester }}@endif
            </td>
            <td>{{ $pdfAdmittedBy }}</td>
            <td>{{ $pdfSourceName }}</td>
            <td style="white-space:nowrap;">{{ $student->admission_date?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-weight:700;">{{ ucfirst($student->status ?? 'pending') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="15" style="text-align:center; padding:12px; font-weight:700; font-size:8px;">
                No admissions matched the selected filters.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ──────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Admissions Report &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ $generatedAt }}</div>
</div>

</body>
</html>
