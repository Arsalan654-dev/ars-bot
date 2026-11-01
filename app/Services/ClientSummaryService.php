<?php

namespace App\Services;

use App\Models\Conversation;

/**
 * Keeps a rolling human-readable summary on every conversation
 * so the CRM always shows "what does this client want".
 */
class ClientSummaryService
{
    public function __construct(private readonly AiService $ai) {}

    public function refresh(Conversation $conversation): void
    {
        $messages = $conversation->messages()
            ->latest('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->all();

        if (count($messages) < 2) {
            return;
        }

        $summary = $this->ai->extractStructured(
            $messages,
            '{"summary": string (max 60 words, what the visitor wants + order status)}'
        );

        if (! empty($summary['summary'])) {
            $conversation->update(['summary' => $summary['summary']]);

            $conversation->client?->update(['summary' => $summary['summary']]);
        }
    }
}
