@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Total clients">{{ $stats['clients_total'] }}</x-stat-card>
        <x-stat-card label="New / unread">
            <span class="{{ $stats['clients_unread'] > 0 ? 'text-red-600' : '' }}">{{ $stats['clients_unread'] }}</span>
        </x-stat-card>
        <x-stat-card label="Orders confirmed">{{ $stats['orders_confirmed'] }}</x-stat-card>
        <x-stat-card label="Order value">${{ number_format((float) $stats['orders_total'], 2) }}</x-stat-card>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Conversations (7d)">{{ $stats['conversations_7d'] }}</x-stat-card>
        <x-stat-card label="Bot status">
            @if ($user->bot_active)
                <span class="text-emerald-600">Active</span>
            @else
                <span class="text-neutral-400">Inactive</span>
            @endif
            <div class="text-xs font-normal">
                <a class="text-neutral-500 underline" href="{{ route('bot.page', $user->api_key) }}" target="_blank">View my bot &rarr;</a>
            </div>
        </x-stat-card>
        <x-stat-card label="Subscription">
            @if ($user->hasActiveSubscription())
                <span class="text-emerald-600">{{ ucfirst($user->subscription_plan ?? 'Paid') }}</span>
                <div class="text-xs font-normal text-neutral-500">until {{ $user->subscription_ends_at?->format('M j, Y') }}</div>
            @else
                <span class="text-red-600">Not paid</span>
                <div class="text-xs font-normal"><a class="text-neutral-500 underline" href="{{ route('crm.billing.index') }}">Renew now</a></div>
            @endif
        </x-stat-card>
        <x-stat-card label="API key">
            <code class="text-xs font-normal">{{ \Illuminate\Support\Str::limit($user->api_key, 18) }}…</code>
            <div class="text-xs font-normal"><a class="text-neutral-500 underline" href="{{ route('crm.bot.embed') }}">Get embed code</a></div>
        </x-stat-card>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- Clients chart --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold">New clients — last 30 days</h2>
            @php
                $max = max(1, (float) $clientsByDay->max());
            @endphp
            @if ($clientsByDay->isEmpty())
                <p class="py-10 text-center text-sm text-neutral-400">No clients yet — your bot is waiting for visitors.</p>
            @else
                <div class="flex h-40 items-end gap-1">
                    @for ($i = 29; $i >= 0; $i--)
                        @php
                            $day = now()->subDays($i)->format('Y-m-d');
                            $value = (int) ($clientsByDay[$day] ?? 0);
                            $height = $value > 0 ? max(6, ($value / $max) * 100) : 2;
                        @endphp
                        <div class="group relative flex-1">
                            <div class="rounded-t bg-neutral-900 transition group-hover:bg-blue-500" style="height: {{ $height }}px"></div>
                            @if ($value > 0)
                                <div class="pointer-events-none absolute -top-8 left-1/2 hidden -translate-x-1/2 rounded bg-neutral-900 px-2 py-1 text-[10px] text-white group-hover:block">
                                    {{ $value }}
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            @endif
        </div>

        {{-- Recent conversations --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold">Recent conversations</h2>
            <div class="divide-y divide-neutral-100">
                @forelse ($recentConversations as $conversation)
                    <a href="{{ route('crm.conversations.show', $conversation) }}" class="flex items-center gap-3 py-3 hover:bg-neutral-50">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium">
                                {{ $conversation->client?->name ?: 'Visitor '.substr($conversation->session_id, 0, 6) }}
                            </div>
                            <div class="truncate text-xs text-neutral-500">{{ $conversation->summary ?: '—' }}</div>
                        </div>
                        <span class="text-xs text-neutral-400">{{ $conversation->last_activity_at?->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-neutral-400">No conversations yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent clients --}}
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white">
        <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
            <h2 class="text-sm font-semibold">Latest clients</h2>
            <a href="{{ route('crm.clients.index') }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">View all →</a>
        </div>
        <div class="divide-y divide-neutral-100">
            @forelse ($recentClients as $client)
                <a href="{{ route('crm.clients.show', $client) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-xs font-semibold">
                        {{ strtoupper(mb_substr($client->name ?: '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $client->name ?: 'Unknown visitor' }}</div>
                        <div class="truncate text-xs text-neutral-500">{{ $client->summary ?: $client->needs ?: '—' }}</div>
                    </div>
                    @if ($client->order_confirmed)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Order ✓</span>
                    @endif
                    @if (! $client->is_read)
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                    @endif
                    <span class="text-xs text-neutral-400">{{ $client->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-sm text-neutral-400">No clients yet.</p>
            @endforelse
        </div>
    </div>
@endsection
