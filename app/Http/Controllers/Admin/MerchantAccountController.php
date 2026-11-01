<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Super admin CRUD for merchant accounts (Stripe / PayPal / Bank).
 * Only ONE account can be active at a time — activating one
 * deactivates all others automatically.
 */
class MerchantAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.merchants.index', [
            'accounts' => PaymentGatewayAccount::query()->latest()->get(),
            'active' => PaymentGatewayAccount::getActive(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $account = PaymentGatewayAccount::query()->create($validated);

        if (! empty($validated['is_active'])) {
            $account->activate();
        }

        return back()->with('status', 'Merchant account added.');
    }

    public function update(Request $request, PaymentGatewayAccount $merchant): RedirectResponse
    {
        $validated = $this->validated($request, $merchant);

        $merchant->update($validated);

        if (! empty($validated['is_active'])) {
            $merchant->activate();
        }

        return back()->with('status', 'Merchant account updated.');
    }

    public function destroy(PaymentGatewayAccount $merchant): RedirectResponse
    {
        $merchant->delete();

        return back()->with('status', 'Merchant account deleted.');
    }

    /**
     * Toggle a merchant account active. Activating one always
     * deactivates every other account.
     */
    public function toggleActive(PaymentGatewayAccount $merchant): RedirectResponse
    {
        if ($merchant->is_active) {
            $merchant->update(['is_active' => false]);

            return back()->with('status', 'Merchant account deactivated.');
        }

        $merchant->activate();

        return back()->with('status', $merchant->name.' is now the active gateway ('.$merchant->label().').');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PaymentGatewayAccount $account = null): array
    {
        $validated = $request->validate([
            'gateway' => ['required', Rule::in(array_keys(PaymentGatewayAccount::GATEWAYS))],
            'name' => ['required', 'string', 'max:120'],
            'mode' => ['required', Rule::in([PaymentGatewayAccount::MODE_SANDBOX, PaymentGatewayAccount::MODE_LIVE])],
            'is_active' => ['boolean'],
            // Stripe
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            // PayPal
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            // Bank
            'bank_name' => ['nullable', 'string', 'max:160'],
            'account_title' => ['nullable', 'string', 'max:160'],
            'account_number' => ['nullable', 'string', 'max:80'],
            'iban' => ['nullable', 'string', 'max:80'],
            'swift' => ['nullable', 'string', 'max:40'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $credentials = collect([
            'publishable_key', 'secret_key', 'client_id', 'client_secret',
            'bank_name', 'account_title', 'account_number', 'iban', 'swift', 'instructions',
        ])
            ->mapWithKeys(fn (string $key): array => [$key => $validated[$key] ?? null])
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->all();

        // Keep existing credentials that were left blank on update.
        if ($account !== null) {
            $credentials = array_merge($account->credentials ?? [], $credentials);
        }

        return [
            'gateway' => $validated['gateway'],
            'name' => $validated['name'],
            'mode' => $validated['mode'],
            'credentials' => $credentials,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
