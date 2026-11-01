<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, Segoe UI, Helvetica, Arial, sans-serif; background:#f5f5f5; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; padding:32px;">
        <h1 style="font-size:20px; margin:0 0 8px;">Order confirmed — invoice {{ $order->number }}</h1>
        <p style="color:#555; font-size:14px; margin:0 0 20px;">
            Hi {{ $order->client?->name ?: 'there' }}, thanks for confirming your order.
            Your illustrated invoice, project brief, and service breakdown are in the attached PDF.
        </p>

        <table style="width:100%; font-size:14px; border-collapse:collapse;">
            <tr style="border-bottom:1px solid #eee;">
                <th style="text-align:left; padding:8px 0; color:#777; font-weight:normal;">Item</th>
                <th style="text-align:right; padding:8px 0; color:#777; font-weight:normal;">Qty</th>
                <th style="text-align:right; padding:8px 0; color:#777; font-weight:normal;">Price</th>
            </tr>
            @foreach ($order->itemsList() as $item)
                <tr style="border-bottom:1px solid #f5f5f5;">
                    <td style="padding:8px 0;">{{ $item['name'] }}</td>
                    <td style="padding:8px 0; text-align:right;">{{ $item['qty'] }}</td>
                    <td style="padding:8px 0; text-align:right;">
                        {{ $order->currency }} {{ number_format((float) $item['price'], 2) }}
                        <br><span style="font-size:11px;color:#777;">Line total: {{ $order->currency }} {{ number_format($item['qty'] * (float) $item['price'], 2) }}</span>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="padding:12px 0; font-weight:bold;">Total</td>
                <td></td>
                <td style="padding:12px 0; text-align:right; font-weight:bold;">
                    {{ $order->currency }} {{ number_format($order->total, 2) }}
                </td>
            </tr>
        </table>

        @if (! $order->isPaid() && $order->payment_reference)
            <div style="margin-top:24px; padding:20px; background:#f6fdf9; border:2px solid #16a34a; border-radius:10px; text-align:center;">
                <p style="margin:0 0 12px; font-size:14px; color:#14532d; font-weight:bold;">
                    Pay {{ $order->currency }} {{ number_format($order->total, 2) }} online
                </p>
                <a href="{{ route('order.payment.show', $order->payment_reference) }}"
                   style="display:inline-block; background:#16a34a; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:8px; font-size:14px; font-weight:bold;">
                    Pay Now &rarr;
                </a>
                <p style="margin:12px 0 0; font-size:11px; color:#6b7280; word-break:break-all;">
                    Or open: {{ route('order.payment.show', $order->payment_reference) }}
                </p>
            </div>
        @elseif ($order->isPaid())
            <div style="margin-top:24px; padding:16px; background:#e8f7ee; border-radius:10px; text-align:center;">
                <p style="margin:0; font-size:14px; color:#1a7f37; font-weight:bold;">
                    Paid ✓ — thank you!
                </p>
            </div>
        @endif

        <p style="color:#999; font-size:12px; margin-top:24px;">
            {{ $order->user?->company ?: $order->user?->name }} — powered by {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
