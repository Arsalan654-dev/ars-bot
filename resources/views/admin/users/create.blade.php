@extends('layouts.app')

@section('title', 'Add user')
@section('heading', 'Add user')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-3xl space-y-6">
        @csrf

        {{-- User details --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="mb-4 text-sm font-semibold">Client details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Name *</label>
                    <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email *</label>
                    <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Company</label>
                    <input name="company" value="{{ old('company') }}" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Phone</label>
                    <input name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Bot name</label>
                    <input name="bot_name" value="{{ old('bot_name', 'ARS Bot') }}" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                </div>
            </div>
        </div>

        {{-- Per-client pricing --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="text-sm font-semibold">Pricing for this client</h2>
            <p class="mb-4 mt-1 text-xs text-neutral-500">
                Har client ki apni pricing — weekly, monthly ya jo tum chaho. Payment link isi pricing se banega.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Plan label *</label>
                    <input name="plan_label" value="{{ old('plan_label', 'Monthly') }}" required list="plan-labels"
                           class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                    <datalist id="plan-labels">
                        @foreach (array_keys($intervalPresets) as $preset)
                            <option value="{{ $preset }}">
                        @endforeach
                    </datalist>
                    @error('plan_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Amount *</label>
                        <input name="plan_amount" type="number" step="0.01" min="0" value="{{ old('plan_amount', '79') }}" required
                               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                        @error('plan_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Currency *</label>
                        <select name="plan_currency" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                            @foreach (['USD', 'EUR', 'GBP', 'PKR', 'AED', 'CAD'] as $currency)
                                <option value="{{ $currency }}" @selected(old('plan_currency', 'USD') === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Billing interval *</label>
                    <select name="plan_days" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                        @foreach ($intervalPresets as $label => $days)
                            <option value="{{ $days }}" @selected((int) old('plan_days', 30) === $days)>{{ $label }} ({{ $days }} days)</option>
                        @endforeach
                    </select>
                    @error('plan_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Per-client gateway --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6">
            <h2 class="text-sm font-semibold">Payment gateway for this client</h2>
            <p class="mb-4 mt-1 text-xs text-neutral-500">
                Is client ki payment kis merchant account se leni hai? Khali chhodo to platform ka active gateway use hoga.
            </p>

            <select name="payment_gateway_account_id" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-neutral-900">
                <option value="">— Platform active gateway —</option>
                @foreach ($gateways as $gateway)
                    <option value="{{ $gateway->id }}" @selected(old('payment_gateway_account_id') == $gateway->id)>
                        {{ $gateway->name }} ({{ $gateway->label() }}){{ $gateway->is_active ? ' — ACTIVE' : '' }}
                    </option>
                @endforeach
            </select>

            @if ($gateways->isEmpty())
                <p class="mt-2 text-xs text-amber-600">⚠ Koi merchant account nahi hai — pehle Admin → Merchants se add karo.</p>
            @endif
        </div>

        <div class="rounded-lg bg-neutral-50 p-4 text-xs text-neutral-500">
            A random password + payment link (is client ki pricing ke saath) email ho jayegi.
            Bot inactive rahega jab tak payment nahi hoti.
        </div>

        <div class="flex gap-3">
            <button class="rounded-lg bg-neutral-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">Create user</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-neutral-300 px-5 py-2.5 text-sm hover:bg-neutral-50">Cancel</a>
        </div>
    </form>
@endsection
