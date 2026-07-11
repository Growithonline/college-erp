<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Status — {{ $student->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            padding: 24px 16px;
            display: flex;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 380px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .header {
            background: #1d4ed8;
            color: #ffffff;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header .institute-name {
            font-size: 14px;
            font-weight: 600;
        }
        .status-badge {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .status-badge.active { background: #16a34a; color: #ffffff; }
        .status-badge.inactive { background: #dc2626; color: #ffffff; }
        .body { padding: 24px 20px; text-align: center; }
        .photo {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            margin: 0 auto 16px;
            overflow: hidden;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #f1f5f9;
        }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .photo .placeholder { font-size: 12px; color: #94a3b8; }
        .student-name { font-size: 18px; font-weight: 700; margin-bottom: 2px; }
        .student-roll { font-size: 13px; color: #64748b; margin-bottom: 20px; }
        .details {
            text-align: left;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .detail-row .label { color: #64748b; }
        .detail-row .value { font-weight: 600; text-align: right; }
        .notice {
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }
        .footer {
            padding: 12px 20px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <span class="institute-name">{{ $institute->name }}</span>
            <span class="status-badge {{ $allocation ? 'active' : 'inactive' }}">
                {{ $allocation ? 'Active' : 'Not Allocated' }}
            </span>
        </div>
        <div class="body">
            <div class="photo">
                @if($student->photo)
                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Student photo">
                @else
                    <span class="placeholder">No Photo</span>
                @endif
            </div>
            <div class="student-name">{{ $student->name }}</div>
            <div class="student-roll">{{ $student->roll_no ?? $student->student_uid }}</div>

            @if($allocation)
                <div class="details">
                    <div class="detail-row">
                        <span class="label">Route</span>
                        <span class="value">{{ $allocation->route?->name ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Stop</span>
                        <span class="value">{{ $allocation->stop?->stop_name ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Vehicle</span>
                        <span class="value">{{ $allocation->vehicle?->vehicle_no ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Driver</span>
                        <span class="value">{{ $allocation->driver?->name ?? '—' }}</span>
                    </div>
                </div>
            @else
                <div class="notice">
                    This student does not currently have an active transport allocation.
                </div>
            @endif
        </div>
        <div class="footer">
            Verified {{ now()->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>
