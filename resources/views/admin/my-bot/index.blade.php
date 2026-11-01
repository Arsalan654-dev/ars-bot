@extends('layouts.app')

@section('title', 'My bot')
@section('heading', 'My bot')

@section('content')
    {{-- Stats strip --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Bot status">
            @if ($user->bot_active)
                <span class="text-emerald-600">Active</span>
            @else
                <span class="text-neutral-400">Inactive</span>
            @endif
        </x-stat-card>
        <x-stat-card label="Clients captured">{{ $clientsCount }}</x-stat-card>
        <x-stat-card label="Conversations">{{ $conversationsCount }}</x-stat-card>
        <x-stat-card label="Knowledge entries">{{ $entries->count() }}</x-stat-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Bot profile --}}
        <form method="POST" action="{{ route('admin.settings.bot') }}" enctype="multipart/form-data"
              class="rounded-xl border border-neutral-200 bg-white p-6">
            @csrf
            @method('PUT')
            <h2 class="text-sm font-semibold">Bot profile</h2>
            <p class="mb-4 mt-1 text-xs text-neutral-500">Ye tumhara apna bot hai jo home page pe serve hota hai.</p>

            <div class="flex items-center gap-5">
                @if ($user->avatar_path)
                    <img src="{{ asset('storage/'.$user->avatar_path) }}" class="h-20 w-20 rounded-2xl object-cover" alt="">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-neutral-900 text-xl font-bold text-white">
                        {{ strtoupper(mb_substr($user->bot_name, 0, 1)) }}
                    </span>
                @endif
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium">Bot avatar</label>
                    <input name="avatar" type="file" accept="image/*" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs">
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Bot name</label>
                    <input name="bot_name" value="{{ $user->bot_name }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Accent color</label>
                    <input name="bot_color" type="color" value="{{ $user->bot_color }}" class="h-10 w-full rounded-lg border border-neutral-300">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Theme</label>
                    <select name="bot_theme" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        <option value="lisa" @selected($user->bot_theme === 'lisa')>Light (LISA)</option>
                        <option value="dark" @selected($user->bot_theme === 'dark')>Dark</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Global knowledge (always known)</label>
                    <textarea name="knowledge_base" rows="4" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                              placeholder="Company description, tone of voice…">{{ $user->knowledge_base }}</textarea>
                </div>
            </div>

            <button class="mt-5 rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
                Save bot profile
            </button>
        </form>

        {{-- API & embed --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-neutral-200 bg-white p-6">
                <h2 class="text-sm font-semibold">API key</h2>
                <code class="mt-3 block break-all rounded-lg bg-neutral-100 p-3 text-xs">{{ $user->api_key }}</code>
                <form method="POST" action="{{ route('admin.settings.api-key') }}" class="mt-3"
                      onsubmit="return confirm('Regenerate API key? Old embeds will stop working.')">
                    @csrf
                    <button class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Regenerate key</button>
                </form>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white p-6">
                <h2 class="text-sm font-semibold">Embed on your website</h2>
                <p class="mt-1 text-xs text-neutral-500">Paste this before <code>&lt;/body&gt;</code> — floating chat + nav link.</p>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-neutral-900 p-4 text-xs text-neutral-100">&lt;script
  src="{{ url('/widget.js') }}?key={{ $user->api_key }}"
  data-nav-link="true"
  data-nav-label="{{ $user->bot_name }}"
&gt;&lt;/script&gt;</pre>

                <div class="mt-4 space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-24 shrink-0 text-neutral-500">Bot page</span>
                        <code class="flex-1 break-all rounded bg-neutral-100 px-2 py-1">{{ route('bot.page', $user->api_key) }}</code>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-24 shrink-0 text-neutral-500">Embed frame</span>
                        <code class="flex-1 break-all rounded bg-neutral-100 px-2 py-1">{{ route('bot.embed', $user->api_key) }}</code>
                    </div>
                </div>

                <a href="{{ route('bot.page', $user->api_key) }}" target="_blank"
                   class="mt-4 inline-block rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-700">
                    View my bot &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Knowledge base --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <h2 class="text-sm font-semibold">Knowledge base</h2>

            @forelse ($entries as $entry)
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5">
                    <form method="POST" action="{{ route('admin.my-bot.knowledge.update', $entry) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3">
                            <input name="title" value="{{ $entry->title }}" required
                                   class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium">
                            <textarea name="content" rows="3" required
                                      class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $entry->content }}</textarea>
                            <div class="flex flex-wrap items-center gap-3">
                                <select name="type" class="rounded-lg border border-neutral-300 px-3 py-1.5 text-xs">
                                    @foreach (['text' => 'General', 'faq' => 'FAQ', 'product' => 'Product', 'service' => 'Service', 'policy' => 'Policy'] as $value => $label)
                                        <option value="{{ $value }}" @selected($entry->type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" @checked($entry->is_active) class="rounded border-neutral-300">
                                    Active
                                </label>
                                <button class="ml-auto rounded-lg bg-neutral-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-neutral-700">Save</button>
                            </div>
                        </div>
                    </form>
                    <div class="mt-3 flex justify-end border-t border-neutral-100 pt-3">
                        <form method="POST" action="{{ route('admin.my-bot.knowledge.destroy', $entry) }}"
                              onsubmit="return confirm('Delete this entry?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete entry</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-400">
                    Knowledge base is empty — add entries on the right.
                </div>
            @endforelse
        </div>

        <div class="min-w-0">
            <form method="POST" action="{{ route('admin.my-bot.knowledge.store') }}"
                  class="rounded-xl border border-neutral-200 bg-white p-5 lg:sticky lg:top-24">
                @csrf
                <h3 class="mb-4 text-sm font-semibold">Add entry</h3>
                <div class="space-y-3">
                    <input name="title" placeholder="e.g. Pricing — Monthly plan" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <textarea name="content" rows="5" placeholder="Full details the bot should know…" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
                    <select name="type" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        <option value="text">General</option>
                        <option value="faq">FAQ</option>
                        <option value="product">Product</option>
                        <option value="service">Service</option>
                        <option value="policy">Policy</option>
                    </select>
                    <input type="hidden" name="is_active" value="1">
                    <button class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">Add to knowledge base</button>
                </div>
            </form>
        </div>
    </div>
@endsection
