@extends('layouts.app')

@section('title', $client->name ?: 'Client')
@section('heading', $client->name ?: 'Unknown visitor')

@section('actions')
    @if ($client->email)
        <a href="mailto:{{ $client->email }}" class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Email client</a>
    @endif
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details + status --}}
        <div class="space-y-4">
            <form method="POST" action="{{ route('crm.clients.update', $client) }}" class="rounded-xl border border-neutral-200 bg-white p-5">
                @csrf
                @method('PUT')
                <h3 class="mb-4 text-sm font-semibold">Contact details</h3>
                <div class="space-y-3 text-sm">
                    <input name="name" value="{{ $client->name }}" placeholder="Name" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input name="email" value="{{ $client->email }}" placeholder="Email" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input name="phone" value="{{ $client->phone }}" placeholder="Phone" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <input name="company" value="{{ $client->company }}" placeholder="Company" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <select name="status" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        @foreach (['new' => 'New', 'contacted' => 'Contacted', 'won' => 'Won', 'lost' => 'Lost'] as $value => $label)
                            <option value="{{ $value }}" @selected($client->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="mt-4 w-full rounded-lg bg-neutral-900 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
                    Save & mark contacted
                </button>
            </form>

            <div class="rounded-xl border border-neutral-200 bg-white p-5">
                <h3 class="mb-2 text-sm font-semibold">Needs</h3>
                <p class="text-sm text-neutral-600">{{ $client->needs ?: '—' }}</p>
                @if ($client->summary)
                    <p class="mt-3 border-t border-neutral-100 pt-3 text-xs text-neutral-500">{{ $client->summary }}</p>
                @endif
            </div>

            @if ($client->order_confirmed)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <h3 class="text-sm font-semibold text-emerald-800">Order confirmed ✓</h3>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">
                        {{ $client->order_total ? '$'.number_format($client->order_total, 2) : '—' }}
                    </p>
                </div>
            @endif

            @if ($orders->isNotEmpty())
                <div class="rounded-xl border border-neutral-200 bg-white p-5">
                    <h3 class="mb-3 text-sm font-semibold">Invoices</h3>
                    <div class="space-y-2">
                        @foreach ($orders as $order)
                            <div class="rounded-lg bg-neutral-50 px-3 py-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('crm.orders.invoice', $order) }}" target="_blank" class="hover:underline">
                                        {{ $order->number }} ↓
                                    </a>
                                    <span class="font-medium">${{ number_format($order->total, 2) }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-xs">
                                    @if ($order->isPaid())
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">Paid ✓ {{ $order->paid_at?->format('M j') }}</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">Awaiting payment</span>
                                        @if ($order->payment_reference)
                                            <a href="{{ route('order.payment.show', $order->payment_reference) }}" target="_blank"
                                               class="text-neutral-500 underline">Payment link</a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('crm.clients.destroy', $client) }}" onsubmit="return confirm('Delete this client?')">
                @csrf
                @method('DELETE')
                <button class="w-full rounded-lg border border-red-200 bg-red-50 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                    Delete client
                </button>
            </form>
        </div>

        {{-- Conversations --}}
        <div class="space-y-6 lg:col-span-2">
            @forelse ($client->conversations as $conversation)
                <div class="rounded-xl border border-neutral-200 bg-white">
                    <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-3">
                        <div class="text-xs text-neutral-500">
                            {{ $conversation->messages_count }} messages ·
                            {{ $conversation->page_url ?: 'unknown page' }}
                        </div>
                        <div class="text-xs text-neutral-400">{{ $conversation->last_activity_at?->diffForHumans() }}</div>
                    </div>
                    @if ($conversation->summary)
                        <div class="border-b border-neutral-100 bg-neutral-50 px-5 py-3 text-xs text-neutral-600">
                            <strong>AI summary:</strong> {{ $conversation->summary }}
                        </div>
                    @endif
                    <div class="max-h-96 space-y-3 overflow-y-auto p-5">
                        @foreach ($conversation->messages->sortBy('id') as $message)
                            <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $message->role === 'user' ? 'bg-neutral-900 text-white' : 'bg-neutral-100' }}">
                                    {{ $message->content }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-400">
                    No conversations recorded.
                </div>
            @endforelse
        </div>
    </div>
@endsection
