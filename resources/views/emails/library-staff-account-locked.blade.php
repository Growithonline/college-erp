<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#dc2626;color:#ffffff;padding:18px 24px;">
            <div style="font-size:20px;font-weight:700;">⚠ Security Alert — Account Locked</div>
            <div style="font-size:13px;opacity:.85;margin-top:4px;">Library Staff Portal</div>
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hello,</p>
            <p>
                The following library staff account has been <strong>temporarily locked</strong> due to
                multiple failed OTP attempts:
            </p>

            <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 0;color:#64748b;width:40%;">Staff Name</td>
                    <td style="padding:8px 0;font-weight:600;">{{ $libraryStaff->name }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 0;color:#64748b;">Employee ID</td>
                    <td style="padding:8px 0;font-weight:600;">{{ $libraryStaff->employee_id }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 0;color:#64748b;">Email</td>
                    <td style="padding:8px 0;">{{ $libraryStaff->email }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 0;color:#64748b;">Trigger IP</td>
                    <td style="padding:8px 0;font-family:monospace;">{{ $triggerIp }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#64748b;">Locked Until</td>
                    <td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $lockedUntil }}</td>
                </tr>
            </table>

            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 14px;margin:16px 0;font-size:13px;color:#991b1b;">
                If this was not a legitimate login attempt, the account may be under a brute-force attack.
                You can unlock the account manually from the Institute Panel → Library Management → Staff.
            </div>

            <p style="margin-bottom:0;font-size:12px;color:#94a3b8;">
                This is an automated security notification from the Library Staff Portal.
                Institute: {{ $libraryStaff->institute->name ?? '—' }}
            </p>
        </div>
    </div>
</body>
</html>
