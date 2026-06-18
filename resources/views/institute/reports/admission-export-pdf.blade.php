<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Report</title>
    <style>
        @page { size: A4 landscape; margin: 14mm 12mm 12mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            color: #000;
            margin: 0;
            padding: 4mm 2mm 2mm 2mm;
            line-height: 1.25;
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
        .inst-name  { font-size:15px; font-weight:800; color:#000; letter-spacing:0.2px; }
        .inst-sub   { font-size:7.5px; color:#000; font-weight:600; margin-top:1px; }
        .hdr-r { text-align:right; font-size:6.5px; color:#000; font-weight:600; white-space:nowrap; }
        .hdr-r div  { margin-bottom:2px; }
        .hdr-r strong { font-weight:800; color:#000; }

        /* ── Table ── */
        table.t { width:100%; border-collapse:collapse; table-layout:fixed; }

        table.t thead th {
            background:#1e3a5f;
            color:#fff;
            font-size:6.5px;
            font-weight:800;
            padding:3px 2px;
            text-align:left;
            white-space:nowrap;
            overflow:hidden;
            border: 0.5px solid #0d2540;
        }
        table.t thead th.c { text-align:center; }

        table.t tbody td {
            padding:2px 3px;
            font-size:6.5px;
            font-weight:600;
            color:#000;
            border-bottom:0.5px solid #bbb;
            border-right:0.5px solid #ddd;
            vertical-align:middle;
            overflow:hidden;
            white-space:nowrap;
        }
        table.t tbody tr:nth-child(even) td { background:#efefef; }
        table.t tbody td.c { text-align:center; }
        table.t tbody td.wrap { white-space:normal; }
        .sub { font-size:5.5px; font-weight:600; color:#000; display:block; margin-top:1px; }

        /* ── Footer ── */
        .ftr {
            margin-top:5px;
            border-top:1.5px solid #000;
            padding-top:3px;
            display:table;
            width:100%;
        }
        .ftr-l, .ftr-r { display:table-cell; font-size:6px; color:#000; font-weight:600; }
        .ftr-r { text-align:right; }

        @media print {
            body { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>

@php
    $logoUrl = null;
    if (!empty($institute->image)) {
        if (file_exists(public_path('storage/' . $institute->image))) {
            $logoUrl = asset('storage/' . $institute->image);
        } elseif (file_exists(public_path($institute->image))) {
            $logoUrl = asset($institute->image);
        }
    }
    $initials = strtoupper(substr($institute->short_name ?: $institute->name, 0, 2));
@endphp

{{-- ── HEADER ─────────────────────────────────────────────── --}}
<div class="hdr">
    <div class="hdr-l">
        <div class="logo-box">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo">
            @else
                {{ $initials }}
            @endif
        </div>
    </div>
    <div class="hdr-m">
        <div class="inst-name">{{ $institute->name }}</div>
        <div class="inst-sub">Admission Report &mdash; {{ $sessionObj?->name ?? 'All Sessions' }}</div>
    </div>
    <div class="hdr-r">
        <div>Session: <strong>{{ $sessionObj?->name ?? 'All Sessions' }}</strong></div>
        <div>Total Students: <strong>{{ $allStudents->count() }}</strong></div>
        <div>Generated: <strong>{{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong></div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────── --}}
<table class="t" cellspacing="0" cellpadding="0">
    <colgroup>
        <col style="width:10px;">   {{-- # --}}
        <col style="width:22px;">   {{-- Session --}}
        <col style="width:50px;">   {{-- Student ID --}}
        <col style="width:65px;">   {{-- Student Name --}}
        <col style="width:48px;">   {{-- Father Name --}}
        <col style="width:48px;">   {{-- Mother Name --}}
        <col style="width:28px;">   {{-- Roll No --}}
        <col style="width:32px;">   {{-- Enroll No --}}
        <col style="width:28px;">   {{-- UIN No --}}
        <col style="width:52px;">   {{-- Course --}}
        <col style="width:28px;">   {{-- Year/Sem --}}
        <col style="width:16px;">   {{-- Gender --}}
        <col style="width:14px;">   {{-- Cat --}}
        <col style="width:36px;">   {{-- Source --}}
        <col style="width:46px;">   {{-- Admitted By --}}
        <col style="width:26px;">   {{-- Adm. Date --}}
        <col style="width:18px;">   {{-- Status --}}
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
            <th>Gender</th>
            <th>Cat.</th>
            <th>Source</th>
            <th>Admitted By</th>
            <th>Adm. Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($allStudents as $i => $student)
        @php
            $pdfSrc = $student->admission_source ?? 'direct';
            $pdfSourceName = match($pdfSrc) {
                'center'                     => ($centersMap[$student->admission_source_id]  ?? null)
                                                ? 'Ctr: ' . \Illuminate\Support\Str::limit($centersMap[$student->admission_source_id], 16)
                                                : 'Center',
                'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                                ? 'Prt: ' . \Illuminate\Support\Str::limit($partnersMap[$student->admission_source_id], 16)
                                                : 'Partner',
                default                      => 'Direct',
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
            <td class="wrap">
                {{ \Illuminate\Support\Str::limit($student->name, 22) }}
                @if($student->mobile)
                    <span class="sub">{{ $student->mobile }}</span>
                @endif
            </td>
            <td>{{ \Illuminate\Support\Str::limit($student->father_name ?: '—', 16) }}</td>
            <td>{{ \Illuminate\Support\Str::limit($student->mother_name ?: '—', 16) }}</td>
            <td>{{ $student->roll_no ?: '—' }}</td>
            <td>{{ $student->enrollment_no ?: '—' }}</td>
            <td>{{ $student->uin_no ?: '—' }}</td>
            <td class="wrap">
                {{ \Illuminate\Support\Str::limit($student->stream?->course?->name ?? '—', 18) }}
                @if($student->stream?->name)
                    <span class="sub">{{ \Illuminate\Support\Str::limit($student->stream->name, 16) }}</span>
                @endif
            </td>
            <td>
                {{ $student->resolved_year_label ?? '—' }}@if($student->current_semester)<span class="sub">S{{ $student->current_semester }}</span>@endif
            </td>
            <td>{{ ucfirst(substr($student->gender ?? '—', 0, 1)) }}</td>
            <td style="text-align:center;">{{ strtoupper($student->category ?? '—') }}</td>
            <td class="wrap">{{ $pdfSourceName }}</td>
            <td class="wrap">{{ \Illuminate\Support\Str::limit($pdfAdmittedBy, 22) }}</td>
            <td style="white-space:nowrap;">{{ $student->admission_date?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-weight:700;">{{ ucfirst($student->status ?? 'pending') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="17" style="text-align:center; padding:12px; font-weight:700; font-size:8px;">
                No students found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ──────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Admission Report &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<script>window.onload = function () { window.print(); };</script>
</body>
</html>
