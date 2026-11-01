<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_toggle_requires_active_subscription(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->unpaid()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-bot', $user))
            ->assertSessionHas('error');

        $this->assertFalse($user->fresh()->bot_active);
    }

    public function test_bot_toggle_activates_when_paid(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->paid()->create(['bot_active' => false]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle-bot', $user))
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->bot_active);
    }

    public function test_mark_paid_activates_user_and_bot(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->unpaid()->create([
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.mark-paid', $user))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue($user->is_paid);
        $this->assertTrue($user->bot_active);
    }

    public function test_admin_can_delete_client_but_user_cannot(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $owner = User::factory()->paid()->create();

        $client = Client::query()->create([
            'user_id' => $owner->id,
            'name' => 'Delete Me',
        ]);

        $this->actingAs($owner)
            ->delete(route('admin.clients.destroy', $client))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.clients.destroy', $client))
            ->assertRedirect(route('admin.clients.index'));

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    public function test_client_notification_badge_counts_unread(): void
    {
        $owner = User::factory()->paid()->create();

        Client::query()->create(['user_id' => $owner->id, 'name' => 'A', 'is_read' => false]);
        Client::query()->create(['user_id' => $owner->id, 'name' => 'B', 'is_read' => false]);
        Client::query()->create(['user_id' => $owner->id, 'name' => 'C', 'is_read' => true]);

        $this->assertSame(2, $owner->unreadClientsCount());
    }

    public function test_settings_require_super_admin(): void
    {
        $user = User::factory()->paid()->create();

        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
    }
}
