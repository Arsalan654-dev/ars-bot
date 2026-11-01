<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_requires_api_key(): void
    {
        $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-1',
            'message' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_chat_rejects_invalid_api_key(): void
    {
        $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-1',
            'message' => 'Hello',
        ], ['X-Api-Key' => 'lisa_invalid'])
            ->assertUnauthorized();
    }

    public function test_chat_rejects_bot_when_subscription_inactive(): void
    {
        $user = User::factory()->unpaid()->create();

        $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-1',
            'message' => 'Hello',
        ], ['X-Api-Key' => $user->api_key])
            ->assertForbidden()
            ->assertJsonPath('error', 'bot_inactive');
    }

    public function test_chat_creates_conversation_and_returns_reply(): void
    {
        $user = User::factory()->paid()->create();

        $response = $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-42',
            'message' => 'Hello, what do you offer?',
        ], ['X-Api-Key' => $user->api_key]);

        $response->assertOk()
            ->assertJsonStructure(['reply', 'conversation_id', 'order_confirmed']);

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'session_id' => 'sess-42',
        ]);

        $conversation = Conversation::query()->firstOrFail();
        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_chat_reuses_existing_session_conversation(): void
    {
        $user = User::factory()->paid()->create();

        $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-same',
            'message' => 'First message',
        ], ['X-Api-Key' => $user->api_key])->assertOk();

        $this->postJson('/api/bot/chat', [
            'session_id' => 'sess-same',
            'message' => 'Second message',
        ], ['X-Api-Key' => $user->api_key])->assertOk();

        $this->assertSame(1, Conversation::query()->count());
        $this->assertSame(4, Conversation::query()->first()->messages()->count());
    }

    public function test_bot_page_renders_for_valid_key(): void
    {
        $user = User::factory()->paid()->create();

        $this->get(route('bot.page', $user->api_key))->assertOk();
    }

    public function test_widget_loader_marks_invalid_key(): void
    {
        $response = $this->get('/widget.js?key=lisa_missing');

        $response->assertOk();
        $this->assertStringContainsString('"valid":false', $response->getContent());
    }

    public function test_clients_are_only_visible_to_their_owner(): void
    {
        $owner = User::factory()->paid()->create();
        $other = User::factory()->paid()->create();

        $client = Client::query()->create([
            'user_id' => $owner->id,
            'name' => 'Test Client',
            'is_read' => false,
        ]);

        $this->actingAs($other)->get(route('crm.clients.show', $client))->assertForbidden();
        $this->actingAs($owner)->get(route('crm.clients.show', $client))->assertOk();
    }
}
