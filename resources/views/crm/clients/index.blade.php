@extends('layouts.app')

@section('title', 'Clients')
@section('heading', 'Clients')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search clients…"
               class="w-64 rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
        <select name="filter" class="rounded-lg border border-neutral-300 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="unread" @selected(request('filter') === 'unread')>Unread ({{ $unreadCount }})</option>
            <option value="orders" @selected(request('filter') === 'orders')>With orders</option>
        </select>
        <button class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Filter</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <table class="w-full text-sm">
            <thead class="border-b border-neutral-100 bg-neutral-50 text-left text-xs uppercase tracking-wide text-neutral-500">
                <tr>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Needs</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($clients as $client)
                    <tr class="hover:bg-neutral-50 {{ ! $client->is_read ? 'bg-blue-50/40' : '' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $client->name ?: 'Unknown visitor' }}</div>
                            <div class="text-xs text-neutral-500">{{ $client->email ?: '—' }}{{ $client->phone ? ' · '.$client->phone : '' }}</div>
                        </td>
                        <td class="max-w-xs px-4 py-3">
                            <p class="truncate text-xs text-neutral-600">{{ $client->summary ?: $client->needs ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if ($client->order_confirmed)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                    {{ $client->order_total ? '$'.number_format($client->order_total, 2) : 'Confirmed' }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$client->status" />
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-400">{{ $client->created_at->format('M j, H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('crm.clients.show', $client) }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">Open →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-neutral-400">No clients yet — your bot is waiting for visitors.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $clients->links() }}</div>
@endsection
