@extends('layouts.app')

@section('title', 'Payment required')
@section('heading', 'Payment required')

@section('content')
    <div class="mx-auto max-w-xl">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="text-lg font-semibold text-amber-900">Please pay first, then use your CRM</h2>
            <p class="mt-2 text-sm text-amber-800">
                Hi {{ $user->name }}, your subscription is not active yet. Complete your payment
                and your bot + CRM will unlock instantly.
            </p>

            @if ($payment)
                <div class="mt-4 flex items-center justify-between rounded-xl bg-white/70 p-4">
                    <div>
                        <div class="font-semibold">{{ $payment->label ?: 'Subscription' }}</div>
                        <div class="text-xs text-neutral-500">
                            {{ $user->plan_days ? $user->plan_days.' days access' : '' }}
                            @if ($payment->gateway) · via {{ ucfirst($payment->gateway) }} @endif
                        </div>
                    </div>
                    <div class="text-xl font-bold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</div>
                </div>

                <a href="{{ route('payment.show', $payment->reference) }}"
                   class="mt-4 inline-block w-full rounded-lg bg-neutral-900 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-neutral-700">
                    Pay now &rarr;
                </a>
            @else
                <p class="mt-4 text-sm text-amber-800">
                    No payment link found. Please contact the platform owner.
                </p>
            @endif
        </div>
    </div>
@endsection
