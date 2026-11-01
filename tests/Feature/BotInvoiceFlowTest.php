<?php

namespace Tests\Feature;

use App\Mail\ClientInvoiceMail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BotInvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_order_asks_for_email_then_emails_illustrated_invoice(): void
    {
        $bot = User::factory()->paid()->create();
        Setting::put('ai_api_key', 'test-google-key');
        Mail::fake();
        Storage::fake('local');
        $this->fakeGoogleImageGeneration();
        $this->mockAi([
            'Order confirmed. [ORDER_CONFIRMED]',
            'Thanks, I have sent the invoice.',
            'You are welcome.',
        ], [
            [
                'name' => 'Jordan Client',
                'needs' => 'A modern ecommerce website for a handmade furniture studio.',
                'order_confirmed' => true,
                'order_total' => 1200,
                'items' => [
                    ['name' => 'Website design', 'qty' => 1, 'price' => 800],
                    ['name' => 'Ecommerce setup', 'qty' => 1, 'price' => 400],
                ],
            ],
            ['summary' => 'Confirmed ecommerce website project.'],
            [
                'name' => 'Jordan Client',
                'email' => 'jordan@example.com',
                'needs' => 'A modern ecommerce website for a handmade furniture studio.',
                'order_confirmed' => true,
                'order_total' => 1200,
                'items' => [
                    ['name' => 'Website design', 'qty' => 1, 'price' => 800],
                    ['name' => 'Ecommerce setup', 'qty' => 1, 'price' => 400],
                ],
            ],
            ['summary' => 'Confirmed ecommerce website project.'],
            [
                'email' => 'jordan@example.com',
                'order_confirmed' => true,
            ],
            ['summary' => 'Invoice sent for confirmed ecommerce website project.'],
        ]);

        $firstResponse = $this->postJson('/api/bot/chat', [
            'session_id' => 'invoice-flow-session',
            'message' => 'I confirm the order.',
        ], ['X-Api-Key' => $bot->api_key]);

        $firstResponse->assertOk()
            ->assertJsonPath('order_confirmed', true)
            ->assertJsonPath('invoice_sent', false);
        $this->assertStringContainsString('What email address should I send your invoice to?', $firstResponse->json('reply'));

        Mail::assertNotSent(ClientInvoiceMail::class);
        $this->assertDatabaseCount('orders', 1);

        $order = Order::query()->firstOrFail();
        $this->assertNotNull($order->invoice_image_path);
        $this->assertNotNull($order->invoice_path);
        Storage::disk('local')->assertExists($order->invoice_image_path);
        Storage::disk('local')->assertExists($order->invoice_path);

        $conversation = Conversation::query()->firstOrFail();
        $orderMessage = $conversation->messages()->where('role', 'assistant')->oldest('id')->firstOrFail();
        $this->assertSame($order->id, $orderMessage->meta['order_id']);
        $this->assertTrue($orderMessage->meta['invoice_artwork']);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($order->invoice_path));
        $this->actingAs($bot)
            ->get(route('crm.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('AI-generated invoice artwork');

        $secondResponse = $this->postJson('/api/bot/chat', [
            'session_id' => 'invoice-flow-session',
            'message' => 'Please send it to jordan@example.com.',
        ], ['X-Api-Key' => $bot->api_key]);

        $secondResponse->assertOk()
            ->assertJsonPath('order_confirmed', false)
            ->assertJsonPath('invoice_sent', true)
            ->assertJsonPath('order_number', $order->number);

        $this->postJson('/api/bot/chat', [
            'session_id' => 'invoice-flow-session',
            'message' => 'Thanks!',
        ], ['X-Api-Key' => $bot->api_key])
            ->assertOk()
            ->assertJsonPath('invoice_sent', false);

        $this->assertDatabaseCount('orders', 1);
        $this->assertNotNull($order->fresh()->invoice_sent_at);
        Mail::assertSent(ClientInvoiceMail::class, fn (ClientInvoiceMail $mail): bool => $mail->order->is($order));
        $this->assertCount(1, Mail::sent(ClientInvoiceMail::class));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/interactions'
            && $request->hasHeader('x-goog-api-key', 'test-google-key')
            && $request['model'] === 'gemini-nano-banana-2.1');
    }

    public function test_confirmed_order_with_email_sends_invoice_immediately(): void
    {
        $bot = User::factory()->paid()->create();
        Setting::put('ai_api_key', 'test-google-key');
        Mail::fake();
        Storage::fake('local');
        $this->fakeGoogleImageGeneration();
        $this->mockAi([
            'Order confirmed. [ORDER_CONFIRMED]',
        ], [
            [
                'name' => 'Jordan Client',
                'email' => 'jordan@example.com',
                'needs' => 'A polished brand identity for a coffee shop.',
                'order_confirmed' => true,
                'order_total' => 500,
                'items' => [['name' => 'Brand identity', 'qty' => 1, 'price' => 500]],
            ],
            ['summary' => 'Confirmed brand identity project.'],
        ]);

        $response = $this->postJson('/api/bot/chat', [
            'session_id' => 'invoice-ready-session',
            'message' => 'Order confirmed; email me at jordan@example.com.',
        ], ['X-Api-Key' => $bot->api_key]);

        $response->assertOk()
            ->assertJsonPath('order_confirmed', true)
            ->assertJsonPath('invoice_sent', true);

        $order = Order::query()->firstOrFail();
        $this->assertNotNull($order->invoice_sent_at);
        Mail::assertSent(ClientInvoiceMail::class, fn (ClientInvoiceMail $mail): bool => $mail->order->is($order));
    }

    public function test_invoice_artwork_is_not_visible_to_another_crm_owner(): void
    {
        $owner = User::factory()->paid()->create();
        $otherOwner = User::factory()->paid()->create();
        Storage::fake('local');
        Storage::disk('local')->put('invoices/artwork/order.png', 'private artwork');

        $order = Order::query()->create([
            'user_id' => $owner->id,
            'number' => 'INV-20261015-TEST1',
            'invoice_image_path' => 'invoices/artwork/order.png',
            'total' => 100,
            'currency' => 'USD',
            'status' => Order::STATUS_CONFIRMED,
        ]);

        $this->actingAs($owner)
            ->get(route('crm.orders.artwork', $order))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($otherOwner)
            ->get(route('crm.orders.artwork', $order))
            ->assertForbidden();
    }

    public function test_invoice_still_sends_without_artwork_when_image_generation_fails(): void
    {
        $bot = User::factory()->paid()->create();
        Setting::put('ai_api_key', 'test-google-key');
        Mail::fake();
        Storage::fake('local');
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/interactions' => Http::response(['error' => 'model unavailable'], 503),
        ]);
        $this->mockAi([
            'Order confirmed. [ORDER_CONFIRMED]',
        ], [
            [
                'needs' => 'A new booking website for a local hotel.',
                'order_confirmed' => true,
                'order_total' => 900,
                'items' => [['name' => 'Booking website', 'qty' => 1, 'price' => 900]],
            ],
            ['summary' => 'Confirmed hotel booking website.'],
        ]);

        $response = $this->postJson('/api/bot/chat', [
            'session_id' => 'invoice-image-failure',
            'message' => 'I confirm the order; send it to jordan@example.com.',
        ], ['X-Api-Key' => $bot->api_key]);

        $response->assertOk()
            ->assertJsonPath('order_confirmed', true)
            ->assertJsonPath('invoice_sent', true);
        $this->assertStringContainsString('has been sent to jordan@example.com', $response->json('reply'));
        $this->assertStringNotContainsString('could not prepare or send the illustrated invoice', $response->json('reply'));

        $order = Order::query()->firstOrFail();
        $this->assertNull($order->invoice_image_path);
        $this->assertNotNull($order->invoice_sent_at);
        Storage::disk('local')->assertExists($order->invoice_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($order->invoice_path));

        $assistantMessage = Message::query()->where('role', 'assistant')->firstOrFail();
        $this->assertFalse($assistantMessage->meta['invoice_failed']);
        $this->assertFalse($assistantMessage->meta['invoice_artwork']);

        Mail::assertSent(ClientInvoiceMail::class, fn (ClientInvoiceMail $mail): bool => $mail->order->is($order));
    }

    /**
     * @param  array<int, string>  $replies
     * @param  array<int, array<string, mixed>>  $structuredData
     */
    private function mockAi(array $replies, array $structuredData): void
    {
        $ai = $this->mock(AiService::class);
        $ai->shouldReceive('chat')
            ->times(count($replies))
            ->andReturn(...array_map(fn (string $reply): array => ['content' => $reply, 'raw' => []], $replies));
        $ai->shouldReceive('extractStructured')
            ->times(count($structuredData))
            ->andReturn(...$structuredData);
    }

    private function fakeGoogleImageGeneration(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/interactions' => Http::response([
                'output_image' => [
                    'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
                    'mime_type' => 'image/png',
                ],
            ]),
        ]);
    }
}
