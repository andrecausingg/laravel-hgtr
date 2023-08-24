<!DOCTYPE html>
<html>
<head>
    <title>Hgtr - New Code</title>
</head>
<body>
    <div style="font-family: 'Segoe UI', sans-serif; width: 500px; margin: auto; padding: 40px; box-shadow: 0 4px 4px rgba(0, 0, 0, 0.1); border: 1px solid #ECEFF3; border-radius: 12px; text-align: center;">
        <h2 style="font-size: 24px; margin-bottom: 16px;">Dear valued {{ $name }},</h2>
        <p style="font-weight: 400; font-size: 16px; margin: 12px 0;">Here is your new verification code:</p>
        <p style="font-size: 32px; font-weight: 600; color: #007bff; margin: 0;">{{ $verificationCode }}</p>
        <p style="font-weight: 400; font-size: 16px; margin: 12px 0;">If you did not request this verification code, please ignore this message.</p>
    </div>
</body>
</html>
