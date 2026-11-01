<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\PaymentGatewayAccount;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantAccountTest extends TestCase
{
    use RefreshDatabase;

    private function stripeAccount(): PaymentGatewayAccount
    {
        return PaymentGatewayAccount::query()->create([
            'gateway' => PaymentGatewayAccount::GATEWAY_STRIPE,
            'name' => 'Stripe Sandbox',
            'mode' => PaymentGatewayAccount::MODE_SANDBOX,
            'credentials' => ['secret_key' => 'sk_test_x', 'publishable_key' => 'pk_test_x'],
            'is_active' => true,
        ]);
    }

    private function clientWithPricing(array $overrides = []): User
    {
        return User::factory()->unpaid()->create(array_merge([
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ], $overrides));
    }

    public function test_only_one_merchant_account_can_be_active(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $first = $this->stripeAccount();
        $second = PaymentGatewayAccount::query()->create([
            'gateway' => PaymentGatewayAccount::GATEWAY_PAYPAL,
            'name' => 'PayPal Sandbox',
            'mode' => PaymentGatewayAccount::MODE_SANDBOX,
            'credentials' => ['client_id' => 'x', 'client_secret' => 'y'],
            'is_active' => false,
        ]);

        $this->assertSame($first->id, PaymentGatewayAccount::getActive()->id);

        $this->actingAs($admin)
            ->post(route('admin.merchants.toggle', $second))
            ->assertRedirect();

        $this->assertSame($second->id, PaymentGatewayAccount::getActive()->id);
        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_toggling_active_account_deactivates_it(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $account = $this->stripeAccount();

        $this->actingAs($admin)
            ->post(route('admin.merchants.toggle', $account))
            ->assertRedirect();

        $this->assertFalse($account->fresh()->is_active);
        $this->assertNull(PaymentGatewayAccount::getActive());
    }

    public function test_payment_link_uses_clients_assigned_gateway(): void
    {
        $platform = $this->stripeAccount();
        $clientGateway = PaymentGatewayAccount::query()->create([
            'gateway' => PaymentGatewayAccount::GATEWAY_PAYPAL,
            'name' => 'PayPal for Acme',
            'mode' => PaymentGatewayAccount::MODE_SANDBOX,
            'credentials' => ['client_id' => 'x', 'client_secret' => 'y'],
            'is_active' => false,
        ]);

        $user = $this->clientWithPricing(['payment_gateway_account_id' => $clientGateway->id]);
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        // Client-specific gateway wins over the platform-active one.
        $this->assertSame('paypal', $payment->gateway);
        $this->assertSame($clientGateway->id, $payment->gateway_account_id);
        $this->assertSame('sandbox', $payment->mode);
    }

    public function test_payment_link_falls_back_to_platform_active_gateway(): void
    {
        $this->stripeAccount();

        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->assertSame('stripe', $payment->gateway);
        $this->assertNotNull($payment->gateway_account_id);
    }

    public function test_payment_page_shows_active_gateway_button(): void
    {
        $this->stripeAccount();

        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->get(route('payment.show', $payment->reference))
            ->assertOk()
            ->assertSee('with Stripe');
    }

    public function test_order_gets_payment_reference_and_public_link(): void
    {
        $this->stripeAccount();

        $admin = User::factory()->superAdmin()->create();
        $client = Client::query()->create([
            'user_id' => $admin->id, 'name' => 'Buyer', 'email' => 'buyer@example.com', 'is_read' => false,
        ]);
        $conversation = Conversation::query()->create([
            'user_id' => $admin->id, 'client_id' => $client->id,
            'session_id' => 's1', 'messages_count' => 2, 'last_activity_at' => now(),
        ]);

        $order = app(InvoiceService::class)->createForClient(
            $admin, $client, $conversation,
            ['order_total' => 500, 'items' => [['name' => 'Design', 'qty' => 1, 'price' => 500]]],
        );

        $this->assertNotNull($order->payment_reference);
        $this->assertSame('unpaid', $order->payment_status);

        $this->get(route('order.payment.show', $order->payment_reference))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('with Stripe');
    }

    public function test_admin_can_delete_payment_and_totals_recalculate(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = $this->clientWithPricing();

        $payment = Payment::query()->create([
            'user_id' => $user->id, 'label' => 'Monthly', 'amount' => 100,
            'currency' => 'USD', 'status' => Payment::STATUS_PAID,
            'reference' => Payment::generateReference(), 'paid_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk()->assertSee('100.00');

        $this->actingAs($admin)
            ->delete(route('admin.payments.destroy', $payment))
            ->assertRedirect();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertSame(0.0, (float) Payment::query()->where('status', 'paid')->sum('amount'));
    }

    public function test_merchant_crud_requires_super_admin(): void
    {
        $user = User::factory()->paid()->create();

        $this->actingAs($user)->get(route('admin.merchants.index'))->assertForbidden();
    }
}
