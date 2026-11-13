<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI brain for ARS Bot instances.
 *
 * Works with any OpenAI-compatible chat completions endpoint
 * (OpenAI, Groq, DeepSeek, OpenRouter, Together, local vLLM, ...).
 * The API key + base URL are configured once by the super admin
 * in CRM settings and shared by every bot.
 */
class AiService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            apiKey: (string) Setting::get('ai_api_key', ''),
            baseUrl: rtrim((string) Setting::get('ai_base_url', 'https://api.openai.com/v1'), '/'),
            model: (string) Setting::get('ai_model', 'gpt-4o-mini'),
        );
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Send a chat completion request.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array{content: string, raw: array<string, mixed>}
     */
    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isConfigured()) {
            return [
                'content' => $this->fallbackReply($messages, $options),
                'raw' => ['fallback' => true],
            ];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->retry(2, 500)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $options['model'] ?? $this->model,
                    'messages' => $messages,
                    'temperature' => $options['temperature'] ?? 0.7,
                    'max_tokens' => $options['max_tokens'] ?? 800,
                ]);

            if ($response->failed()) {
                Log::warning('AI request failed', ['status' => $response->status(), 'body' => $response->body()]);

                return [
                    'content' => $this->fallbackReply($messages, $options),
                    'raw' => ['error' => $response->status()],
                ];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            return ['content' => trim((string) $content), 'raw' => $data];
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('AI request failed', ['message' => $exception->getMessage()]);

            return [
                'content' => $this->fallbackReply($messages, $options),
                'raw' => ['error' => 'request_failed'],
            ];
        }
    }

    /**
     * Ask the model to extract structured data from a conversation
     * (client info, needs, order confirmation, totals).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    public function extractStructured(array $messages, string $schemaHint): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $messages[] = [
            'role' => 'user',
            'content' => 'Now extract structured data from the conversation above. '
                .'Reply with valid JSON only, no markdown fences. Schema: '.$schemaHint,
        ];

        $result = $this->chat($messages, ['temperature' => 0.1, 'max_tokens' => 500]);
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $result['content']) ?? '';

        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Cheap deterministic fallback so the bot stays useful
     * before a real AI key is configured.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    private function fallbackReply(array $messages, array $options): string
    {
        $last = collect($messages)->reverse()->firstWhere('role', 'user');
        $text = mb_strtolower($last['content'] ?? '');

        if (str_contains($text, 'price') || str_contains($text, 'cost') || str_contains($text, 'plan')) {
            return 'Our plans start with a flexible weekly option and a full monthly plan. '
                .'Tell me a bit about your project and I will point you to the right one.';
        }

        if (str_contains($text, 'hi') || str_contains($text, 'hello') || str_contains($text, 'hey')) {
            return 'Hi there! I am your assistant. How can I help you today?';
        }

        return 'Thanks for your message! Could you share a few more details so I can help you properly?';
    }
}
