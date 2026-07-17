<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#dc2626;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            Payment Could Not Be Verified
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear <strong>{{ $student->name }}</strong>,</p>
            <p>We could not verify the payment you submitted for your admission. Reason given by the institute:</p>
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin:16px 0;color:#991b1b;">
                {{ $reason }}
            </div>
            <p>Please review and submit your payment proof again using the button below.</p>

            <div style="text-align:center;margin:24px 0;">
                <a href="{{ $paymentUrl }}" style="background:#2563EB;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;display:inline-block;">
                    Resubmit Payment
                </a>
            </div>

            <p style="color:#64748b;font-size:13px;">This link is valid for 30 days. Do not share it with anyone.</p>
            <p style="margin-bottom:0;">Regards,<br><strong>{{ $student->institute->name }}</strong></p>
        </div>
    </div>
</body>
</html>
