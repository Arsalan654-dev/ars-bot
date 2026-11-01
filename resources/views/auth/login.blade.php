<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in — {{ \App\Models\Setting::get('crm_brand_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full items-center justify-center bg-neutral-950 px-4">
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <img src="{{ asset('assets/images/logo.png') }}?v=3" alt="" class="mx-auto mb-4 h-14 w-14 object-contain">
            <h1 class="text-xl font-semibold text-white">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-neutral-400">Manage your assistant, clients and orders.</p>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-900/50 bg-red-950/60 px-4 py-3 text-sm text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded-2xl bg-white p-6 shadow-xl">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-neutral-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-neutral-700">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="remember" class="rounded border-neutral-300">
                Remember me
            </label>
            <button class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-700">
                Log in
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-neutral-500">
            Access is provisioned by the platform owner. Check your email for credentials.
        </p>
    </div>
</body>
</html>
