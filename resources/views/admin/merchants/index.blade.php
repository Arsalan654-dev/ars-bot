@extends('layouts.app')

@section('title', 'Merchant accounts')
@section('heading', 'Merchant accounts')

@section('content')
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        <strong>Ek waqt mein sirf ek account active hota hai.</strong>
        Jo account active hoga, saare payment links (subscriptions + invoices) usi gateway se process honge.
        @if ($active)
            <div class="mt-2">
                Active now: <strong>{{ $active->name }}</strong>
                <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700">{{ $active->label() }}</span>
            </div>
        @else
            <div class="mt-2 text-amber-700">⚠ No merchant account is active — payment links will fail. Activate one below.</div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Existing accounts --}}
        <div class="min-w-0 space-y-4 lg:col-span-2">
            @forelse ($accounts as $account)
                <div class="rounded-xl border bg-white p-4 sm:p-5 {{ $account->is_active ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-neutral-200' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold">{{ $account->name }}</span>
                                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium capitalize">{{ $account->gateway }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $account->isSandbox() ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">
                                    {{ $account->mode }}
                                </span>
                                @if ($account->is_active)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">● Active</span>
                                @endif
                            </div>
                            <div class="mt-2 text-xs text-neutral-500">
                                @if ($account->gateway === 'stripe')
                                    Key: {{ \Illuminate\Support\Str::limit($account->getCred('secret_key', '—'), 18) }}
                                @elseif ($account->gateway === 'paypal')
                                    Client: {{ \Illuminate\Support\Str::limit($account->getCred('client_id', '—'), 18) }}
                                @else
                                    {{ $account->getCred('bank_name', '—') }} · {{ $account->getCred('account_number', '—') }}
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.merchants.toggle', $account) }}">
                                @csrf
                                <button class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $account->is_active ? 'border border-neutral-300 text-neutral-600 hover:bg-neutral-50' : 'bg-emerald-600 text-white hover:bg-emerald-500' }}">
                                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.merchants.destroy', $account) }}"
                                  onsubmit="return confirm('Delete this merchant account?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-100">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Edit form --}}
                    <details class="mt-4">
                        <summary class="cursor-pointer text-xs font-medium text-neutral-500 hover:text-neutral-900">Edit details</summary>
                        <form method="POST" action="{{ route('admin.merchants.update', $account) }}" class="mt-3 space-y-3">
                            @csrf
                            @method('PUT')
                            @include('admin.merchants._fields', ['account' => $account, 'prefix' => 'edit-'.$account->id])
                            <button class="rounded-lg bg-neutral-900 px-4 py-1.5 text-xs font-semibold text-white hover:bg-neutral-700">Save</button>
                        </form>
                    </details>
                </div>
            @empty
                <div class="rounded-xl border border-neutral-200 bg-white p-10 text-center text-sm text-neutral-400">
                    No merchant accounts yet — add Stripe, PayPal or a bank account on the right.
                </div>
            @endforelse
        </div>

        {{-- Create account --}}
        <div class="min-w-0">
            <form method="POST" action="{{ route('admin.merchants.store') }}" class="rounded-xl border border-neutral-200 bg-white p-5 lg:sticky lg:top-24"
                  x-data="{ gateway: 'stripe' }">
                @csrf
                <h3 class="mb-4 text-sm font-semibold">Add merchant account</h3>

                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500">Gateway</label>
                        <select name="gateway" x-model="gateway" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank">Bank account</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500">Account name</label>
                        <input name="name" placeholder="e.g. Stripe Sandbox - Global" required
                               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500">Mode</label>
                        <select name="mode" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <option value="sandbox">Sandbox (test)</option>
                            <option value="live">Live</option>
                        </select>
                    </div>

                    {{-- Stripe fields --}}
                    <template x-if="gateway === 'stripe'">
                        <div class="space-y-3">
                            <input name="publishable_key" placeholder="Publishable key (pk_…)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="secret_key" placeholder="Secret key (sk_…)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        </div>
                    </template>

                    {{-- PayPal fields --}}
                    <template x-if="gateway === 'paypal'">
                        <div class="space-y-3">
                            <input name="client_id" placeholder="Client ID" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="client_secret" placeholder="Client secret" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                        </div>
                    </template>

                    {{-- Bank fields --}}
                    <template x-if="gateway === 'bank'">
                        <div class="space-y-3">
                            <input name="bank_name" placeholder="Bank name" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="account_title" placeholder="Account title" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="account_number" placeholder="Account number" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="iban" placeholder="IBAN (optional)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <input name="swift" placeholder="SWIFT / BIC (optional)" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                            <textarea name="instructions" rows="2" placeholder="Extra instructions for the customer…" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"></textarea>
                        </div>
                    </template>

                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-neutral-300">
                        Make this the active gateway
                    </label>

                    <button class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-semibold text-white hover:bg-neutral-700">
                        Add account
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush
@endsection
