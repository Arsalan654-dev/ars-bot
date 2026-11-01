<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentGatewayAccount;
use App\Services\PaymentGatewayService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public payment page reached from the email link.
 * The amount comes from the client's OWN pricing (set by the super
 * admin) and checkout runs through the client's assigned merchant
 * account (falls back to the platform-active one).
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly PaymentGatewayService $gateways,
    ) {}

    public function show(string $reference): View
    {
        $payment = Payment::query()
            ->with(['user', 'gatewayAccount'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('payment.show', [
            'payment' => $payment,
            'gatewayAccount' => $payment->user?->preferredGateway() ?? $this->gateways->activeAccount(),
        ]);
    }

    /**
     * Start the real gateway checkout with the client's merchant account.
     */
    public function pay(string $reference): RedirectResponse
    {
        $payment = Payment::query()->with(['user'])->where('reference', $reference)->firstOrFail();

        if ($payment->isPaid()) {
            return redirect()->route('payment.show', $payment->reference);
        }

        $account = $payment->user?->preferredGateway() ?? $this->gateways->activeAccount();

        if ($account === null) {
            return back()->with('error', 'No payment method is configured for this account. Please contact support.');
        }

        $result = $this->gateways->startCheckout($payment, $account);

        if ($result['type'] === 'bank') {
            return redirect()->route('payment.bank', $payment->reference);
        }

        if ($result['type'] === 'redirect' && $result['redirect']) {
            return redirect()->away($result['redirect']);
        }

        return back()->with('error', $result['message'] ?? 'Payment could not be started.');
    }

    /**
     * Bank transfer instructions page.
     */
    public function bank(string $reference): View
    {
        $payment = Payment::query()->with(['user', 'gatewayAccount'])->where('reference', $reference)->firstOrFail();

        return view('payment.bank', [
            'payment' => $payment,
            'account' => $payment->gatewayAccount ?? $payment->user?->preferredGateway() ?? $this->gateways->activeAccount(),
        ]);
    }

    /**
     * Customer marked "I have paid" on the bank page.
     */
    public function bankClaim(string $reference): RedirectResponse
    {
        $payment = Payment::query()->where('reference', $reference)->firstOrFail();

        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'bank_claimed_at' => now()->toIso8601String(),
            ]),
        ]);

        return redirect()
            ->route('payment.show', $payment->reference)
            ->with('status', 'Thanks! We will confirm your bank transfer shortly and activate your account.');
    }

    /**
     * Gateway return URL (Stripe success_url / PayPal return_url).
     */
    public function return(Request $request, string $reference): RedirectResponse
    {
        $payment = Payment::query()->with(['user', 'gatewayAccount'])->where('reference', $reference)->firstOrFail();

        if ($payment->isPaid()) {
            return redirect()->route('payment.show', $payment->reference);
        }

        $account = $payment->gatewayAccount ?? $payment->user?->preferredGateway() ?? $this->gateways->activeAccount();

        if ($account !== null && $account->gateway === PaymentGatewayAccount::GATEWAY_STRIPE) {
            $sessionId = (string) $request->query('session_id', '');

            if ($sessionId !== '' && $this->gateways->verifyStripeSession($account, $sessionId)) {
                $this->subscriptions->markPaid($payment, $sessionId);

                return redirect()->route('payment.show', $payment->reference)
                    ->with('status', 'Payment confirmed! Your bot is now active.');
            }
        }

        if ($account !== null && $account->gateway === PaymentGatewayAccount::GATEWAY_PAYPAL) {
            $paypalOrderId = (string) $request->query('token', '');

            if ($paypalOrderId !== '' && $this->gateways->capturePaypalOrder($account, $paypalOrderId)) {
                $this->subscriptions->markPaid($payment, $paypalOrderId);

                return redirect()->route('payment.show', $payment->reference)
                    ->with('status', 'Payment confirmed! Your bot is now active.');
            }
        }

        return redirect()->route('payment.show', $payment->reference)
            ->with('error', 'We could not confirm the payment yet. If you were charged, contact support.');
    }

    /**
     * Manual confirmation (local/testing only).
     */
    public function confirm(string $reference): RedirectResponse
    {
        $payment = Payment::query()->where('reference', $reference)->firstOrFail();

        $this->subscriptions->markPaid($payment, 'simulated_'.now()->timestamp);

        return redirect()
            ->route('payment.show', $payment->reference)
            ->with('status', 'Payment confirmed! Your bot is now active.');
    }
}
