<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#185FA5;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            Center Login OTP
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Hello {{ $center->name }},</p>
            <p>Your one-time password for center login is:</p>

            <div style="text-align:center;margin:24px 0;">
                <div style="display:inline-block;padding:14px 22px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;font-size:28px;font-weight:700;letter-spacing:6px;">
                    {{ $otp }}
                </div>
            </div>

            <p>This OTP is valid for 5 minutes.</p>
            <p style="margin-bottom:0;">If you did not request this login, please ignore this email.</p>
        </div>
    </div>
</body>
</html>
