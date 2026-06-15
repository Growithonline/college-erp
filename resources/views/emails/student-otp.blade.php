<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#2563EB;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            🔐 Student Portal OTP
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear <strong>{{ $student->name }}</strong>,</p>
            <p>Your One-Time Password (OTP) for student portal login is:</p>

            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;margin:20px 0;text-align:center;">
                <span style="font-size:36px;font-weight:700;letter-spacing:12px;color:#2563EB;">{{ $otp }}</span>
            </div>

            <p style="color:#64748b;font-size:13px;">This OTP is valid for a limited time. Do not share it with anyone.</p>
            <p style="margin-bottom:0;">Regards,<br><strong>{{ $student->institute?->name ?? config('app.name') }}</strong></p>
        </div>
    </div>
</body>
</html>
