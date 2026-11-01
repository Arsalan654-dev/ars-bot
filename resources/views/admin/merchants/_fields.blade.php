@php
    /** @var \App\Models\PaymentGatewayAccount $account */
    $prefix = $prefix ?? ('acc-'.$account->id);
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs font-medium text-neutral-500">Gateway</label>
        <select name="gateway" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            @foreach (\App\Models\PaymentGatewayAccount::GATEWAYS as $value => $label)
                <option value="{{ $value }}" @selected($account->gateway === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-neutral-500">Mode</label>
        <select name="mode" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
            <option value="sandbox" @selected($account->isSandbox())>Sandbox (test)</option>
            <option value="live" @selected(! $account->isSandbox())>Live</option>
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs font-medium text-neutral-500">Account name</label>
        <input name="name" value="{{ $account->name }}" required class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
    </div>
</div>

@if ($account->gateway === 'stripe')
    <div class="grid gap-3 sm:grid-cols-2">
        <input name="publishable_key" value="{{ $account->getCred('publishable_key') }}" placeholder="Publishable key (pk_…)"
               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="secret_key" value="" placeholder="Secret key — blank rakho to same rahega"
               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
    </div>
@elseif ($account->gateway === 'paypal')
    <div class="grid gap-3 sm:grid-cols-2">
        <input name="client_id" value="{{ $account->getCred('client_id') }}" placeholder="Client ID"
               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="client_secret" value="" placeholder="Client secret — blank rakho to same rahega"
               class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2">
        <input name="bank_name" value="{{ $account->getCred('bank_name') }}" placeholder="Bank name" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="account_title" value="{{ $account->getCred('account_title') }}" placeholder="Account title" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="account_number" value="{{ $account->getCred('account_number') }}" placeholder="Account number" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="iban" value="{{ $account->getCred('iban') }}" placeholder="IBAN" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <input name="swift" value="{{ $account->getCred('swift') }}" placeholder="SWIFT / BIC" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
        <textarea name="instructions" rows="2" placeholder="Instructions" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">{{ $account->getCred('instructions') }}</textarea>
    </div>
@endif

<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked($account->is_active) class="rounded border-neutral-300">
    Active
</label>
