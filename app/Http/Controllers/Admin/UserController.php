<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGatewayAccount;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->with('gatewayAccount')
            ->withCount('clients')
            ->withCount(['payments as paid_payments_count' => fn ($query) => $query->where('status', 'paid')])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company', 'like', $term));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'gateways' => PaymentGatewayAccount::query()->latest()->get(),
            'activeGateway' => PaymentGatewayAccount::getActive(),
            'intervalPresets' => SubscriptionService::intervalPresets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'bot_name' => ['nullable', 'string', 'max:60'],
            // Per-client pricing
            'plan_label' => ['required', 'string', 'max:60'],
            'plan_amount' => ['required', 'numeric', 'min:0'],
            'plan_currency' => ['required', 'string', 'max:8'],
            'plan_days' => ['required', 'integer', 'min:1', 'max:3650'],
            // Per-client gateway
            'payment_gateway_account_id' => ['nullable', 'exists:payment_gateway_accounts,id'],
        ]);

        $password = bin2hex(random_bytes(6)); // 12 chars

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($password),
            'role' => User::ROLE_USER,
            'api_key' => User::generateApiKey(),
            'bot_name' => $validated['bot_name'] ?? 'L.I.S.A',
            'bot_active' => false,
            'is_paid' => false,
            'plan_label' => $validated['plan_label'],
            'plan_amount' => $validated['plan_amount'],
            'plan_currency' => strtoupper($validated['plan_currency']),
            'plan_days' => $validated['plan_days'],
            'payment_gateway_account_id' => $validated['payment_gateway_account_id'] ?? null,
        ]);

        // Payment link uses this client's own pricing + chosen gateway.
        $payment = $this->subscriptions->createPaymentLink($user);

        // Email credentials + the client-specific payment link.
        $this->subscriptions->sendCredentialsWithPassword($user, $password, $payment);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User created. Credentials + payment link emailed ('.$user->plan_label.' · '.$user->plan_currency.' '.number_format((float) $user->plan_amount, 2).').');
    }

    public function show(User $user): View
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $user->load(['gatewayAccount', 'clients' => fn ($query) => $query->latest()->limit(20)]);

        return view('admin.users.show', [
            'user' => $user,
            'payments' => $user->payments()->with('gatewayAccount')->latest()->get(),
            'orders' => $user->orders()->with('client')->latest()->limit(20)->get(),
            'pendingPayment' => $user->payments()->where('status', 'pending')->latest()->first(),
        ]);
    }

    public function edit(User $user): View
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        return view('admin.users.edit', [
            'user' => $user,
            'gateways' => PaymentGatewayAccount::query()->latest()->get(),
            'intervalPresets' => SubscriptionService::intervalPresets(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'bot_name' => ['nullable', 'string', 'max:60'],
            // Per-client pricing
            'plan_label' => ['required', 'string', 'max:60'],
            'plan_amount' => ['required', 'numeric', 'min:0'],
            'plan_currency' => ['required', 'string', 'max:8'],
            'plan_days' => ['required', 'integer', 'min:1', 'max:3650'],
            // Per-client gateway
            'payment_gateway_account_id' => ['nullable', 'exists:payment_gateway_accounts,id'],
        ]);

        $validated['plan_currency'] = strtoupper($validated['plan_currency']);

        $user->update($validated);

        // Keep any pending payment link in sync with the new pricing/gateway.
        $pending = $user->payments()->where('status', 'pending')->latest()->first();

        if ($pending !== null && ! $user->is_paid) {
            $gateway = $user->preferredGateway();

            $pending->update([
                'label' => $user->plan_label,
                'amount' => $user->plan_amount,
                'currency' => $user->plan_currency,
                'gateway_account_id' => $gateway?->id,
                'gateway' => $gateway?->gateway,
                'mode' => $gateway?->mode ?? 'sandbox',
            ]);
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'User + pricing updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    /**
     * Toggle bot serving on/off. Only possible when paid.
     */
    public function toggleBot(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        if (! $user->hasActiveSubscription()) {
            return back()->with('error', 'Cannot activate bot: subscription is not paid.');
        }

        $user->update(['bot_active' => ! $user->bot_active]);

        return back()->with('status', $user->bot_active ? 'Bot activated.' : 'Bot deactivated.');
    }

    /**
     * Manually mark a user paid (off-platform payments).
     */
    public function markPaid(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $payment = $user->payments()->where('status', 'pending')->latest()->first()
            ?? $this->subscriptions->createPaymentLink($user);

        $this->subscriptions->markPaid($payment, 'manual_admin');

        return back()->with('status', 'User marked as paid and bot activated.');
    }

    /**
     * Re-issue a payment link + resend the credentials email.
     */
    public function resendCredentials(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $payment = $user->payments()->where('status', 'pending')->latest()->first();

        if ($payment === null) {
            $payment = $this->subscriptions->createPaymentLink($user);
        }

        $this->subscriptions->sendCredentials($user, $payment);

        return back()->with('status', 'Credentials email sent with payment link.');
    }

    /**
     * Regenerate the payment link for this client with their current pricing.
     */
    public function regeneratePaymentLink(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_USER, 404);

        $user->payments()->where('status', 'pending')->update(['status' => Payment::STATUS_FAILED]);

        $payment = $this->subscriptions->createPaymentLink($user);

        return back()->with('status', 'New payment link generated: '.route('payment.show', $payment->reference));
    }
}
