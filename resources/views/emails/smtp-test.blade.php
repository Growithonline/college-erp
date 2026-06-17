<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>SMTP Test</title></head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 16px;">
        <tr><td align="center">
            <table width="100%" style="max-width:520px;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                <tr>
                    <td style="background:linear-gradient(135deg,#1D9E75,#15785a);padding:28px 32px;text-align:center;">
                        <div style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px;">✓</div>
                        <h2 style="margin:0;color:#fff;font-size:20px;font-weight:700;">SMTP Test Successful!</h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:15px;color:#374151;line-height:1.6;">
                            Your SMTP configuration is working correctly. Emails from your institute will now be sent through your own mail server.
                        </p>
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
                            <p style="margin:0;font-size:13px;color:#166534;">
                                <strong>From:</strong> {{ $fromName }} &lt;{{ $fromEmail }}&gt;
                            </p>
                        </div>
                        <p style="margin:0;font-size:13px;color:#94a3b8;">This is an automated test email sent from College ERP — Gaurangi Technologies.</p>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 32px;text-align:center;">
                        <p style="margin:0;font-size:11px;color:#cbd5e1;">&copy; {{ date('Y') }} Gaurangi Technologies. All rights reserved.</p>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
