<!DOCTYPE html>
<html>
<head>
    <title>Hgtr - New Code</title>
</head>
<body>
    <div style="font-family: Segoe UI; width: 500px; margin: auto auto; padding: 56px; box-shadow: 0 4px 4px 0 rgba(233, 240, 243, 0.4); border: 1px solid #ECEFF3; border-radius: 12px; text-align: center;">
        <img src="https://hugoteroclothing.com/hgtr-logo.png" alt="Logo" style="width:150px;">
        <h1>Reset Password</h1>
        <p style="font-weight: 400; font-size: 16px;">If you've lost your password or wish to reset it, use the link below to get started. 
            You'll receive an email containing a verification key that you'll need to enter during the password reset process to ensure the security of your account.</p>
        <a href="https://hugoteroclothing.com/update-password?verificationKey={{ $verificationToken }}&email={{ $email }}" style="display: inline-block; padding: 10px 20px; background-color: #000000; color: white; border: none; border-radius: 5px; text-decoration: none; font-size: 16px; margin-top: 20px;">Reset Password</a>
        <p style="font-weight: 400; font-size: 16px; margin-top: 20px;">If you did not request this verification code, please ignore this message.</p>
    </div>
</body>
</html>
