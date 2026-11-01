<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, Arial, sans-serif; background:#f5f5f5; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="font-size:20px; margin:0 0 8px;">New client from your bot</h1>
        <p style="color:#555; font-size:14px; margin:0 0 20px;">
            Your bot just captured a new client.
        </p>

        <table style="width:100%; font-size:14px; border-collapse:collapse;">
            <tr><td style="padding:6px 0; color:#777; width:110px;">Name</td><td>{{ $client->name ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0; color:#777;">Email</td><td>{{ $client->email ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0; color:#777;">Phone</td><td>{{ $client->phone ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0; color:#777;">Needs</td><td>{{ $client->needs ?: '—' }}</td></tr>
            <tr><td style="padding:6px 0; color:#777;">Order</td><td>{{ $client->order_confirmed ? 'Confirmed ✓' : 'Not confirmed' }}</td></tr>
        </table>

        <a href="{{ $crmUrl }}"
           style="display:inline-block; margin-top:20px; background:#111; color:#fff; text-decoration:none; padding:10px 18px; border-radius:8px; font-size:14px;">
            Open in CRM
        </a>
    </div>
</body>
</html>
