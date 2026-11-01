<?php

namespace Tests\Feature;

use App\Models\PageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_logs_into_admin_dashboard_without_payment(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_unpaid_user_is_redirected_to_payment_required(): void
    {
        $user = User::factory()->unpaid()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('crm.payment-required'));
    }

    public function test_paid_user_reaches_crm_dashboard(): void
    {
        $user = User::factory()->paid()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('crm.dashboard'));
    }

    public function test_paid_user_crm_dashboard_renders(): void
    {
        $user = User::factory()->paid()->create();

        $this->actingAs($user)
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('Total clients')
            ->assertSee(Str::limit($user->api_key, 18));
    }

    public function test_crm_pages_render_for_paid_user(): void
    {
        $user = User::factory()->paid()->create();

        $this->actingAs($user)->get(route('crm.clients.index'))->assertOk();
        $this->actingAs($user)->get(route('crm.knowledge.index'))->assertOk();
        $this->actingAs($user)->get(route('crm.bot.edit'))->assertOk();
        $this->actingAs($user)->get(route('crm.bot.embed'))->assertOk();
        $this->actingAs($user)->get(route('crm.billing.index'))->assertOk();
    }

    public function test_admin_pages_render_for_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.clients.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk();
    }

    public function test_public_pages_render_with_dynamic_content(): void
    {
        $admin = User::factory()->superAdmin()->create();

        PageSection::query()->create([
            'page' => 'work',
            'title' => 'Test Project',
            'subtitle' => 'Branding',
            'content' => 'A test project section.',
            'is_active' => true,
        ]);

        $this->get(route('pages.work'))->assertOk()->assertSee('Test Project');
        $this->get(route('pages.agency'))->assertOk();
        $this->get(route('pages.careers'))->assertOk();
    }

    public function test_inactive_page_sections_are_hidden(): void
    {
        $admin = User::factory()->superAdmin()->create();

        PageSection::query()->create([
            'page' => 'work',
            'title' => 'Hidden Project',
            'is_active' => false,
        ]);

        $this->get(route('pages.work'))->assertOk()->assertDontSee('Hidden Project');
    }

    public function test_unpaid_user_cannot_access_crm_dashboard(): void
    {
        $user = User::factory()->unpaid()->create([
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ]);

        $response = $this->actingAs($user)->get(route('crm.dashboard'));

        $payment = $user->payments()->firstOrFail();
        $response->assertRedirect(route('payment.show', $payment->reference));
    }

    public function test_super_admin_is_redirected_from_customer_crm_to_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('crm.dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_regular_user_cannot_access_admin_area(): void
    {
        $user = User::factory()->paid()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_client_login_ignores_stale_admin_intended_url(): void
    {
        $user = User::factory()->paid()->create();

        // Simulate: guest visited an admin URL before logging in,
        // so Laravel stored it as the "intended" URL.
        $response = $this
            ->withSession(['url.intended' => url('/admin/users')])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        // Client must land in the CRM — never on an admin URL.
        $response->assertRedirect(route('crm.dashboard'));
    }

    public function test_client_login_honours_crm_intended_url(): void
    {
        $user = User::factory()->paid()->create();

        $response = $this
            ->withSession(['url.intended' => url('/crm/clients')])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(url('/crm/clients'));
    }

    public function test_super_admin_login_honours_admin_intended_url(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this
            ->withSession(['url.intended' => url('/admin/users')])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $response->assertRedirect(url('/admin/users'));
    }
}
