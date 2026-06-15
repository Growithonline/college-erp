<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#185FA5;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            Center Portal Credentials
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hello {{ $center->name }},</p>
            <p>Your center account for <strong>{{ $center->institute?->name ?? config('app.name') }}</strong> has been created.</p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin:20px 0;">
                <p style="margin:0 0 8px;"><strong>Login URL:</strong> {{ route('center.login') }}</p>
                <p style="margin:0 0 8px;"><strong>Email:</strong> {{ $center->email }}</p>
                <p style="margin:0;"><strong>Password:</strong> {{ $plainPassword }}</p>
            </div>

            <p>Login ke baad email OTP verification complete karke dashboard access milega.</p>
            <p style="margin-bottom:0;">Regards,<br>{{ $center->institute?->name ?? config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
