<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM') — {{ \App\Models\Setting::get('crm_brand_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-neutral-100 font-sans text-neutral-900 antialiased">

<div class="flex min-h-full">
    {{-- ============ Sidebar ============ --}}
    <aside class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col border-r border-slate-800 bg-[#0F172A] text-slate-100 transition-transform lg:translate-x-0" id="sidebar">
        <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
            @php
                $logo = \App\Models\Setting::get('crm_logo_path');
            @endphp
            @if ($logo)
                <img src="{{ asset('storage/'.$logo) }}" alt="Logo" class="h-7 w-7 rounded object-cover">
            @else
                <img src="{{ asset('assets/images/logo.png') }}?v=3" alt="ARS Bot" class="h-7 w-7 object-contain">
            @endif
            <span class="truncate text-sm font-semibold text-white">
                {{ \App\Models\Setting::get('crm_brand_name', config('app.name')) }}
            </span>
            @if (auth()->user()->isSuperAdmin())
                <span class="ml-auto rounded bg-[#22C55E] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#0F172A]">Admin</span>
            @endif
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3 text-sm">
            @if (auth()->user()->isSuperAdmin())
                <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-link>
                <x-nav-link :href="route('admin.my-bot.index')" :active="request()->routeIs('admin.my-bot.*')">My bot</x-nav-link>
                <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Users</x-nav-link>
                <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">
                    Clients
                    @php
                        $unread = \App\Models\Client::where('is_read', false)->count();
                    @endphp
                    @if ($unread > 0)
                        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                    @endif
                </x-nav-link>
                <x-nav-link :href="route('admin.payments.index')" :active="request()->routeIs('admin.payments.*')">Payments</x-nav-link>
                <x-nav-link :href="route('admin.merchants.index')" :active="request()->routeIs('admin.merchants.*')">
                    Merchants
                    @php
                        $activeMerchant = \App\Models\PaymentGatewayAccount::getActive();
                    @endphp
                    @if ($activeMerchant === null)
                        <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-900">!</span>
                    @endif
                </x-nav-link>
                <x-nav-link :href="route('admin.pages.index')" :active="request()->routeIs('admin.pages.*')">Page content</x-nav-link>
                <x-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">Settings</x-nav-link>
            @else
                <x-nav-link :href="route('crm.dashboard')" :active="request()->routeIs('crm.dashboard')">Dashboard</x-nav-link>
                <x-nav-link :href="route('crm.clients.index')" :active="request()->routeIs('crm.clients.*')">
                    Clients
                    @php
                        $unread = auth()->user()->clients()->where('is_read', false)->count();
                    @endphp
                    @if ($unread > 0)
                        <span class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                    @endif
                </x-nav-link>
                <x-nav-link :href="route('crm.bot.edit')" :active="request()->routeIs('crm.bot.edit')">Bot &amp; branding</x-nav-link>
                <x-nav-link :href="route('crm.knowledge.index')" :active="request()->routeIs('crm.knowledge.*')">Knowledge base</x-nav-link>
                <x-nav-link :href="route('crm.bot.embed')" :active="request()->routeIs('crm.bot.embed')">API &amp; embed</x-nav-link>
                <x-nav-link :href="route('crm.billing.index')" :active="request()->routeIs('crm.billing.*')">Billing</x-nav-link>
            @endif
        </nav>

        <div class="border-t border-white/10 p-3 text-sm">
            <div class="mb-2 truncate px-2 text-xs text-slate-400">{{ auth()->user()->email }}</div>
            @if (! auth()->user()->isSuperAdmin())
                @if (auth()->user()->hasActiveSubscription())
                    <div class="mx-2 mb-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-medium text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        {{ ucfirst(auth()->user()->subscription_plan ?? 'paid') }} · renews {{ auth()->user()->subscription_ends_at?->format('M j') }}
                    </div>
                @else
                    <div class="mx-2 mb-2 inline-flex items-center gap-1 rounded-full bg-red-500/15 px-2 py-0.5 text-[11px] font-medium text-red-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Not paid
                    </div>
                @endif
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-lg px-2 py-1.5 text-left text-slate-300 hover:bg-white/10 hover:text-white">Log out</button>
            </form>
        </div>
    </aside>

    <div class="fixed inset-0 z-20 hidden bg-black/40 lg:hidden" id="sidebar-backdrop"></div>

    {{-- ============ Main ============ --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-10 flex h-16 items-center gap-3 border-b border-neutral-200 bg-white/80 px-4 backdrop-blur lg:px-8">
            <button class="rounded-lg border border-neutral-200 p-2 lg:hidden" id="sidebar-toggle" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <h1 class="truncate text-base font-semibold">@yield('heading', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-2">
                @yield('actions')
            </div>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 lg:mx-8">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 lg:mx-8">
                {{ session('error') }}
            </div>
        @endif

        <main class="flex-1 p-4 lg:p-8">
            @yield('content')
        </main>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggle = document.getElementById('sidebar-toggle');
    const close = () => { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); };
    toggle?.addEventListener('click', () => { sidebar.classList.toggle('-translate-x-full'); backdrop.classList.toggle('hidden'); });
    backdrop?.addEventListener('click', close);
</script>
@stack('scripts')
</body>
</html>
