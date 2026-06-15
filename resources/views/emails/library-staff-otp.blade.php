<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#0ea5e9;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            Library Staff — Login OTP
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hello {{ $libraryStaff->name }},</p>
            <p>Your one-time password for Library Staff Portal login is:</p>

            <div style="text-align:center;margin:24px 0;">
                <div style="display:inline-block;padding:14px 28px;border-radius:12px;background:#f0f9ff;border:1px solid #bae6fd;color:#0c4a6e;font-size:30px;font-weight:700;letter-spacing:8px;">
                    {{ $otp }}
                </div>
            </div>

            <p>This OTP is valid for <strong>5 minutes</strong> and can only be used once.</p>
            <p style="color:#64748b;font-size:13px;">
                If you did not request this login, your account may be at risk. Please contact your administrator immediately.
            </p>
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0;">
            <p style="margin-bottom:0;font-size:12px;color:#94a3b8;">
                Employee ID: {{ $libraryStaff->employee_id }} &nbsp;|&nbsp; {{ $libraryStaff->institute->name ?? 'Institute' }}
            </p>
        </div>
    </div>
</body>
</html>
