<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Orchestrates one bot chat turn:
 *  - resolves/creates the conversation + client
 *  - builds the system prompt from bot settings + knowledge base
 *  - calls the AI brain
 *  - persists messages
 *  - extracts structured data (client info / order confirmation)
 *  - generates summary + invoice on order confirmation
 */
class BotChatService
{
    public function __construct(
        private readonly AiService $ai,
        private readonly InvoiceService $invoices,
        private readonly ClientSummaryService $summaries,
    ) {}

    /**
     * @param  array{session_id: string, message: string, page_url?: string, ip?: string, user_agent?: string}  $payload
     * @return array{reply: string, conversation_id: int, order_confirmed: bool, order_number: string|null, invoice_sent: bool}
     */
    public function handle(User $bot, array $payload): array
    {
        $conversation = Conversation::query()->firstOrCreate(
            [
                'user_id' => $bot->id,
                'session_id' => $payload['session_id'],
            ],
            [
                'page_url' => $payload['page_url'] ?? null,
                'ip' => $payload['ip'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'last_activity_at' => now(),
            ],
        );

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $payload['message'],
        ]);

        $history = $this->historyFor($conversation);
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($bot)]],
            $history,
        );

        $result = $this->ai->chat($messages);
        $reply = $result['content'];

        // The confirmation marker is internal — never show it to the visitor.
        $markerConfirmed = str_contains($reply, '[ORDER_CONFIRMED]');
        $reply = trim(str_replace('[ORDER_CONFIRMED]', '', $reply));

        $assistantMessage = Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
        ]);

        $conversation->update([
            'messages_count' => $conversation->messages_count + 2,
            'last_activity_at' => now(),
        ]);

        $extraction = $this->extractClientData($conversation);

        if ($markerConfirmed) {
            $extraction['order_confirmed'] = true;
        }

        $currentEmail = $this->emailIn($payload['message']);
        if ($currentEmail !== null) {
            $extraction['email'] = $currentEmail;
            $extraction['_direct_email'] = true;
        }
        if (! is_string($extraction['email'] ?? null)
            || filter_var($extraction['email'], FILTER_VALIDATE_EMAIL) === false) {
            unset($extraction['email']);
        }

        $client = $this->syncClient($bot, $conversation, $extraction);
        $order = null;
        $orderConfirmed = false;

        if ($client !== null && ($extraction['order_confirmed'] ?? false) === true && ! $conversation->order_confirmed) {
            $order = $this->invoices->createForClient($bot, $client, $conversation, $extraction);
            $conversation->update(['order_confirmed' => true]);
            $orderConfirmed = true;
        } elseif ($client !== null && $conversation->order_confirmed) {
            $order = Order::query()
                ->where('conversation_id', $conversation->id)
                ->where('status', Order::STATUS_CONFIRMED)
                ->whereNull('invoice_sent_at')
                ->latest('id')
                ->first();
        }

        $invoiceSent = false;
        $invoiceFailed = false;
        if ($order !== null) {
            try {
                $this->invoices->prepareInvoice($order);
                $invoiceSent = $this->invoices->sendInvoiceIfReady($order);
            } catch (ConnectionException|RequestException|RuntimeException|TransportExceptionInterface $exception) {
                Log::error('Invoice preparation or delivery failed', [
                    'order_id' => $order->id,
                    'conversation_id' => $conversation->id,
                    'exception' => $exception->getMessage(),
                ]);
                $invoiceFailed = true;
            }

            if ($invoiceFailed) {
                $reply .= "\n\nYour order is confirmed, but I could not prepare or send the illustrated invoice just now. Please message us again and we will retry.";
            }
            if ($this->invoices->emailAddressNeeded($order)) {
                $reply .= "\n\nWhat email address should I send your invoice to?";
            } elseif ($invoiceSent) {
                $reply .= "\n\nYour invoice {$order->number} has been sent to {$client->email}.";
            }

            $assistantMessage->update([
                'content' => $reply,
                'meta' => [
                    'order_id' => $order->id,
                    'order_number' => $order->number,
                    'invoice_sent' => $invoiceSent,
                    'invoice_failed' => $invoiceFailed,
                    'invoice_artwork' => (bool) $order->invoice_image_path,
                ],
            ]);
        }

        $this->summaries->refresh($conversation);

        return [
            'reply' => $reply,
            'conversation_id' => $conversation->id,
            'order_confirmed' => $orderConfirmed,
            'order_number' => $order?->number,
            'invoice_sent' => $invoiceSent,
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function historyFor(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();
    }

    private function systemPrompt(User $bot): string
    {
        $knowledge = $bot->knowledgeEntries()
            ->where('is_active', true)
            ->get()
            ->map(fn ($entry): string => "### {$entry->title}\n{$entry->content}")
            ->implode("\n\n");

        if ($bot->knowledge_base) {
            $knowledge .= "\n\n".$bot->knowledge_base;
        }

        $brand = $bot->company ?: $bot->name;

        return <<<PROMPT
        You are {$bot->bot_name}, the AI assistant for {$brand}.
        You are friendly, sharp and concise. You help visitors with questions,
        recommend services/products, and guide them to place an order.

        Use ONLY the knowledge base below to answer business questions.
        If something is not covered, say you will check with the team.
        Keep replies under 120 words unless the visitor asks for detail.
        When the visitor clearly agrees to buy / confirms an order, end your
        reply with the exact marker [ORDER_CONFIRMED] on its own line.
        Do not claim that an invoice has been generated or emailed. The system
        will ask for a billing email and confirm delivery after the order.

        KNOWLEDGE BASE:
        {$knowledge}
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractClientData(Conversation $conversation): array
    {
        $history = $this->historyFor($conversation);

        if (count($history) < 2) {
            return [];
        }

        $schema = '{'
            .'"name": string|null, "email": string|null, "phone": string|null, "company": string|null, '
            .'"needs": string|null (short summary of what the visitor wants), '
            .'"order_confirmed": boolean, "order_total": number|null, '
            .'"items": [{"name": string, "qty": number, "price": number}]'
            .'}';

        $data = $this->ai->extractStructured($history, $schema);

        return Arr::only($data, ['name', 'email', 'phone', 'company', 'needs', 'order_confirmed', 'order_total', 'items']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncClient(User $bot, Conversation $conversation, array $data): ?Client
    {
        $hasData = collect(['name', 'email', 'phone', 'company', 'needs'])
            ->contains(fn (string $key): bool => ! empty($data[$key]))
            || ($data['order_confirmed'] ?? false) === true;

        if (! $hasData && ! $conversation->client_id) {
            return null;
        }

        return DB::transaction(function () use ($bot, $conversation, $data): Client {
            $client = $conversation->client_id
                ? Client::query()->findOrFail($conversation->client_id)
                : new Client([
                    'user_id' => $bot->id,
                    'source' => 'bot',
                ]);

            foreach (['name', 'email', 'phone', 'company', 'needs'] as $field) {
                $isExplicitEmailCorrection = $field === 'email' && ($data['_direct_email'] ?? false) === true;
                if (! empty($data[$field]) && (empty($client->{$field}) || $isExplicitEmailCorrection)) {
                    $client->{$field} = $data[$field];
                }
            }

            if (($data['order_confirmed'] ?? false) === true) {
                $client->order_confirmed = true;
                $client->order_total = $data['order_total'] ?? $client->order_total;
            }

            $client->is_read = false; // triggers CRM notification badge
            $client->save();

            if ($conversation->client_id === null) {
                $conversation->update(['client_id' => $client->id]);
            }

            return $client;
        });
    }

    private function emailIn(string $message): ?string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $message, $matches) !== 1) {
            return null;
        }

        return filter_var($matches[0], FILTER_VALIDATE_EMAIL) !== false
            ? $matches[0]
            : null;
    }
}
