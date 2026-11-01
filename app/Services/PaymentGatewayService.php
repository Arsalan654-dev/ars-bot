<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewayAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Drives checkout through the single ACTIVE merchant account.
 * Supports Stripe (Checkout Sessions), PayPal (Orders API) and
 * manual bank transfers. Sandbox and live modes both supported.
 */
class PaymentGatewayService
{
    public function activeAccount(): ?PaymentGatewayAccount
    {
        return PaymentGatewayAccount::getActive();
    }

    /**
     * Create the gateway checkout for a subscription payment.
     *
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    public function startCheckout(Payment $payment, PaymentGatewayAccount $account): array
    {
        $payment->update([
            'gateway' => $account->gateway,
            'gateway_account_id' => $account->id,
            'mode' => $account->mode,
        ]);

        return match ($account->gateway) {
            PaymentGatewayAccount::GATEWAY_STRIPE => $this->stripeCheckout($payment, $account),
            PaymentGatewayAccount::GATEWAY_PAYPAL => $this->paypalCheckout($payment, $account),
            PaymentGatewayAccount::GATEWAY_BANK => ['type' => 'bank', 'redirect' => null, 'message' => null],
            default => ['type' => 'none', 'redirect' => null, 'message' => 'Unsupported gateway.'],
        };
    }

    /**
     * Create the gateway checkout for an order (invoice payment link).
     *
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    public function startOrderCheckout(Order $order, PaymentGatewayAccount $account): array
    {
        return match ($account->gateway) {
            PaymentGatewayAccount::GATEWAY_STRIPE => $this->stripeOrderCheckout($order, $account),
            PaymentGatewayAccount::GATEWAY_PAYPAL => $this->paypalOrderCheckout($order, $account),
            PaymentGatewayAccount::GATEWAY_BANK => ['type' => 'bank', 'redirect' => null, 'message' => null],
            default => ['type' => 'none', 'redirect' => null, 'message' => 'Unsupported gateway.'],
        };
    }

    // ---------------------------------------------------------
    //  Stripe
    // ---------------------------------------------------------

    /**
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    private function stripeCheckout(Payment $payment, PaymentGatewayAccount $account): array
    {
        $secret = $account->getCred('secret_key');

        if (! $secret) {
            return ['type' => 'none', 'redirect' => null, 'message' => 'Stripe secret key missing.'];
        }

        try {
            $response = Http::asForm()
                ->withToken($secret)
                ->timeout(45)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => route('payment.return', $payment->reference).'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('payment.show', $payment->reference),
                    'customer_email' => $payment->user->email,
                    'metadata[payment_reference]' => $payment->reference,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => strtolower($payment->currency),
                    'line_items[0][price_data][unit_amount]' => (int) round(((float) $payment->amount) * 100),
                    'line_items[0][price_data][product_data][name]' => ($payment->label ?? 'Subscription').' — '.config('app.name'),
                ]);

            if ($response->failed()) {
                Log::warning('Stripe checkout failed', ['status' => $response->status(), 'body' => $response->body()]);

                return ['type' => 'none', 'redirect' => null, 'message' => 'Stripe error: '.$response->json('error.message', 'checkout failed')];
            }

            $url = $response->json('url');
            $sessionId = $response->json('id');

            $payment->update(['gateway_reference' => $sessionId, 'checkout_url' => $url]);

            return ['type' => 'redirect', 'redirect' => $url, 'message' => null];
        } catch (\Throwable $e) {
            Log::warning('Stripe connection error', ['message' => $e->getMessage()]);

            return ['type' => 'none', 'redirect' => null, 'message' => 'Could not reach Stripe.'];
        }
    }

    /**
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    private function stripeOrderCheckout(Order $order, PaymentGatewayAccount $account): array
    {
        $secret = $account->getCred('secret_key');

        if (! $secret) {
            return ['type' => 'none', 'redirect' => null, 'message' => 'Stripe secret key missing.'];
        }

        try {
            $response = Http::asForm()
                ->withToken($secret)
                ->timeout(45)
                ->post('https://api.stripe.com/v1/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => route('order.payment.return', $order->payment_reference).'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('order.payment.show', $order->payment_reference),
                    'customer_email' => $order->client?->email,
                    'metadata[order_number]' => $order->number,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => strtolower($order->currency),
                    'line_items[0][price_data][unit_amount]' => (int) round(((float) $order->total) * 100),
                    'line_items[0][price_data][product_data][name]' => 'Invoice '.$order->number,
                ]);

            if ($response->failed()) {
                Log::warning('Stripe order checkout failed', ['status' => $response->status(), 'body' => $response->body()]);

                return ['type' => 'none', 'redirect' => null, 'message' => 'Stripe error: '.$response->json('error.message', 'checkout failed')];
            }

            $url = $response->json('url');
            $order->update(['payment_status' => 'unpaid']);

            return ['type' => 'redirect', 'redirect' => $url, 'message' => null];
        } catch (\Throwable $e) {
            Log::warning('Stripe connection error', ['message' => $e->getMessage()]);

            return ['type' => 'none', 'redirect' => null, 'message' => 'Could not reach Stripe.'];
        }
    }

    /**
     * Verify a Stripe checkout session after the customer returns.
     */
    public function verifyStripeSession(PaymentGatewayAccount $account, string $sessionId): bool
    {
        $secret = $account->getCred('secret_key');

        if (! $secret) {
            return false;
        }

        try {
            $response = Http::withToken($secret)
                ->timeout(45)
                ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

            return $response->successful() && $response->json('payment_status') === 'paid';
        } catch (\Throwable $e) {
            Log::warning('Stripe verify error', ['message' => $e->getMessage()]);

            return false;
        }
    }

