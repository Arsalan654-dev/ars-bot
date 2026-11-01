<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The public bot page (home) renders when the platform bot exists.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        User::factory()->superAdmin()->create();

        $this->get('/')->assertOk();
    }

    /**
     * The home page fails loudly when no platform bot is configured.
     */
    public function test_home_returns_404_without_platform_bot(): void
    {
        $this->get('/')->assertNotFound();
    }

    /**
     * Payment links are generated from the client's own pricing.
     */
    public function test_payment_page_renders_for_valid_reference(): void
    {
        $user = User::factory()->unpaid()->create([
            'plan_label' => 'Weekly',
            'plan_amount' => 29,
            'plan_currency' => 'USD',
            'plan_days' => 7,
        ]);

        $payment = app(SubscriptionService::class)->createPaymentLink($user);

        $this->get(route('payment.show', $payment->reference))
            ->assertOk()
            ->assertSee('29.00');
    }

    /**
     * The old public pricing page is gone.
     */
    public function test_pricing_page_no_longer_exists(): void
    {
        User::factory()->superAdmin()->create();

        $this->get('/pricing')->assertNotFound();
    }
}
