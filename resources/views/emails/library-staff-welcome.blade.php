<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:580px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#0c4a6e,#0ea5e9);padding:28px 28px 20px;text-align:center;">
            <div style="font-size:36px;margin-bottom:8px;">📚</div>
            <div style="font-size:20px;font-weight:700;color:#fff;">Welcome to the Library Portal</div>
            <div style="font-size:13px;color:#bae6fd;margin-top:4px;">
                {{ $libraryStaff->institute->name ?? 'Institute' }}
            </div>
        </div>

        <div style="padding:28px;">
            <p style="margin-top:0;font-size:15px;">
                Hello <strong>{{ $libraryStaff->name }}</strong>,
            </p>
            <p style="color:#374151;">
                Your Library Staff Portal account has been created. Use the login credentials below to access the portal.
                An OTP will be sent to your email each time you log in.
            </p>

            {{-- Credentials box --}}
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:18px 20px;margin:20px 0;">
                <div style="font-weight:700;color:#0c4a6e;margin-bottom:12px;font-size:14px;">
                    Your Login Credentials
                </div>
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tr style="border-bottom:1px solid #e0f2fe;">
                        <td style="padding:7px 0;color:#64748b;width:38%;">Employee ID</td>
                        <td style="padding:7px 0;font-weight:600;font-family:monospace;color:#0c4a6e;">
                            {{ $libraryStaff->employee_id }}
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #e0f2fe;">
                        <td style="padding:7px 0;color:#64748b;">Designation</td>
                        <td style="padding:7px 0;font-weight:600;">
                            {{ \App\Models\LibraryStaff::DESIGNATION_LABELS[$libraryStaff->designation] ?? $libraryStaff->designation }}
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #e0f2fe;">
                        <td style="padding:7px 0;color:#64748b;">Login Email</td>
                        <td style="padding:7px 0;font-weight:600;">{{ $libraryStaff->email }}</td>
                    </tr>
                    @if($plainPassword)
                    <tr>
                        <td style="padding:7px 0;color:#64748b;">Password</td>
                        <td style="padding:7px 0;">
                            <span style="font-family:monospace;font-size:15px;font-weight:700;
                                         background:#fef9c3;padding:3px 10px;border-radius:6px;
                                         border:1px solid #fde047;color:#713f12;letter-spacing:.05em;">
                                {{ $plainPassword }}
                            </span>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Login button --}}
            <div style="text-align:center;margin:24px 0;">
                <a href="{{ $loginUrl }}"
                   style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:#fff;text-decoration:none;border-radius:10px;font-weight:600;font-size:15px;letter-spacing:.02em;">
                    Login to Library Portal
                </a>
            </div>

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:13px;color:#92400e;margin-bottom:20px;">
                <strong>How to login:</strong> Go to the portal → enter your email &amp; password → an OTP will be sent to this email → enter OTP → done.<br>
                <strong>Please change your password after first login.</strong>
            </div>

            <p style="margin-bottom:0;font-size:12px;color:#94a3b8;">
                If you have not requested this account, please contact your administrator immediately.<br>
                This email was sent to {{ $libraryStaff->email }}.
            </p>
        </div>
    </div>
</body>
</html>