    // ---------------------------------------------------------
    //  PayPal
    // ---------------------------------------------------------

    private function paypalBase(PaymentGatewayAccount $account): string
    {
        return $account->isSandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function paypalToken(PaymentGatewayAccount $account): ?string
    {
        $clientId = $account->getCred('client_id');
        $secret = $account->getCred('client_secret');

        if (! $clientId || ! $secret) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $secret)
                ->timeout(45)
                ->post($this->paypalBase($account).'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            return $response->successful() ? $response->json('access_token') : null;
        } catch (\Throwable $e) {
            Log::warning('PayPal token error', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    private function paypalCheckout(Payment $payment, PaymentGatewayAccount $account): array
    {
        return $this->paypalCreateOrder(
            $account,
            (float) $payment->amount,
            $payment->currency,
            ($payment->label ?? 'Subscription').' — '.config('app.name'),
            route('payment.return', $payment->reference),
            route('payment.show', $payment->reference),
            ['payment_reference' => $payment->reference],
        );
    }

    /**
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    private function paypalOrderCheckout(Order $order, PaymentGatewayAccount $account): array
    {
        return $this->paypalCreateOrder(
            $account,
            (float) $order->total,
            $order->currency,
            'Invoice '.$order->number,
            route('order.payment.return', $order->payment_reference),
            route('order.payment.show', $order->payment_reference),
            ['order_number' => $order->number],
        );
    }

    /**
     * @param  array<string, string>  $custom
     * @return array{type: string, redirect: string|null, message: string|null}
     */
    private function paypalCreateOrder(
        PaymentGatewayAccount $account,
        float $amount,
        string $currency,
        string $description,
        string $returnUrl,
        string $cancelUrl,
        array $custom,
    ): array {
        $token = $this->paypalToken($account);

        if (! $token) {
            return ['type' => 'none', 'redirect' => null, 'message' => 'PayPal credentials invalid.'];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(45)
                ->post($this->paypalBase($account).'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                        'description' => $description,
                        'custom_id' => json_encode($custom),
                    ]],
                    'application_context' => [
                        'brand_name' => config('app.name'),
                        'user_action' => 'PAY_NOW',
                        'return_url' => $returnUrl,
                        'cancel_url' => $cancelUrl,
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('PayPal order failed', ['status' => $response->status(), 'body' => $response->body()]);

                return ['type' => 'none', 'redirect' => null, 'message' => 'PayPal error: checkout failed.'];
            }

            $approve = collect($response->json('links', []))->firstWhere('rel', 'approve')['href'] ?? null;

            return $approve
                ? ['type' => 'redirect', 'redirect' => $approve, 'message' => null]
                : ['type' => 'none', 'redirect' => null, 'message' => 'PayPal approval link missing.'];
        } catch (\Throwable $e) {
            Log::warning('PayPal connection error', ['message' => $e->getMessage()]);

            return ['type' => 'none', 'redirect' => null, 'message' => 'Could not reach PayPal.'];
        }
    }

    /**
     * Capture a PayPal order after approval (called from return URL with ?token=ORDER_ID).
     */
    public function capturePaypalOrder(PaymentGatewayAccount $account, string $paypalOrderId): bool
    {
        $token = $this->paypalToken($account);

        if (! $token) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(45)
                ->withBody('{}', 'application/json')
                ->post($this->paypalBase($account)."/v2/checkout/orders/{$paypalOrderId}/capture");

            return $response->successful() && $response->json('status') === 'COMPLETED';
        } catch (\Throwable $e) {
            Log::warning('PayPal capture error', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
