<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#2563EB;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            {{ $isWaitlisted ? 'Your Application is on the Waitlist' : 'Next Steps for Your Admission' }}
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear <strong>{{ $student->name }}</strong>,</p>
            @if($isWaitlisted)
                <p>Your application has been received, but the stream you applied for is currently full. You have been placed on the waitlist and we will notify you as soon as a seat becomes available.</p>
            @else
                <p>Your application has been received. Please click the button below to upload your documents and complete payment (if applicable) to finish your admission.</p>
            @endif

            <div style="text-align:center;margin:24px 0;">
                <a href="{{ $url }}" style="background:#2563EB;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;display:inline-block;">
                    {{ $isWaitlisted ? 'View Application Status' : 'View Next Steps' }}
                </a>
            </div>

            <p style="color:#64748b;font-size:13px;">This link is valid for 30 days. Do not share it with anyone.</p>
            <p style="margin-bottom:0;">Regards,<br><strong>{{ $student->institute->name }}</strong></p>
        </div>
    </div>
</body>
</html>
