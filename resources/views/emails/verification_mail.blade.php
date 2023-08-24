<!DOCTYPE html>
<html>
<head>
    <title>Hgtr - Verification Code</title>
</head>
<body>
    <div style="font-family: Segoe UI; width: 500px; margin: auto auto; padding: 56px; box-shadow: 0 4px 4px 0 rgba(233, 240, 243, 0.4); border: 1px solid #ECEFF3; border-radius: 12px;">
        <h2 style="font-size: 30px;">Dear valued {{ $name }},</h2>
        <p style="font-weight: 400; font-size: 16px;">Thank you for signing up for our service. To complete the verification process, please use the following code: <span style="font-size: 24px; font-weight: 600;">{{ $verificationCode }}</span>.</p>
        <p style="font-weight: 400; font-size: 16px;">If you did not request this verification code, please ignore this message.</p>
    </div>
</body>
</html>
