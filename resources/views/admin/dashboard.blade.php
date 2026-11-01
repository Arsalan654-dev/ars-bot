@extends('layouts.app')

@section('title', 'Admin dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Monthly revenue">${{ number_format($stats['revenue_month'], 2) }}</x-stat-card>
        <x-stat-card label="Weekly revenue">${{ number_format($stats['revenue_week'], 2) }}</x-stat-card>
        <x-stat-card label="Total revenue">${{ number_format($stats['revenue_all'], 2) }}</x-stat-card>
        <x-stat-card label="Active bots">{{ $stats['active_bots'] }}</x-stat-card>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Users">{{ $stats['total_users'] }} <span class="text-sm font-normal text-neutral-500">({{ $stats['paid_users'] }} paid)</span></x-stat-card>
        <x-stat-card label="Clients captured">{{ $stats['total_clients'] }}</x-stat-card>
        <x-stat-card label="Unread clients">
            <span class="{{ $stats['unread_clients'] > 0 ? 'text-red-600' : '' }}">{{ $stats['unread_clients'] }}</span>
        </x-stat-card>
        <x-stat-card label="Top buyers">
            @if ($topBuyers->first())
                {{ $topBuyers->first()->name }}
                <span class="text-sm font-normal text-neutral-500">({{ $topBuyers->first()->paid_payments_count }}x)</span>
            @else
                —
            @endif
        </x-stat-card>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- Revenue chart --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold">Revenue — last 30 days</h2>
            @php
                $max = max(1, (float) $revenueByDay->max());
            @endphp
            @if ($revenueByDay->isEmpty())
                <p class="py-10 text-center text-sm text-neutral-400">No payments yet.</p>
            @else
                <div class="flex h-40 items-end gap-1">
                    @for ($i = 29; $i >= 0; $i--)
                        @php
                            $day = now()->subDays($i)->format('Y-m-d');
                            $value = (float) ($revenueByDay[$day] ?? 0);
                            $height = $value > 0 ? max(6, ($value / $max) * 100) : 2;
                        @endphp
                        <div class="group relative flex-1">
                            <div class="rounded-t bg-neutral-900 transition group-hover:bg-emerald-500" style="height: {{ $height }}px"></div>
                            @if ($value > 0)
                                <div class="pointer-events-none absolute -top-8 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-neutral-900 px-2 py-1 text-[10px] text-white group-hover:block">
                                    ${{ number_format($value, 2) }}
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            @endif
        </div>

        {{-- Top buyers --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold">Top buyers</h2>
            <div class="divide-y divide-neutral-100">
                @forelse ($topBuyers as $buyer)
                    <a href="{{ route('admin.users.show', $buyer) }}" class="flex items-center justify-between py-3 hover:bg-neutral-50">
                        <div>
                            <div class="text-sm font-medium">{{ $buyer->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $buyer->company ?: $buyer->email }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold">${{ number_format((float) $buyer->paid_total, 2) }}</div>
                            <div class="text-xs text-neutral-500">{{ $buyer->paid_payments_count }} payment(s)</div>
                        </div>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-neutral-400">No payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent clients --}}
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white">
        <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
            <h2 class="text-sm font-semibold">Latest clients from all bots</h2>
            <a href="{{ route('admin.clients.index') }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">View all →</a>
        </div>
        <div class="divide-y divide-neutral-100">
            @forelse ($recentClients as $client)
                <a href="{{ route('admin.clients.show', $client) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-neutral-50">
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
                <p class="px-5 py-8 text-center text-sm text-neutral-400">No clients yet — share your bot!</p>
            @endforelse
        </div>
    </div>
@endsection
