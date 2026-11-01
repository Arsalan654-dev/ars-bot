@extends('layouts.app')

@section('title', $user->name)
@section('heading', $user->name)

@section('actions')
    <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Edit</a>
    <form method="POST" action="{{ route('admin.users.toggle-bot', $user) }}">
        @csrf
        <button @disabled(! $user->hasActiveSubscription())
                class="rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40 {{ $user->bot_active ? 'bg-red-600 hover:bg-red-500' : 'bg-emerald-600 hover:bg-emerald-500' }}">
            {{ $user->bot_active ? 'Deactivate bot' : 'Activate bot' }}
        </button>
    </form>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profile card --}}
        <div class="space-y-4">
            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    @if ($user->avatar_path)
                        <img src="{{ asset('storage/'.$user->avatar_path) }}" class="h-12 w-12 rounded-full object-cover" alt="">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-900 text-sm font-bold text-white">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <div class="font-semibold">{{ $user->name }}</div>
                        <div class="text-xs text-neutral-500">{{ $user->email }}</div>
                    </div>
                </div>

                <dl class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-neutral-500">Company</dt><dd>{{ $user->company ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Phone</dt><dd>{{ $user->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Status</dt>
                        <dd>
                            @if ($user->hasActiveSubscription())
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Paid</span>
                            @elseif ($user->subscriptionExpired())
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Expired</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-medium text-red-700">Not paid</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Bot</dt>
                        <dd>{{ $user->bot_active ? 'Active' : 'Inactive' }}</dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Renews / ends</dt><dd>{{ $user->subscription_ends_at?->format('M j, Y') ?? '—' }}</dd></div>
                </dl>
            </div>

            {{-- Per-client pricing --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Client pricing</h3>
                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">Edit →</a>
                </div>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-neutral-500">Plan</dt><dd>{{ $user->plan_label ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Amount</dt>
                        <dd class="font-semibold">{{ $user->plan_currency ?: 'USD' }} {{ $user->plan_amount !== null ? number_format((float) $user->plan_amount, 2) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Interval</dt><dd>{{ $user->plan_days ? $user->plan_days.' days' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">Gateway</dt>
                        <dd>{{ $user->gatewayAccount?->name ?? 'Platform default' }}</dd>
                    </div>
                </dl>

                @if ($pendingPayment)
                    <div class="mt-4 rounded-lg bg-amber-50 p-3 text-xs text-amber-800">
                        <div class="font-medium">Pending payment link</div>
                        <div class="mt-1 break-all">{{ route('payment.show', $pendingPayment->reference) }}</div>
                        <div class="mt-1 text-amber-600">
                            {{ $pendingPayment->currency }} {{ number_format($pendingPayment->amount, 2) }}
                            via {{ $pendingPayment->gateway ?: '—' }}/{{ $pendingPayment->mode }}
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.users.regenerate-link', $user) }}" class="mt-3"
                      onsubmit="return confirm('Purana pending link cancel karke naya banayein?')">
                    @csrf
                    <button class="w-full rounded-lg border border-neutral-300 py-2 text-xs font-medium hover:bg-neutral-50">
                        Regenerate payment link (current pricing)
                    </button>
                </form>
            </div>

            {{-- Quick actions --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <h3 class="mb-3 text-sm font-semibold">Actions</h3>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.users.mark-paid', $user) }}">
                        @csrf
                        <button class="w-full rounded-lg bg-neutral-900 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
                            Mark as paid (manual)
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.resend-credentials', $user) }}">
                        @csrf
                        <button class="w-full rounded-lg border border-neutral-300 py-2 text-sm hover:bg-neutral-50">
                            Resend credentials email
                        </button>
                    </form>
                </div>
            </div>

            {{-- API key --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <h3 class="text-sm font-semibold">API key</h3>
                <code class="mt-2 block break-all rounded-lg bg-neutral-100 p-3 text-xs">{{ $user->api_key }}</code>
                <p class="mt-2 text-xs text-neutral-500">Used by the client's website to load their bot.</p>
            </div>
        </div>

        {{-- Payments + clients --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-neutral-200 bg-white">
                <h3 class="border-b border-neutral-100 px-5 py-4 text-sm font-semibold">Payment history</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-neutral-500">
                            <tr>
                                <th class="px-5 py-2">Reference</th>
                                <th class="px-5 py-2">Pricing</th>
                                <th class="px-5 py-2">Gateway</th>
                                <th class="px-5 py-2">Amount</th>
                                <th class="px-5 py-2">Status</th>
                                <th class="px-5 py-2">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-5 py-3"><code class="text-xs">{{ \Illuminate\Support\Str::limit($payment->reference, 16) }}</code></td>
                                    <td class="px-5 py-3">{{ $payment->label ?: '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="capitalize">{{ $payment->gateway ?: '—' }}</span>
                                        <span class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $payment->mode === 'live' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">{{ $payment->mode }}</span>
                                    </td>
                                    <td class="px-5 py-3">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($payment->isPaid())
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Paid</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-neutral-500">{{ $payment->paid_at?->format('M j, Y H:i') ?? $payment->created_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-neutral-400">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white">
                <h3 class="border-b border-neutral-100 px-5 py-4 text-sm font-semibold">Clients from this user's bot</h3>
                <div class="divide-y divide-neutral-100">
                    @forelse ($user->clients as $client)
                        <a href="{{ route('admin.clients.show', $client) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{{ $client->name ?: 'Unknown visitor' }}</div>
                                <div class="truncate text-xs text-neutral-500">{{ $client->summary ?: '—' }}</div>
                            </div>
                            @if ($client->order_confirmed)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Order ✓</span>
                            @endif
                            <span class="text-xs text-neutral-400">{{ $client->created_at->diffForHumans() }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-neutral-400">No clients captured yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
