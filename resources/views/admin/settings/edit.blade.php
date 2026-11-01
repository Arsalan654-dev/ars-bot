@extends('layouts.app')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- AI brain settings --}}
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="rounded-xl border border-neutral-200 bg-white p-6">
            @csrf
            @method('PUT')
            <h2 class="text-sm font-semibold">AI brain</h2>
            <p class="mb-4 mt-1 text-xs text-neutral-500">
                The chat model powers every bot. Google AI Studio is also used to create one
                project illustration for each confirmed-order invoice.
            </p>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">API key</label>
                    <input name="ai_api_key" type="password" value="{{ $settings['ai_api_key'] }}"
                           placeholder="sk-…" autocomplete="off"
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                    @error('ai_api_key') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Base URL</label>
                    <input name="ai_base_url" value="{{ $settings['ai_base_url'] }}" required
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Model</label>
                    <input name="ai_model" value="{{ $settings['ai_model'] }}" required
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Google invoice image model</label>
                    <input name="ai_image_model" value="{{ $settings['ai_image_model'] }}" required
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                    <p class="mt-1 text-xs text-neutral-500">Uses the configured Google AI API key. Image generation may incur separate usage charges.</p>
                    @error('ai_image_model') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <h2 class="mt-8 text-sm font-semibold">CRM branding</h2>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Brand name</label>
                    <input name="crm_brand_name" value="{{ $settings['crm_brand_name'] }}" required
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Brand color</label>
                        <input name="crm_brand_color" type="color" value="{{ $settings['crm_brand_color'] }}"
                               class="h-10 w-full rounded-lg border border-neutral-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Logo</label>
                        <input name="crm_logo" type="file" accept="image/*"
                               class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs">
                    </div>
                </div>
                @if ($settings['crm_logo_path'])
                    <img src="{{ asset('storage/'.$settings['crm_logo_path']) }}" class="h-10 rounded" alt="Logo">
                @endif
            </div>

            <button class="mt-6 rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
                Save settings
            </button>
        </form>

        <div class="space-y-6">
            {{-- Own bot --}}
            <form method="POST" action="{{ route('admin.settings.bot') }}" enctype="multipart/form-data" class="rounded-xl border border-neutral-200 bg-white p-6">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-semibold">Your own bot</h2>
                <p class="mb-4 mt-1 text-xs text-neutral-500">The bot served on the platform home page.</p>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Bot name</label>
                            <input name="bot_name" value="{{ auth()->user()->bot_name }}" required
                                   class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Accent color</label>
                            <input name="bot_color" type="color" value="{{ auth()->user()->bot_color }}"
                                   class="h-10 w-full rounded-lg border border-neutral-300">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Theme</label>
                        <select name="bot_theme" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <option value="lisa" @selected(auth()->user()->bot_theme === 'lisa')>Light (LISA)</option>
                            <option value="dark" @selected(auth()->user()->bot_theme === 'dark')>Dark</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Avatar</label>
                        <input name="avatar" type="file" accept="image/*" class="w-full rounded-lg border border-neutral-300 px-2 py-1.5 text-xs">
                        @if (auth()->user()->avatar_path)
                            <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="mt-2 h-12 w-12 rounded-full object-cover" alt="">
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Global knowledge base</label>
                        <textarea name="knowledge_base" rows="5" placeholder="Anything the AI should always know…"
                                  class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ auth()->user()->knowledge_base }}</textarea>
                    </div>
                </div>

                <button class="mt-5 rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
                    Save bot profile
                </button>
            </form>

            {{-- API key --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6">
                <h2 class="text-sm font-semibold">Platform API key</h2>
                <code class="mt-3 block break-all rounded-lg bg-neutral-100 p-3 text-xs">{{ auth()->user()->api_key }}</code>
                <p class="mt-2 text-xs text-neutral-500">Embed snippet:</p>
                <code class="mt-1 block break-all rounded-lg bg-neutral-900 p-3 text-xs text-neutral-100">
                    &lt;script src="{{ url('/widget.js') }}?key={{ auth()->user()->api_key }}"&gt;&lt;/script&gt;
                </code>
                <form method="POST" action="{{ route('admin.settings.api-key') }}" class="mt-3"
                      onsubmit="return confirm('Regenerate API key? Old embeds will stop working.')">
                    @csrf
                    <button class="rounded-lg border border-neutral-300 px-4 py-2 text-sm hover:bg-neutral-50">Regenerate key</button>
                </form>
            </div>
        </div>
    </div>
@endsection
