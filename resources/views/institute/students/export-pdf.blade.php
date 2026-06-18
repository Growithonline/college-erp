<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student List</title>
    <style>
        @page { size: A4 landscape; margin: 8mm 7mm 8mm 7mm; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            color: #000;
            margin: 0; padding: 0;
            line-height: 1.25;
            font-weight: 700;
        }

        /* ── Header ── */
        .hdr { display:table; width:100%; border-bottom:2px solid #000; padding-bottom:4px; margin-bottom:5px; }
        .hdr-l, .hdr-m, .hdr-r { display:table-cell; vertical-align:middle; }
        .hdr-l { width:44px; padding-right:7px; }
        .logo-box {
            width:38px; height:38px; border:1.5px solid #000; border-radius:4px;
            text-align:center; line-height:38px; font-size:15px; font-weight:900;
            color:#000; overflow:hidden; background:#e8e8e8;
        }
        .logo-box img { width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; }
        .inst-name  { font-size:15px; font-weight:900; color:#000; letter-spacing:0.2px; }
        .inst-sub   { font-size:7.5px; color:#000; font-weight:800; margin-top:1px; }
        .hdr-r { text-align:right; font-size:6.5px; color:#000; font-weight:800; white-space:nowrap; }
        .hdr-r div  { margin-bottom:2px; }

        /* ── Table ── */
        table.t { width:100%; border-collapse:collapse; table-layout:fixed; }

        table.t thead th {
            background:#1e3a5f;
            color:#fff;
            font-size:6.5px;
            font-weight:900;
            padding:3px 2px;
            text-align:left;
            white-space:nowrap;
            overflow:hidden;
            border: 0.5px solid #0d2540;
        }
        table.t thead th.c { text-align:center; }

        table.t tbody td {
            padding:2px 2px;
            font-size:6.5px;
            font-weight:800;
            color:#000;
            border-bottom:0.5px solid #bbb;
            border-right:0.5px solid #ddd;
            vertical-align:middle;
            overflow:hidden;
            white-space:nowrap;
        }
        table.t tbody tr:nth-child(even) td { background:#f2f2f2; }
        table.t tbody td.c { text-align:center; }
        table.t tbody td.wrap { white-space:normal; }

        /* ── Footer ── */
        .ftr {
            margin-top:5px;
            border-top:1.5px solid #000;
            padding-top:3px;
            display:table;
            width:100%;
        }
        .ftr-l, .ftr-r { display:table-cell; font-size:6px; color:#000; font-weight:800; }
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
        <div class="inst-sub">Student List &mdash; {{ $sessionObj?->name ?? 'All Sessions' }}</div>
    </div>
    <div class="hdr-r">
        <div>Session: <strong>{{ $sessionObj?->name ?? 'All Sessions' }}</strong></div>
        <div>Total Students: <strong>{{ $students->count() }}</strong></div>
        <div>Generated: <strong>{{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</strong></div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────── --}}
@php
    $sourceLabel = function ($student) use ($centersMap, $partnersMap) {
        return match($student->admission_source) {
            'center'  => ($centersMap[$student->admission_source_id] ?? null)
                            ? 'Ctr: ' . \Illuminate\Support\Str::limit($centersMap[$student->admission_source_id], 14)
                            : 'Center',
            'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                            ? 'Prt: ' . \Illuminate\Support\Str::limit($partnersMap[$student->admission_source_id], 14)
                            : 'Partner',
            default   => 'Direct',
        };
    };
@endphp

<table class="t" cellspacing="0" cellpadding="0">
    <colgroup>
        <col style="width:12px;">   {{-- # --}}
        <col style="width:24px;">   {{-- Session --}}
        <col style="width:50px;">   {{-- Student ID --}}
        <col style="width:68px;">   {{-- Student Name --}}
        <col style="width:52px;">   {{-- Father Name --}}
        <col style="width:52px;">   {{-- Mother Name --}}
        <col style="width:28px;">   {{-- Roll No --}}
        <col style="width:34px;">   {{-- Enroll No --}}
        <col style="width:28px;">   {{-- UIN No --}}
        <col style="width:54px;">   {{-- Course --}}
        <col style="width:30px;">   {{-- Year/Sem --}}
        <col style="width:44px;">   {{-- Admitted By --}}
        <col style="width:38px;">   {{-- Source --}}
        <col style="width:28px;">   {{-- Adm. Date --}}
        <col style="width:22px;">   {{-- Status --}}
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
        <tr>
            <td class="c">{{ $i + 1 }}</td>
            <td>{{ $student->session?->name ?? '—' }}</td>
            <td style="font-weight:900;">{{ $student->student_uid ?? '—' }}</td>
            <td class="wrap">
                {{ \Illuminate\Support\Str::limit($student->name, 24) }}
                @if($student->mobile)
                    <br><span style="font-size:5.5px; font-weight:700; color:#333;">{{ $student->mobile }}</span>
                @endif
            </td>
            <td>{{ \Illuminate\Support\Str::limit($student->father_name ?: '—', 18) }}</td>
            <td>{{ \Illuminate\Support\Str::limit($student->mother_name ?: '—', 18) }}</td>
            <td>{{ $student->roll_no ?: '—' }}</td>
            <td>{{ $student->enrollment_no ?: '—' }}</td>
            <td>{{ $student->uin_no ?: '—' }}</td>
            <td class="wrap">
                {{ \Illuminate\Support\Str::limit($student->stream?->course?->name ?? '—', 20) }}
                @if($student->stream?->name)
                    <br><span style="font-size:5.5px; font-weight:700; color:#333;">{{ \Illuminate\Support\Str::limit($student->stream->name, 18) }}</span>
                @endif
            </td>
            <td>
                {{ $student->coursePart?->year_label ?? '—' }}@if($student->current_semester) / S{{ $student->current_semester }}@endif
            </td>
            <td>{{ \Illuminate\Support\Str::limit($student->admittedBy?->name ?? '—', 18) }}</td>
            <td>{{ $sourceLabel($student) }}</td>
            <td style="white-space:nowrap;">{{ $student->admission_date?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-weight:900;">{{ ucfirst($student->status ?? 'pending') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="15" style="text-align:center; padding:12px; font-weight:900; font-size:8px;">
                No students found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── FOOTER ──────────────────────────────────────────────── --}}
<div class="ftr">
    <div class="ftr-l">{{ $institute->name }} &mdash; Student List &mdash; Confidential</div>
    <div class="ftr-r">Generated: {{ now()->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
</div>

<script>window.onload = function () { window.print(); };</script>
</body>
</html>
