<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, Arial, sans-serif; background:#f5f5f5; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="font-size:20px; margin:0 0 8px;">Your subscription needs attention</h1>
        <p style="color:#555; font-size:14px; margin:0 0 20px;">
            Hi {{ $user->name }}, your bot subscription has expired or is awaiting payment.
            Your bot has been paused until the subscription is renewed.
        </p>
        <a href="{{ route('login') }}"
           style="display:inline-block; background:#111; color:#fff; text-decoration:none; padding:10px 18px; border-radius:8px; font-size:14px;">
            Log in &amp; renew
        </a>
    </div>
</body>
</html>
