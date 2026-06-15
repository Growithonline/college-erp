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
                Your Library Staff Portal account has been created. You can now log in using your registered
                mobile number and an OTP sent to this email.
            </p>

            {{-- Credentials box --}}
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:18px 20px;margin:20px 0;">
                <div style="font-weight:700;color:#0c4a6e;margin-bottom:12px;font-size:14px;">
                    Your Account Details
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
                        <td style="padding:7px 0;color:#64748b;">Login Mobile</td>
                        <td style="padding:7px 0;font-weight:600;">{{ $libraryStaff->phone }}</td>
                    </tr>
                    <tr>
                        <td style="padding:7px 0;color:#64748b;">OTP sent to</td>
                        <td style="padding:7px 0;">{{ $libraryStaff->email }}</td>
                    </tr>
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
                <strong>How to login:</strong> Go to the portal → enter your mobile number → receive OTP on this email → enter OTP → done.
            </div>

            <p style="margin-bottom:0;font-size:12px;color:#94a3b8;">
                If you have not requested this account, please contact your administrator immediately.<br>
                This email was sent to {{ $libraryStaff->email }}.
            </p>
        </div>
    </div>
</body>
</html>
