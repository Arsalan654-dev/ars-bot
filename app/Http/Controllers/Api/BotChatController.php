<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public bot endpoint used by the embeddable widget
 * and the standalone bot page.
 */
class BotChatController extends Controller
{
    public function __construct(private readonly BotChatService $chat) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'message' => ['required', 'string', 'max:4000'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $bot = $request->user();

        $key = 'bot-chat:'.$bot->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'error' => 'rate_limited',
                'message' => 'Too many messages, please slow down.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $result = $this->chat->handle($bot, [
            'session_id' => $validated['session_id'],
            'message' => $validated['message'],
            'page_url' => $validated['page_url'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        return response()->json($result);
    }
}
