@extends('layouts.app')

@section('title', 'Bot & branding')
@section('heading', 'Bot & branding')

@section('content')
    <form method="POST" action="{{ route('crm.bot.update') }}" enctype="multipart/form-data" class="max-w-2xl rounded-xl border border-neutral-200 bg-white p-6">
        @csrf
        @method('PUT')

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
                <p class="mt-1 text-xs text-neutral-500">Square image, at least 256×256 recommended.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Bot name</label>
                <input name="bot_name" value="{{ old('bot_name', $user->bot_name) }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                @error('bot_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Accent color</label>
                <input name="bot_color" type="color" value="{{ old('bot_color', $user->bot_color) }}" class="h-10 w-full rounded-lg border border-neutral-300">
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
                <textarea name="knowledge_base" rows="5" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                          placeholder="Company description, tone of voice, opening hours…">{{ old('knowledge_base', $user->knowledge_base) }}</textarea>
            </div>
        </div>

        <button class="mt-6 rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
            Save bot profile
        </button>
    </form>
@endsection
