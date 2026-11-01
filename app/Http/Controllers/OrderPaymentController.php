<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentGatewayAccount;
use App\Services\PaymentGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public payment pages for invoices (orders created by the bot).
 * The client clicks the link from the invoice email / PDF and pays
 * through the single active merchant account.
 */
class OrderPaymentController extends Controller
{
    public function __construct(private readonly PaymentGatewayService $gateways) {}

    public function show(string $reference): View
    {
        $order = Order::query()
            ->with(['client', 'user'])
            ->where('payment_reference', $reference)
            ->firstOrFail();

        return view('orders.pay', [
            'order' => $order,
            'gatewayAccount' => $this->gateways->activeAccount(),
        ]);
    }

    /**
     * Start the gateway checkout for this order.
     */
    public function pay(string $reference): RedirectResponse
    {
        $order = Order::query()->with('client')->where('payment_reference', $reference)->firstOrFail();

        if ($order->isPaid()) {
            return redirect()->route('order.payment.show', $order->payment_reference);
        }

        $account = $this->gateways->activeAccount();

        if ($account === null) {
            return back()->with('error', 'No active payment method is configured. Please contact us.');
        }

        $result = $this->gateways->startOrderCheckout($order, $account);

        if ($result['type'] === 'bank') {
            return redirect()->route('order.payment.bank', $order->payment_reference);
        }

        if ($result['type'] === 'redirect' && $result['redirect']) {
            return redirect()->away($result['redirect']);
        }

        return back()->with('error', $result['message'] ?? 'Payment could not be started.');
    }

    /**
     * Bank transfer instructions for the order.
     */
    public function bank(string $reference): View
    {
        $order = Order::query()->with('client')->where('payment_reference', $reference)->firstOrFail();

        return view('orders.bank', [
            'order' => $order,
            'account' => $this->gateways->activeAccount(),
        ]);
    }

    public function bankClaim(string $reference): RedirectResponse
    {
        $order = Order::query()->where('payment_reference', $reference)->firstOrFail();

        $order->update([
            'payment_status' => $order->payment_status === 'paid' ? 'paid' : 'unpaid',
        ]);

        return redirect()
            ->route('order.payment.show', $order->payment_reference)
            ->with('status', 'Thanks! We will confirm your bank transfer shortly.');
    }

    /**
     * Gateway return URL for order payments.
     */
    public function return(Request $request, string $reference): RedirectResponse
    {
        $order = Order::query()->where('payment_reference', $reference)->firstOrFail();

        if ($order->isPaid()) {
            return redirect()->route('order.payment.show', $order->payment_reference);
        }

        $account = $this->gateways->activeAccount();

        if ($account !== null && $account->gateway === PaymentGatewayAccount::GATEWAY_STRIPE) {
            $sessionId = (string) $request->query('session_id', '');

            if ($sessionId !== '' && $this->gateways->verifyStripeSession($account, $sessionId)) {
                $this->markOrderPaid($order, $sessionId);

                return redirect()->route('order.payment.show', $order->payment_reference)
                    ->with('status', 'Payment received. Thank you!');
            }
        }

        if ($account !== null && $account->gateway === PaymentGatewayAccount::GATEWAY_PAYPAL) {
            $paypalOrderId = (string) $request->query('token', '');

            if ($paypalOrderId !== '' && $this->gateways->capturePaypalOrder($account, $paypalOrderId)) {
                $this->markOrderPaid($order, $paypalOrderId);

                return redirect()->route('order.payment.show', $order->payment_reference)
                    ->with('status', 'Payment received. Thank you!');
            }
        }

        return redirect()->route('order.payment.show', $order->payment_reference)
            ->with('error', 'We could not confirm the payment yet. If you were charged, contact us.');
    }

    private function markOrderPaid(Order $order, ?string $gatewayReference = null): void
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        // Keep the invoice in sync for the bot owner's CRM.
        $order->client?->update([
            'order_confirmed' => true,
        ]);
    }
}
