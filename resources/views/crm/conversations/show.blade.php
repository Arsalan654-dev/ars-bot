@extends('layouts.app')

@section('title', 'Conversation')
@section('heading', 'Conversation')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ url()->previous() }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">← Back</a>

        <div class="mt-4 rounded-xl border border-neutral-200 bg-white">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-3">
                <div>
                    <div class="text-sm font-semibold">
                        {{ $conversation->client?->name ?: 'Visitor '.substr($conversation->session_id, 0, 8) }}
                    </div>
                    <div class="text-xs text-neutral-500">
                        {{ $conversation->messages_count }} messages ·
                        {{ $conversation->page_url ?: 'unknown page' }} ·
                        {{ $conversation->ip }}
                    </div>
                </div>
                @if ($conversation->client)
                    <a href="{{ route('crm.clients.show', $conversation->client) }}" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">
                        Client profile →
                    </a>
                @endif
            </div>

            @if ($conversation->summary)
                <div class="border-b border-neutral-100 bg-neutral-50 px-5 py-3 text-xs text-neutral-600">
                    <strong>AI summary:</strong> {{ $conversation->summary }}
                </div>
            @endif

            <div class="space-y-3 p-5">
                @foreach ($conversation->messages as $message)
                    @if ($message->role === 'system')
                        @continue
                    @endif
                    <div class="flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm {{ $message->role === 'user' ? 'bg-neutral-900 text-white' : 'bg-neutral-100' }}">
                            {{ $message->content }}
                            @if ($message->meta['order_id'] ?? false)
                                <div class="mt-3 border-t border-neutral-300 pt-3">
                                    <a href="{{ route('crm.orders.invoice', $message->meta['order_id']) }}" class="font-medium underline">
                                        Invoice {{ $message->meta['order_number'] }}
                                    </a>
                                    @if ($message->meta['invoice_artwork'] ?? false)
                                        <img src="{{ route('crm.orders.artwork', $message->meta['order_id']) }}"
                                             alt="AI-generated invoice artwork" class="mt-2 rounded-lg">
                                    @endif
                                    <div class="mt-1 text-xs">
                                        {{ ($message->meta['invoice_sent'] ?? false) ? 'Invoice emailed' : 'Waiting for a valid invoice email' }}
                                    </div>
                                </div>
                            @endif
                            <div class="mt-1 text-[10px] opacity-50">{{ $message->created_at->format('H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
