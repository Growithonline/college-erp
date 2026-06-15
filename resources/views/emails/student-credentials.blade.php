<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#2563EB;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            🎓 Admission Confirmed — Student Portal
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear <strong>{{ $student->name }}</strong>,</p>
            <p>Congratulations! Your admission at <strong>{{ $student->institute?->name ?? config('app.name') }}</strong> has been confirmed.</p>
            <p>Your student portal login details are below. Please keep them safe.</p>

            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px;margin:20px 0;">
                <p style="margin:0 0 8px;"><strong>Portal URL:</strong> <a href="{{ route('student.login') }}" style="color:#2563EB;">{{ route('student.login') }}</a></p>
                <p style="margin:0 0 8px;"><strong>Student ID:</strong> {{ $student->student_uid }}</p>
                <p style="margin:0 0 8px;"><strong>Email:</strong> {{ $student->email }}</p>
                <p style="margin:0;"><strong>Temporary Password:</strong> {{ $plainPassword }}</p>
            </div>

            <p style="color:#dc2626;font-size:13px;">⚠️ First login pe aapko password change karna hoga.</p>

            <p style="margin-bottom:0;">Regards,<br><strong>{{ $student->institute?->name ?? config('app.name') }}</strong></p>
        </div>
    </div>
</body>
</html>
