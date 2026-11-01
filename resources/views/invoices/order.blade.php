<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 38px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #17202a; font-size: 11px; }
        .header { width: 100%; margin-bottom: 24px; }
        .header td { border: 0; vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; color: #132f3b; }
        .muted { color: #71808a; }
        .label { color: #71808a; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
        .invoice-title { font-size: 23px; font-weight: bold; letter-spacing: 1px; }
        .badge { display: inline-block; background: #e8f7ee; color: #1a7f37; padding: 4px 9px; border-radius: 10px; font-size: 9px; }
        .badge-unpaid { background: #fff4df; color: #a05a00; }
        .hero { margin: 20px 0; }
        .hero img { width: 100%; height: 215px; object-fit: cover; border-radius: 10px; }
        .hero-caption { margin-top: 5px; color: #71808a; font-size: 8px; }
        .details { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .details td { width: 50%; padding: 12px; border: 1px solid #e3e9eb; vertical-align: top; line-height: 1.6; }
        .details strong { color: #132f3b; }
        .section-title { margin: 18px 0 8px; color: #132f3b; font-size: 13px; }
        .scope { padding: 11px 13px; border-left: 3px solid #24a5a1; background: #f2f8f8; line-height: 1.6; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items th { text-align: left; border-bottom: 1px solid #cbd5d8; padding: 8px 4px; font-size: 9px; color: #71808a; text-transform: uppercase; }
        .items td { padding: 9px 4px; border-bottom: 1px solid #edf0f1; }
        .right { text-align: right; }
        .total-row td { border-top: 2px solid #132f3b; border-bottom: none; font-weight: bold; font-size: 13px; padding-top: 12px; }
        .chart { margin-top: 18px; }
        .chart-row { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .chart-row td { padding: 3px 4px; border: 0; }
        .chart-label { width: 35%; color: #4f626a; }
        .chart-track { width: 48%; background: #edf2f3; }
        .chart-bar { height: 9px; background: #29aaa5; }
        .chart-value { width: 17%; text-align: right; color: #4f626a; }
        .pay-box { margin-top: 24px; border: 1px solid #a8d7c0; border-radius: 8px; padding: 14px; background: #f2fbf6; }
        .pay-box h3 { margin: 0 0 6px; font-size: 13px; color: #14532d; }
        .pay-box p { margin: 0 0 10px; color: #4b5563; font-size: 10px; }
        .pay-link { display: inline-block; background: #16845a; color: #ffffff; padding: 9px 15px; border-radius: 5px; font-size: 11px; text-decoration: none; font-weight: bold; }
        .pay-url { margin-top: 8px; font-size: 8px; color: #6b7280; word-break: break-all; }
        .footer { margin-top: 25px; padding-top: 10px; border-top: 1px solid #e3e9eb; color: #71808a; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ $order->user?->company ?: $order->user?->name }}</div>
                <div class="muted">{{ $order->user?->email }}</div>
            </td>
            <td class="right">
                <div class="invoice-title">INVOICE</div>
                <div class="muted">{{ $order->number }}</div>
                <div class="muted">{{ ($order->invoiced_at ?? $order->created_at)->format('F j, Y') }}</div>
                <div style="margin-top: 7px;">
                    @if ($order->isPaid())
                        <span class="badge">PAID</span>
                    @else
                        <span class="badge badge-unpaid">AWAITING PAYMENT</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if ($artwork)
        <div class="hero">
            <img src="{{ $artwork }}" alt="Illustration of your project requirements">
            <div class="hero-caption">A project illustration created for your requested scope.</div>
        </div>
    @endif

    <table class="details">
        <tr>
            <td>
                <div class="label">Prepared for</div>
                <strong>{{ $order->client?->name ?: 'Client' }}</strong><br>
                @if ($order->client?->company) {{ $order->client->company }}<br> @endif
                @if ($order->client?->email) {{ $order->client->email }}<br> @endif
                @if ($order->client?->phone) {{ $order->client->phone }} @endif
            </td>
            <td>
                <div class="label">Issued by</div>
                <strong>{{ $order->user?->company ?: $order->user?->name }}</strong><br>
                {{ $order->user?->email }}
            </td>
        </tr>
    </table>

    @if ($order->client?->needs)
        <h2 class="section-title">Your project brief</h2>
        <div class="scope">{{ $order->client->needs }}</div>
    @endif

    <h2 class="section-title">Order summary</h2>
    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="right">{{ $item['qty'] }}</td>
                    <td class="right">{{ $order->currency }} {{ number_format((float) $item['price'], 2) }}</td>
                    <td class="right">{{ $order->currency }} {{ number_format($item['qty'] * (float) $item['price'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="right">Total</td>
                <td class="right">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if (count($items) > 0)
        <div class="chart">
            <h2 class="section-title">Investment by service</h2>
            @foreach ($items as $item)
                @php
                    $amount = max(0, (int) $item['qty'] * (float) $item['price']);
                    $share = (float) $order->total > 0 ? min(100, ($amount / (float) $order->total) * 100) : 0;
                @endphp
                <table class="chart-row">
                    <tr>
                        <td class="chart-label">{{ $item['name'] }}</td>
                        <td class="chart-track"><div class="chart-bar" style="width: {{ number_format($share, 2, '.', '') }}%;"></div></td>
                        <td class="chart-value">{{ $order->currency }} {{ number_format($amount, 2) }}</td>
                    </tr>
                </table>
            @endforeach
        </div>
    @endif

    @if (! $order->isPaid() && $order->payment_reference)
        <div class="pay-box">
            <h3>Pay online — {{ $order->currency }} {{ number_format($order->total, 2) }}</h3>
            <p>Click the button below to pay securely. The payment page opens in your browser.</p>
            <a href="{{ route('order.payment.show', $order->payment_reference) }}" class="pay-link">Pay Now →</a>
            <div class="pay-url">{{ route('order.payment.show', $order->payment_reference) }}</div>
        </div>
    @endif

    <div class="footer">Thank you for your business. This invoice was generated after your order was confirmed.</div>
</body>
</html>
