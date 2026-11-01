<?php

namespace Tests\Feature;

use App\Mail\UserCredentialsMail;
use App\Models\Payment;
use App\Models\PaymentGatewayAccount;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function clientWithPricing(array $overrides = []): User
    {
        return User::factory()->unpaid()->create(array_merge([
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ], $overrides));
    }

    public function test_marking_payment_paid_activates_bot_and_extends_subscription(): void
    {
        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->assertFalse($user->fresh()->hasActiveSubscription());

        app(SubscriptionService::class)->markPaid($payment, 'test_ref');

        $user->refresh();
        $this->assertTrue($payment->fresh()->isPaid());
        $this->assertTrue($user->is_paid);
        $this->assertTrue($user->bot_active);
        $this->assertTrue($user->hasActiveSubscription());
        $this->assertTrue($user->subscription_ends_at->isFuture());
        // Interval comes from the client's own pricing.
        $this->assertSame(30, $user->subscription_days);
    }

    public function test_weekly_client_gets_seven_days(): void
    {
        $user = $this->clientWithPricing(['plan_label' => 'Weekly', 'plan_amount' => 29, 'plan_days' => 7]);
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        app(SubscriptionService::class)->markPaid($payment);

        $user->refresh();
        $this->assertSame(7, $user->subscription_days);
        $this->assertSame('Weekly', $user->subscription_plan);
        $this->assertEqualsWithDelta(7, now()->diffInDays($user->subscription_ends_at, true), 0.1);
    }

    public function test_payment_link_uses_client_pricing_and_label(): void
    {
        $user = $this->clientWithPricing(['plan_label' => 'Gold Monthly', 'plan_amount' => 149.5, 'plan_currency' => 'EUR']);
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->assertSame('Gold Monthly', $payment->label);
        $this->assertSame('149.50', $payment->amount);
        $this->assertSame('EUR', $payment->currency);
    }

    public function test_payment_page_renders_for_valid_reference(): void
    {
        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->get(route('payment.show', $payment->reference))
            ->assertOk()
            ->assertSee('Complete your subscription')
            ->assertSee('79.00');
    }

    public function test_confirming_payment_activates_user(): void
    {
        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->post(route('payment.confirm', $payment->reference))
            ->assertRedirect(route('payment.show', $payment->reference));

        $this->assertTrue($user->fresh()->hasActiveSubscription());
    }

    public function test_expiry_command_disables_bot_for_overdue_subscription(): void
    {
        $user = User::factory()->paid()->create([
            'subscription_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->is_paid);
        $this->assertFalse($user->bot_active);
        $this->assertFalse($user->botIsServing());
    }

    public function test_expiry_command_leaves_active_subscription_alone(): void
    {
        $user = User::factory()->paid()->create();

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertTrue($user->fresh()->botIsServing());
    }

    public function test_creating_user_sends_credentials_with_payment_link(): void
    {
        Mail::fake();

        $admin = User::factory()->superAdmin()->create();
        $gateway = PaymentGatewayAccount::query()->create([
            'gateway' => 'stripe', 'name' => 'Stripe Sandbox', 'mode' => 'sandbox',
            'credentials' => ['secret_key' => 'sk_test_x'], 'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Customer',
            'email' => 'customer@example.com',
            'plan_label' => 'Monthly',
            'plan_amount' => 99,
            'plan_currency' => 'USD',
            'plan_days' => 30,
            'payment_gateway_account_id' => $gateway->id,
        ])->assertRedirect();

        $user = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->assertNotNull($user->api_key);
        $this->assertFalse($user->is_paid);
        $this->assertSame('Monthly', $user->plan_label);
        $this->assertSame('99.00', $user->plan_amount);
        $this->assertSame($gateway->id, $user->payment_gateway_account_id);

        // The payment link carries the client's pricing + gateway.
        $payment = $user->payments()->firstOrFail();
        $this->assertSame('99.00', $payment->amount);
        $this->assertSame('stripe', $payment->gateway);

        Mail::assertSent(UserCredentialsMail::class, fn (UserCredentialsMail $mail): bool => $mail->hasTo('customer@example.com')
            && $mail->payment !== null
            && $mail->plainPassword !== null);
    }

    public function test_renewing_extends_from_current_expiry_when_still_active(): void
    {
        $user = $this->clientWithPricing();
        $user->update([
            'is_paid' => true,
            'bot_active' => true,
            'subscription_ends_at' => now()->addDays(10),
        ]);

        $payment = app(SubscriptionService::class)->createPaymentLink($user);
        app(SubscriptionService::class)->markPaid($payment);

        $user->refresh();
        $this->assertGreaterThanOrEqual(39, (int) now()->diffInDays($user->subscription_ends_at, true));
    }

    public function test_paid_payment_is_idempotent(): void
    {
        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        app(SubscriptionService::class)->markPaid($payment);
        $endsAt = $user->fresh()->subscription_ends_at;

        app(SubscriptionService::class)->markPaid($payment->fresh());

        $this->assertTrue($endsAt->equalTo($user->fresh()->subscription_ends_at));
        $this->assertSame(1, Payment::query()->where('status', Payment::STATUS_PAID)->count());
    }

    public function test_updating_client_pricing_syncs_pending_payment_link(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = $this->clientWithPricing();
        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'plan_label' => 'Weekly',
            'plan_amount' => 39,
            'plan_currency' => 'USD',
            'plan_days' => 7,
        ])->assertRedirect();

        $payment->refresh();
        $this->assertSame('Weekly', $payment->label);
        $this->assertSame('39.00', $payment->amount);
    }
}
