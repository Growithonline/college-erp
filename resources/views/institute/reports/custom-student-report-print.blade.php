<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Custom Student Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px; white-space: nowrap; }
        th { background: #e5e7eb; text-align: left; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
        .muted { color: #6b7280; }
        @page { size: landscape; margin: 10mm; }
    </style>
</head>
<body>
@php $valueResolver = app(\App\Http\Controllers\Institute\Reports\ReportController::class); @endphp
<div class="header">
    <div>
        <h2 style="margin:0;">Custom Student Report</h2>
        <div class="muted">{{ $sessionObj?->name ?? 'All Sessions' }} | {{ number_format($totalStudents) }} students</div>
    </div>
    <div class="muted">Printed: {{ now()->format('d-M-Y h:i A') }}</div>
</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            @foreach($selectedColumns as $key)
                <th>{{ $columns[$key]['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                @foreach($selectedColumns as $key)
                    <td>{{ $valueResolver->customReportValue($student, $key, $columns[$key]) ?: '-' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
