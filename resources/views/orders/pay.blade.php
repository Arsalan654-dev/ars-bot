<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->number }} — {{ $order->user?->company ?: $order->user?->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-neutral-100 py-10">
<div class="mx-auto max-w-xl px-4">

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold">Invoice {{ $order->number }}</h1>
        <p class="mt-1 text-sm text-neutral-500">
            {{ $order->user?->company ?: $order->user?->name }} — thank you for your order.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-neutral-200 bg-white p-6">
        {{-- Items --}}
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-neutral-100 text-left text-xs uppercase tracking-wide text-neutral-400">
                    <th class="pb-2">Item</th>
                    <th class="pb-2 text-right">Qty</th>
                    <th class="pb-2 text-right">Price</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-50">
                @foreach ($order->itemsList() as $item)
                    <tr>
                        <td class="py-2">{{ $item['name'] }}</td>
                        <td class="py-2 text-right">{{ $item['qty'] }}</td>
                        <td class="py-2 text-right">{{ $order->currency }} {{ number_format((float) $item['price'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="border-t border-neutral-900 font-semibold">
                    <td class="py-3">Total</td>
                    <td></td>
                    <td class="py-3 text-right">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if ($order->isPaid())
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                <div class="text-lg font-semibold text-emerald-800">Paid ✓</div>
                <p class="mt-1 text-sm text-emerald-700">
                    Payment received {{ $order->paid_at?->format('F j, Y') }}. Thank you!
                </p>
            </div>
        @else
            <div class="mt-6">
                @if ($gatewayAccount)
                    <a href="{{ route('order.payment.pay', $order->payment_reference) }}"
                       class="block w-full rounded-xl bg-emerald-600 py-3 text-center text-sm font-semibold text-white transition hover:bg-emerald-500">
                        Pay {{ $order->currency }} {{ number_format($order->total, 2) }} with {{ \App\Models\PaymentGatewayAccount::GATEWAYS[$gatewayAccount->gateway] ?? $gatewayAccount->gateway }}
                    </a>
                    <p class="mt-2 text-center text-[11px] text-neutral-400">
                        {{ $gatewayAccount->name }} · {{ ucfirst($gatewayAccount->mode) }} mode
                    </p>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-sm text-amber-800">
                        Online payment is not available right now — please contact us to arrange payment.
                    </div>
                @endif
            </div>
        @endif
    </div>

    <p class="mt-6 text-center text-xs text-neutral-400">
        {{ $order->user?->company ?: $order->user?->name }} · powered by {{ config('app.name') }}
    </p>
</div>
</body>
</html>
