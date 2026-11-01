<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment — {{ $payment->user->company ?: $payment->user->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-neutral-100 py-10">
<div class="mx-auto max-w-xl px-4">

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold">Complete your subscription</h1>
        <p class="mt-1 text-sm text-neutral-500">
            Hi {{ $payment->user->name }}, activate your bot access below.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($payment->isPaid())
        <div class="rounded-2xl border border-emerald-200 bg-white p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                <svg class="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold">Payment confirmed</h2>
            <p class="mt-1 text-sm text-neutral-500">
                Your {{ $payment->label ?: 'subscription' }} is active
                @if ($payment->user->subscription_ends_at)
                    until {{ $payment->user->subscription_ends_at->format('F j, Y') }}
                @endif.
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-block rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
                Log in to your CRM
            </a>
        </div>
    @else
        {{-- Order summary --}}
        <div class="rounded-2xl border border-neutral-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">{{ $payment->label ?: 'Subscription' }}</div>
                    <div class="text-xs text-neutral-500">
                        {{ $payment->user->plan_days ? $payment->user->plan_days.' days access' : 'Subscription' }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
                </div>
            </div>

            @if ($gatewayAccount)
                <a href="{{ route('payment.pay', $payment->reference) }}"
                   class="mt-6 block w-full rounded-xl bg-emerald-600 py-3 text-center text-sm font-semibold text-white transition hover:bg-emerald-500">
                    Pay {{ $payment->currency }} {{ number_format($payment->amount, 2) }} with {{ \App\Models\PaymentGatewayAccount::GATEWAYS[$gatewayAccount->gateway] ?? $gatewayAccount->gateway }}
                </a>
                <p class="mt-2 text-center text-[11px] text-neutral-400">
                    {{ $gatewayAccount->name }} · {{ ucfirst($gatewayAccount->mode) }} mode
                    — secure checkout opens on the payment provider's page.
                </p>
            @else
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-center text-sm text-amber-800">
                    No payment method is configured for this account yet. Please contact support.
                </div>
            @endif

            @if (app()->environment('local'))
                <form method="POST" action="{{ route('payment.confirm', $payment->reference) }}" class="mt-3">
                    @csrf
                    <button class="w-full rounded-xl border border-neutral-300 py-2.5 text-sm font-medium hover:bg-neutral-50">
                        Simulate payment (local only)
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
</body>
</html>
