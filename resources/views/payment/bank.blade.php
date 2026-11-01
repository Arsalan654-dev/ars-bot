<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank transfer — {{ $payment->user->company ?: $payment->user->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-neutral-100 py-10">
<div class="mx-auto max-w-xl px-4">

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold">Bank transfer</h1>
        <p class="mt-1 text-sm text-neutral-500">
            Transfer <strong>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</strong>
            for {{ $payment->label ?: 'your subscription' }} using the details below.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-neutral-200 bg-white p-6">
        <h2 class="text-sm font-semibold">{{ $account?->name ?? 'Bank account' }}</h2>

        <dl class="mt-4 space-y-3 text-sm">
            @if ($account?->getCred('bank_name'))
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">Bank</dt><dd class="text-right font-medium">{{ $account->getCred('bank_name') }}</dd></div>
            @endif
            @if ($account?->getCred('account_title'))
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">Account title</dt><dd class="text-right font-medium">{{ $account->getCred('account_title') }}</dd></div>
            @endif
            @if ($account?->getCred('account_number'))
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">Account number</dt><dd class="text-right font-mono font-medium">{{ $account->getCred('account_number') }}</dd></div>
            @endif
            @if ($account?->getCred('iban'))
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">IBAN</dt><dd class="text-right font-mono font-medium">{{ $account->getCred('iban') }}</dd></div>
            @endif
            @if ($account?->getCred('swift'))
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">SWIFT / BIC</dt><dd class="text-right font-mono font-medium">{{ $account->getCred('swift') }}</dd></div>
            @endif
            <div class="flex justify-between gap-4 border-t border-neutral-100 pt-3">
                <dt class="text-neutral-500">Reference</dt>
                <dd class="text-right font-mono font-medium">{{ $payment->reference }}</dd>
            </div>
        </dl>

        @if ($account?->getCred('instructions'))
            <p class="mt-4 rounded-lg bg-neutral-50 p-3 text-xs text-neutral-600">{{ $account->getCred('instructions') }}</p>
        @endif

        <p class="mt-4 text-xs text-neutral-500">
            Transfer ke waqt <strong>reference {{ $payment->reference }}</strong> zaroor likhein
            taake hum aapki payment match kar saken.
        </p>

        <form method="POST" action="{{ route('payment.bank-claim', $payment->reference) }}" class="mt-6">
            @csrf
            <button class="w-full rounded-xl bg-neutral-900 py-3 text-sm font-semibold text-white hover:bg-neutral-700">
                I have sent the transfer
            </button>
        </form>

        <a href="{{ route('payment.show', $payment->reference) }}" class="mt-3 block text-center text-xs text-neutral-500 underline">
            ← Back to payment page
        </a>
    </div>
</div>
</body>
</html>
