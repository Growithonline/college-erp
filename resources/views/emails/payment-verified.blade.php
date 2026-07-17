<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
        <div style="background:#16a34a;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:700;">
            Payment Verified
        </div>
        <div style="padding:24px;">
            <p style="margin-top:0;">Dear <strong>{{ $student->name }}</strong>,</p>
            <p>Your payment of <strong>Rs {{ number_format($amount, 2) }}</strong> towards your admission has been verified.</p>
            <p>Your application is now with the institute for final review. You'll receive your student ID and login details by email once your admission is approved.</p>
            <p style="margin-bottom:0;">Regards,<br><strong>{{ $student->institute->name }}</strong></p>
        </div>
    </div>
</body>
</html>
