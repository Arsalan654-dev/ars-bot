@extends('layouts.app')

@section('title', 'Billing')
@section('heading', 'Billing & subscription')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Current status --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6">
                <h2 class="text-sm font-semibold">Current subscription</h2>
                @if ($user->hasActiveSubscription())
                    <div class="mt-3 flex items-center gap-4">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                        <div class="text-sm">
                            {{ $user->plan_label ?: ucfirst($user->subscription_plan ?? 'Paid') }} plan —
                            renews/ends <strong>{{ $user->subscription_ends_at?->format('F j, Y') }}</strong>
                        </div>
                    </div>
                @elseif ($user->subscriptionExpired())
                    <div class="mt-3 flex items-center gap-4">
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Expired</span>
                        <div class="text-sm">Your subscription ended {{ $user->subscription_ends_at?->format('F j, Y') }}. Renew to reactivate your bot.</div>
                    </div>
                @else
                    <div class="mt-3 flex items-center gap-4">
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Not paid</span>
                        <div class="text-sm">Complete your payment below to activate your bot.</div>
                    </div>
                @endif
            </div>

            {{-- Payment history --}}
            <div class="rounded-xl border border-neutral-200 bg-white">
                <h2 class="border-b border-neutral-100 px-5 py-4 text-sm font-semibold">Payment history</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-neutral-500">
                            <tr>
                                <th class="px-5 py-2">Plan</th>
                                <th class="px-5 py-2">Amount</th>
                                <th class="px-5 py-2">Gateway</th>
                                <th class="px-5 py-2">Status</th>
                                <th class="px-5 py-2">Date</th>
                                <th class="px-5 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-5 py-3">{{ $payment->label ?: '—' }}</td>
                                    <td class="px-5 py-3 font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-5 py-3 capitalize">{{ $payment->gateway ?: '—' }}</td>
                                    <td class="px-5 py-3">
                                        @if ($payment->isPaid())
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Paid</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-neutral-500">{{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        @if (! $payment->isPaid())
                                            <a href="{{ route('payment.show', $payment->reference) }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">Pay →</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-400">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Your plan --}}
        <div class="space-y-4">
            <h2 class="text-sm font-semibold">Your plan</h2>
            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <div class="flex items-baseline justify-between">
                    <div class="font-semibold">{{ $user->plan_label ?: 'Subscription' }}</div>
                    <div class="text-lg font-bold">
                        {{ $user->plan_currency ?: 'USD' }} {{ $user->plan_amount !== null ? number_format((float) $user->plan_amount, 2) : '—' }}
                    </div>
                </div>
                <div class="text-xs text-neutral-500">{{ $user->plan_days ? $user->plan_days.' days' : '' }}</div>

                <form method="POST" action="{{ route('crm.billing.checkout') }}" class="mt-4">
                    @csrf
                    <button class="w-full rounded-lg bg-neutral-900 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
                        {{ $user->hasActiveSubscription() ? 'Extend subscription' : 'Pay now' }}
                    </button>
                </form>

                <p class="mt-3 text-[11px] text-neutral-400">
                    Payment is processed securely through our payment provider.
                </p>
            </div>
        </div>
    </div>
@endsection
