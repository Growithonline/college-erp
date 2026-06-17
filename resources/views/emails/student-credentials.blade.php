<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Confirmed — Student Portal Login</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Arial,sans-serif;color:#1e293b;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                {{-- ── Institute Header ── --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2563EB 100%);padding:32px 40px;text-align:center;">
                        @if($student->institute?->image)
                            <img src="{{ asset('storage/' . $student->institute->image) }}"
                                 alt="{{ $student->institute->name }}"
                                 style="height:64px;max-width:200px;object-fit:contain;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto;background:#fff;border-radius:8px;padding:6px;">
                        @else
                            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:12px;margin:0 auto 14px;text-align:center;line-height:64px;">
                                <span style="font-size:24px;font-weight:700;color:#fff;">{{ strtoupper(substr($student->institute->name ?? 'I', 0, 2)) }}</span>
                            </div>
                        @endif
                        <h1 style="margin:0;font-size:20px;font-weight:700;color:#ffffff;letter-spacing:0.3px;">{{ $student->institute?->name ?? config('app.name') }}</h1>
                        <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.75);">Student Portal — Admission Confirmation</p>
                    </td>
                </tr>

                {{-- ── Welcome Banner ── --}}
                <tr>
                    <td style="background:#eff6ff;border-bottom:1px solid #bfdbfe;padding:18px 40px;text-align:center;">
                        <p style="margin:0;font-size:16px;font-weight:700;color:#1d4ed8;">🎓 Congratulations! Admission Confirmed.</p>
                        <p style="margin:6px 0 0;font-size:13px;color:#1e40af;">Your student portal account is ready. Start your journey today!</p>
                    </td>
                </tr>

                {{-- ── Body ── --}}
                <tr>
                    <td style="padding:32px 40px;">

                        <p style="margin:0 0 20px;font-size:15px;color:#374151;">
                            Dear <strong>{{ $student->name }}</strong>,
                        </p>
                        <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.7;">
                            Welcome to <strong>{{ $student->institute?->name }}</strong>! Your admission has been confirmed and your student portal account has been created. Below are your login details.
                        </p>

                        {{-- Credentials Card --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;overflow:hidden;">
                            <tr>
                                <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Student ID</p>
                                    <p style="margin:4px 0 0;font-size:18px;font-weight:700;color:#2563eb;letter-spacing:1px;">{{ $student->student_uid }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Portal URL</p>
                                    <p style="margin:4px 0 0;font-size:14px;color:#2563eb;font-weight:500;">
                                        <a href="{{ route('student.login') }}" style="color:#2563eb;text-decoration:none;">{{ route('student.login') }}</a>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 24px;border-bottom:1px solid #e2e8f0;">
                                    <p style="margin:0;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:600;">Login Email</p>
                                    <p style="margin:4px 0 0;font-size:15px;color:#1e293b;font-weight:600;">{{ $student->email }}</p>
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
                                    <a href="{{ route('student.login') }}"
                                       style="display:inline-block;background:linear-gradient(135deg,#2563EB,#1d4ed8);color:#ffffff;text-decoration:none;padding:13px 36px;border-radius:8px;font-size:15px;font-weight:700;">
                                        Login to Student Portal →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Security Notice --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td style="padding:14px 20px;">
                                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
                                        <strong>⚠️ Security Notice:</strong> This is a temporary password. After your first login, an OTP will be sent to your email for verification. Please change your password from the Profile section immediately.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;font-size:14px;color:#64748b;line-height:1.7;">
                            If you have any questions, please contact your institute office. We wish you a great academic journey!
                        </p>

                    </td>
                </tr>

                {{-- ── Footer ── --}}
                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
                        <p style="margin:0 0 4px;font-size:12px;color:#94a3b8;">Powered by <strong style="color:#2563EB;">College ERP</strong></p>
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
