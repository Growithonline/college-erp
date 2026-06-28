<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">

        @php
            $typeColors = [
                'exam'    => '#dc2626',
                'fee'     => '#d97706',
                'holiday' => '#16a34a',
                'urgent'  => '#dc2626',
                'event'   => '#16a34a',
                'general' => '#2563eb',
            ];
            $headerColor = $typeColors[$notice->notice_type] ?? '#2563eb';
            $typeName    = \App\Models\Notice::TYPES[$notice->notice_type] ?? ucfirst($notice->notice_type);
        @endphp

        {{-- Header --}}
        <div style="background:{{ $headerColor }};color:#ffffff;padding:18px 24px;">
            <div style="font-size:11px;opacity:.8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">
                {{ $typeName }} Notice
            </div>
            <div style="font-size:20px;font-weight:700;">{{ $notice->title }}</div>
        </div>

        {{-- Body --}}
        <div style="padding:24px;">
            @if($notice->is_pinned)
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;margin-bottom:16px;font-size:12px;color:#d97706;">
                <strong>📌 Pinned Notice</strong>
            </div>
            @endif

            <p style="margin-top:0;font-size:15px;line-height:1.6;white-space:pre-line;">{{ $notice->body }}</p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin:20px 0;font-size:13px;">
                <div style="margin-bottom:6px;">
                    <strong>Notice Date:</strong> {{ $notice->notice_date->format('d M Y') }}
                </div>
                @if($notice->expires_at)
                <div style="margin-bottom:6px;">
                    <strong>Valid Until:</strong> {{ $notice->expires_at->format('d M Y') }}
                </div>
                @endif
                <div>
                    <strong>Posted By:</strong> {{ $notice->postedByStaff?->name ?? 'Institute Admin' }}
                </div>
            </div>

            <p style="margin-bottom:0;color:#64748b;font-size:13px;">
                This notice has been sent to you by your institute. For more details, please log in to the student portal.<br><br>
                <strong>{{ $notice->institute?->name ?? config('app.name') }}</strong>
            </p>
        </div>
    </div>
</body>
</html>
