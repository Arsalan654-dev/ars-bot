<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates public bot requests via the X-Api-Key header.
 * Injects the resolved bot owner as the request user.
 */
class AuthenticateBotApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) $request->header('X-Api-Key', '');

        if ($apiKey === '') {
            return response()->json(['error' => 'Missing API key.'], 401);
        }

        $bot = User::query()->where('api_key', $apiKey)->first();

        if ($bot === null) {
            return response()->json(['error' => 'Invalid API key.'], 401);
        }

        if (! $bot->botIsServing()) {
            return response()->json([
                'error' => 'bot_inactive',
                'message' => 'This bot is currently unavailable. Please contact the site owner.',
            ], 403);
        }

        $request->setUserResolver(fn () => $bot);

        return $next($request);
    }
}
