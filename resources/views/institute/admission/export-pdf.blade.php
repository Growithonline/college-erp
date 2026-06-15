<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admissions Export Report</title>
    <style>
        @page { size: A4 landscape; margin: 16mm 10mm 14mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 12px; }
        .brand { width: 100%; }
        .brand td { vertical-align: top; }
        .logo { width: 56px; height: 56px; border: 1px solid #d1d5db; border-radius: 12px; text-align: center; }
        .logo img { width: 56px; height: 56px; object-fit: cover; }
        .title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
        .subtitle { color: #475569; font-size: 11px; }
        .meta { text-align: right; font-size: 10px; color: #475569; }
        .summary-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; margin-bottom: 12px; }
        .summary-title { font-size: 11px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .chips { width: 100%; }
        .chips td { width: 33.33%; padding: 3px 6px 3px 0; vertical-align: top; }
        .chip { background: #ffffff; border: 1px solid #dbeafe; color: #1d4ed8; border-radius: 7px; padding: 5px 7px; }
        .chip strong { color: #0f172a; }
        .metrics { width: 100%; margin-bottom: 12px; }
        .metric-card { background: linear-gradient(135deg, #ecfeff, #eff6ff); border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 12px; }
        .metric-label { font-size: 10px; color: #475569; text-transform: uppercase; }
        .metric-value { font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 4px; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #dbe2ea; padding: 6px; vertical-align: top; }
        table.report thead th { background: #0f766e; color: #ffffff; font-size: 10px; text-align: left; }
        table.report tbody tr:nth-child(even) { background: #f8fafc; }
        .muted { color: #64748b; }
        .footer { margin-top: 10px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table class="brand" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width:70px;">
                    <div class="logo">
                        @if(!empty($institute->image) && file_exists(public_path($institute->image)))
                            <img src="{{ public_path($institute->image) }}" alt="Institute Logo">
                        @else
                            <div style="line-height:56px; font-size:20px; font-weight:700; color:#0f766e;">
                                {{ strtoupper(substr($institute->short_name ?: $institute->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="title">{{ $institute->name }}</div>
                    <div class="subtitle">Admissions Export Report</div>
                    <div class="muted">
                        {{ collect([$institute->city, $institute->state])->filter()->implode(', ') }}
                    </div>
                </td>
                <td class="meta" style="width:220px;">
                    <div><strong>Generated:</strong> {{ $generatedAt }}</div>
                    <div><strong>Total Records:</strong> {{ number_format($students->count()) }}</div>
                    <div><strong>Report Type:</strong> Filtered Admissions</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <div class="summary-title">Applied Filters</div>
        @if(!empty($appliedFilters))
            <table class="chips" cellpadding="0" cellspacing="0">
                @foreach(array_chunk($appliedFilters, 3, true) as $chunk)
                    <tr>
                        @foreach($chunk as $label => $value)
                            <td><div class="chip"><strong>{{ $label }}:</strong> {{ $value }}</div></td>
                        @endforeach
                        @for($filler = count($chunk); $filler < 3; $filler++)
                            <td></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        @else
            <div class="muted">Default admission listing filters applied.</div>
        @endif
    </div>

    <table class="metrics" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:33.33%; padding-right:8px;">
                <div class="metric-card">
                    <div class="metric-label">Students Found</div>
                    <div class="metric-value">{{ number_format($students->count()) }}</div>
                </div>
            </td>
            <td style="width:33.33%; padding-right:8px;">
                <div class="metric-card">
                    <div class="metric-label">Statuses Covered</div>
                    <div class="metric-value">{{ number_format($students->pluck('status')->filter()->unique()->count()) }}</div>
                </div>
            </td>
            <td style="width:33.33%;">
                <div class="metric-card">
                    <div class="metric-label">Sources Covered</div>
                    <div class="metric-value">{{ number_format($students->pluck('admission_source')->filter()->unique()->count()) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th style="width:28px;">#</th>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach($row as $cell)
                        <td>{{ $cell !== '' ? $cell : '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) + 1 }}" style="text-align:center; padding:18px;">
                        No admissions matched the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        System generated admissions report
    </div>
</body>
</html>
