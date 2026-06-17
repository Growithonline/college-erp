<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Staff Portal Login Credentials</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                {{-- ── Institute Header ── --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e3a5f 0%,#1D9E75 100%);padding:32px 40px;text-align:center;">
                        @if($staffMember->institute?->image)
                            <img src="{{ asset('storage/' . $staffMember->institute->image) }}"
                                 alt="{{ $staffMember->institute->name }}"
                                 style="height:64px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto;background:#fff;border-radius:8px;padding:6px;">
                        @else
                            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:12px;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                                <span style="font-size:24px;font-weight:700;color:#fff;">{{ strtoupper(substr($staffMember->institute->name ?? 'I', 0, 2)) }}</span>
                            </div>
                        @endif
                        <h1 style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.3px;">{{ $staffMember->institute?->name ?? config('app.name') }}</h1>
                        <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.75);">Staff Portal — Login Credentials</p>
                    </td>
                </tr>

                {{-- ── Welcome Banner ── --}}
                <tr>
                    <td style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;padding:18px 40px;text-align:center;">
                        <p style="margin:0;font-size:16px;font-weight:700;color:#15803d;">Welcome to the Staff Portal!</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#166534;">Your account has been created. Use the details below to log in.</p>
                    </td>
                </tr>

                {{-- ── Body ── --}}
                <tr>
                    <td style="padding:32px 40px;">

                        <p style="margin:0 0 20px;font-size:15px;color:#374151;">
                            Hello <strong>{{ $staffMember->name }}</strong>,
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.7;">
                            Your staff account at <strong>{{ $staffMember->institute?->name }}</strong> has been created. Below are your login credentials to access the staff portal.
                        </p>

                        {{-- Credentials Card --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;overflow:hidden;">
                            <tr>
                                <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Portal URL</p>
                                    <p style="margin:4px 0 0;font-size:14px;color:#2563eb;font-weight:500;">
                                        <a href="{{ route('staff.login') }}" style="color:#2563eb;text-decoration:none;">{{ route('staff.login') }}</a>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Login Email</p>
                                    <p style="margin:4px 0 0;font-size:15px;color:#1e293b;font-weight:600;">{{ $staffMember->email }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 24px;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Temporary Password</p>
                                    <p style="margin:4px 0 0;font-size:20px;font-weight:700;letter-spacing:3px;font-family:'Courier New',monospace;color:#1e293b;">{{ $plainPassword }}</p>
                                </td>
                            </tr>
                        </table>

                        {{-- Login Button --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ route('staff.login') }}"
                                       style="display:inline-block;background:linear-gradient(135deg,#1D9E75,#15785a);color:#ffffff;text-decoration:none;padding:13px 36px;border-radius:8px;font-size:15px;font-weight:700;">
                                        Login to Staff Portal →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Security Notice --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td style="padding:14px 20px;">
                                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
                                        <strong>⚠️ Security Notice:</strong> This is a temporary password. After your first login, an OTP will be sent to your email. Please change your password from the Profile section.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;font-size:14px;color:#64748b;line-height:1.7;">
                            If you did not expect this email or have any questions, please contact your institute administrator.
                        </p>

                    </td>
                </tr>

                {{-- ── Footer ── --}}
                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
                        <p style="margin:0 0 4px;font-size:12px;color:#94a3b8;">Powered by <strong style="color:#1D9E75;">College ERP</strong></p>
                        <p style="margin:0;font-size:11px;color:#cbd5e1;">
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
