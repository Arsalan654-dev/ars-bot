@extends('layouts.app')

@section('title', 'API & embed')
@section('heading', 'API key & embed')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="text-sm font-semibold">Your API key</h2>
            <p class="mt-1 text-xs text-neutral-500">
                This key identifies your bot. Anyone with the key can serve your bot — keep it secret.
            </p>
            <code class="mt-3 block break-all rounded-lg bg-neutral-100 p-3 text-xs">{{ $user->api_key }}</code>

            <form method="POST" action="{{ route('crm.bot.api-key') }}" class="mt-3"
                  onsubmit="return confirm('Regenerate API key? Your existing embed will stop working until updated.')">
                @csrf
                <button class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Regenerate key</button>
            </form>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="text-sm font-semibold">Embed on your website</h2>
            <p class="mt-1 text-xs text-neutral-500">
                Paste this before <code>&lt;/body&gt;</code>. It adds a floating chat launcher
                <strong>and</strong> a nav link to your bot page.
            </p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-neutral-900 p-4 text-xs text-neutral-100">&lt;script
  src="{{ url('/widget.js') }}?key={{ $user->api_key }}"
  data-nav-link="true"
  data-nav-label="{{ $user->bot_name }}"
&gt;&lt;/script&gt;</pre>

            <h3 class="mt-5 text-sm font-semibold">Direct links</h3>
            <div class="mt-2 space-y-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-24 text-neutral-500">Bot page</span>
                    <code class="flex-1 break-all rounded bg-neutral-100 px-2 py-1">{{ route('bot.page', $user->api_key) }}</code>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-24 text-neutral-500">Embed frame</span>
                    <code class="flex-1 break-all rounded bg-neutral-100 px-2 py-1">{{ route('bot.embed', $user->api_key) }}</code>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-24 text-neutral-500">API endpoint</span>
                    <code class="flex-1 break-all rounded bg-neutral-100 px-2 py-1">POST {{ url('/api/bot/chat') }} (X-Api-Key)</code>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="text-sm font-semibold">Custom frontend (developer)</h2>
            <p class="mt-1 text-xs text-neutral-500">Send a POST request from your own app:</p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-neutral-900 p-4 text-xs text-neutral-100">fetch('{{ url('/api/bot/chat') }}', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Api-Key': '{{ $user->api_key }}'
  },
  body: JSON.stringify({
    session_id: 'visitor-123',
    message: 'Hello!',
    page_url: window.location.href
  })
})</pre>
            <p class="mt-2 text-xs text-neutral-500">
                Response: <code>{ reply, conversation_id, order_confirmed, order_number }</code>
            </p>
        </div>
    </div>
@endsection
