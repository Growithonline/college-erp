<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Subject Wise Student</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10px; color: #111; background: #fff; }

    .page-header { display: flex; justify-content: space-between; align-items: flex-start;
                   border-bottom: 2px solid #1e293b; padding-bottom: 8px; margin-bottom: 10px; }
    .page-header .inst-name { font-size: 16px; font-weight: 700; color: #1e293b; }
    .page-header .report-title { font-size: 12px; font-weight: 600; color: #1d4ed8; margin-top: 2px; }
    .page-header .meta { text-align: right; font-size: 9px; color: #000; font-weight: 600; line-height: 1.6; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    thead th { background: #1e293b; color: #fff; padding: 4px 5px; font-size: 8.5px;
               font-weight: 700; text-transform: uppercase; border: 1px solid #334155; text-align: left; }
    thead th.c { text-align: center; }
    tbody td { padding: 3px 5px; border: 1px solid #e5e7eb; font-size: 9px; vertical-align: middle; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    tbody td.c { text-align: center; }
    tfoot td { padding: 4px 5px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 9.5px;
               background: #f1f5f9; }

    .t-muted { color: #000; }

    .footer { margin-top: 8px; display: flex; justify-content: space-between;
              font-size: 8px; color: #000; font-weight: 600; border-top: 1px solid #e2e8f0; padding-top: 4px; }

    @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>

<div class="page-header">
    <div>
        <div class="inst-name">{{ $instituteName }}</div>
        <div class="report-title">Subject Wise Student</div>
    </div>
    <div class="meta">
        <div><strong>Session:</strong> {{ $sessionObj?->name ?? '—' }}</div>
        <div><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        <div><strong>Total Students:</strong> {{ $allStudents->count() }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:20px;">#</th>
            <th style="width:60px;">Std ID</th>
            <th style="width:45px;">Form No</th>
            <th style="width:45px;">UIN No</th>
            <th style="width:40px;">Roll No</th>
            <th style="width:50px;">Enroll No</th>
            <th style="width:80px;">Name</th>
            <th style="width:65px;">Father Name</th>
            <th style="width:65px;">Mother Name</th>
            <th style="width:55px;">Course Type</th>
            <th style="width:65px;">Course</th>
            <th style="width:30px;" class="c">Sem</th>
            <th>Major Subjects</th>
            <th>Minor Subjects</th>
            <th>Other Subjects</th>
        </tr>
    </thead>
    <tbody>
        @foreach($allStudents as $i => $student)
        <tr>
            <td class="t-muted">{{ $i + 1 }}</td>
            <td class="t-muted">{{ $student->student_uid ?: '—' }}</td>
            <td class="t-muted">{{ $student->institute_form_no ?: '—' }}</td>
            <td class="t-muted">{{ $student->uin_no ?: '—' }}</td>
            <td class="t-muted">{{ $student->roll_no ?: '—' }}</td>
            <td class="t-muted">{{ $student->enrollment_no ?: '—' }}</td>
            <td style="font-weight:600;">{{ $student->name }}</td>
            <td>{{ $student->father_name ?: '—' }}</td>
            <td>{{ $student->mother_name ?: '—' }}</td>
            <td class="t-muted">{{ $student->stream?->course?->type?->name ?? '—' }}</td>
            <td class="t-muted">{{ $student->stream?->course?->name ?? '—' }}</td>
            <td class="c t-muted">{{ $student->current_semester ? 'S'.$student->current_semester : '—' }}</td>
            <td>{{ $student->major_subjects ?: '—' }}</td>
            <td>{{ $student->minor_subjects ?: '—' }}</td>
            <td>{{ $student->other_subjects ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <span>Subject Wise Student — {{ $instituteName }} — Session: {{ $sessionObj?->name ?? '—' }}</span>
    <span>Generated: {{ now()->format('d M Y, h:i A') }}</span>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
