<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fraud Report Received</title>
</head>
<body>
    <p>Hello {{ $report->full_name }},</p>
    <p>We received your fraud report and our team is reviewing it.</p>
    <p><strong>Reference:</strong> FR-{{ $report->id }}</p>
    <p><strong>Submitted At:</strong> {{ optional($report->created_at)->format('Y-m-d H:i') }}</p>
    <p>If we need more details, we will contact you at this email or phone number.</p>
    <p>For your security, please do not send full card numbers, CVV, PIN, or OTP in email replies.</p>
    <p>Regards,<br>Fraud Team</p>
</body>
</html>
