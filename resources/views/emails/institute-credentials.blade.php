<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Institute Login Credentials</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8;padding:40px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                    {{-- ── Header ── --}}
                    <tr>
                        <td style="background:linear-gradient(135deg,#1D9E75 0%,#15785a 100%);padding:32px 40px;text-align:center;">
                            <img src="{{ $logoUrl }}" alt="Gaurangi Technologies" style="height:60px;max-width:200px;object-fit:contain;margin-bottom:16px;display:block;margin-left:auto;margin-right:auto;">
                            <p style="margin:0;color:#d1fae5;font-size:13px;letter-spacing:1px;text-transform:uppercase;font-weight:600;">Powered by Gaurangi Technologies</p>
                        </td>
                    </tr>

                    {{-- ── Welcome Banner ── --}}
                    <tr>
                        <td style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:20px 40px;text-align:center;">
                            <h1 style="margin:0;font-size:22px;font-weight:700;color:#15803d;">Welcome to College ERP! 🎓</h1>
                            <p style="margin:8px 0 0;font-size:14px;color:#166534;">Your institute account has been successfully created.</p>
                        </td>
                    </tr>

                    {{-- ── Body ── --}}
                    <tr>
                        <td style="padding:36px 40px;">

                            <p style="margin:0 0 20px;font-size:15px;color:#374151;">Hello <strong>{{ $ownerName }}</strong>,</p>
                            <p style="margin:0 0 28px;font-size:15px;color:#374151;line-height:1.7;">
                                We're excited to have <strong>{{ $instituteName }}</strong> onboard. Below are your login credentials to access the College ERP portal.
                            </p>

                            {{-- Credentials Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:28px;">
                                <tr>
                                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Institute ID</p>
                                        <p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#1D9E75;letter-spacing:1px;">{{ $instituteUid }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Login Email</p>
                                        <p style="margin:4px 0 0;font-size:15px;color:#1e293b;font-weight:500;">{{ $email }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Temporary Password</p>
                                        <p style="margin:4px 0 0;font-size:18px;color:#1e293b;font-weight:700;letter-spacing:2px;font-family:'Courier New',monospace;">{{ $password }}</p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Login Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $loginUrl }}" style="display:inline-block;background:linear-gradient(135deg,#1D9E75,#15785a);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:8px;font-size:15px;font-weight:700;letter-spacing:0.5px;">
                                            Login to Your Dashboard →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Security Notice --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:28px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
                                            <strong>⚠️ Security Notice:</strong> This is a temporary password. For the security of your institute data, please change your password immediately after your first login.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Steps --}}
                            <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#374151;">Getting Started:</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="display:inline-block;background:#1D9E75;color:#fff;border-radius:50%;width:22px;height:22px;text-align:center;line-height:22px;font-size:12px;font-weight:700;margin-right:10px;vertical-align:middle;">1</span>
                                        <span style="font-size:14px;color:#374151;vertical-align:middle;">Visit the login URL and enter your credentials.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="display:inline-block;background:#1D9E75;color:#fff;border-radius:50%;width:22px;height:22px;text-align:center;line-height:22px;font-size:12px;font-weight:700;margin-right:10px;vertical-align:middle;">2</span>
                                        <span style="font-size:14px;color:#374151;vertical-align:middle;">Change your password from the Profile section.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="display:inline-block;background:#1D9E75;color:#fff;border-radius:50%;width:22px;height:22px;text-align:center;line-height:22px;font-size:12px;font-weight:700;margin-right:10px;vertical-align:middle;">3</span>
                                        <span style="font-size:14px;color:#374151;vertical-align:middle;">Set up your institute profile and start managing students.</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;color:#64748b;line-height:1.7;">
                                If you have any questions or need assistance, feel free to reach out to our support team. We're here to help you get the most out of College ERP.
                            </p>

                        </td>
                    </tr>

                    {{-- ── Footer ── --}}
                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 40px;text-align:center;">
                            <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#1D9E75;">Gaurangi Technologies</p>
                            <p style="margin:0 0 12px;font-size:12px;color:#94a3b8;">College ERP — Simplifying Education Management</p>
                            <p style="margin:0;font-size:11px;color:#cbd5e1;">
                                This is an automated email. Please do not reply to this message.<br>
                                &copy; {{ date('Y') }} Gaurangi Technologies. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
