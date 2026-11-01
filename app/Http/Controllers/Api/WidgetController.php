<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves the embeddable widget loader script:
 *   <script src="https://crm.example.com/widget.js?key=lisa_xxx"></script>
 *
 * The script injects a floating launcher + iframe pointing at /embed/{key}
 * and optionally a nav link to the standalone bot page.
 */
class WidgetController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $key = (string) $request->query('key', '');
        $bot = $key !== '' ? User::query()->where('api_key', $key)->first() : null;

        $config = [
            'key' => $key,
            'valid' => $bot !== null && $bot->botIsServing(),
            'name' => $bot?->bot_name ?? 'Assistant',
            'color' => $bot?->bot_color ?? '#000000',
            'avatar' => $bot?->avatar_path ? asset('storage/'.$bot->avatar_path) : null,
            'embedUrl' => $bot !== null ? route('bot.embed', ['key' => $key]) : null,
            'pageUrl' => $bot !== null ? route('bot.page', ['key' => $key]) : null,
        ];

        return response()
            ->view('widget.loader', ['config' => $config])
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
