<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, Arial, sans-serif; background:#f5f5f5; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="font-size:20px; margin:0 0 8px;">Your bot CRM access</h1>
        <p style="color:#555; font-size:14px; margin:0 0 20px;">
            Hi {{ $user->name }}, your account is ready. Use the credentials below to log in.
        </p>

        <table style="width:100%; font-size:14px; border-collapse:collapse;">
            <tr>
                <td style="padding:8px 0; color:#777;">Login URL</td>
                <td style="padding:8px 0;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></td>
            </tr>
            <tr>
                <td style="padding:8px 0; color:#777;">Email</td>
                <td style="padding:8px 0;">{{ $user->email }}</td>
            </tr>
            @if ($plainPassword)
                <tr>
                    <td style="padding:8px 0; color:#777;">Password</td>
                    <td style="padding:8px 0;"><code style="background:#f0f0f0; padding:2px 6px; border-radius:4px;">{{ $plainPassword }}</code></td>
                </tr>
            @endif
        </table>

        @if ($paymentUrl)
            <div style="margin-top:24px; padding:16px; background:#fff8e6; border:1px solid #f2d58a; border-radius:8px;">
                <p style="margin:0 0 10px; font-size:14px; color:#7a5b00;">
                    <strong>Action required:</strong> activate your subscription to unlock the CRM and your bot.
                </p>
                <a href="{{ $paymentUrl }}"
                   style="display:inline-block; background:#111; color:#fff; text-decoration:none; padding:10px 18px; border-radius:8px; font-size:14px;">
                    Complete payment
                </a>
            </div>
        @endif

        <p style="color:#999; font-size:12px; margin-top:24px;">
            If you didn't expect this email, you can ignore it.
        </p>
    </div>
</body>
</html>
