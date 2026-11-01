<?php

namespace App\Http\Controllers;

use App\Models\PageSection;
use App\Models\User;
use Illuminate\View\View;

/**
 * Public bot surfaces:
 *  - /bot            standalone page for the super admin's own bot
 *  - /bot/{key}      standalone page for a customer's bot (nav link target)
 *  - /embed/{key}    compact iframe UI injected by widget.js
 */
class BotPageController extends Controller
{
    public function index(): View
    {
        $bot = User::query()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();

        return $this->renderBot($bot, embedded: false);
    }

    public function page(string $key): View
    {
        $bot = User::query()->where('api_key', $key)->firstOrFail();

        return $this->renderBot($bot, embedded: false);
    }

    public function embed(string $key): View
    {
        $bot = User::query()->where('api_key', $key)->firstOrFail();

        return $this->renderBot($bot, embedded: true);
    }

    private function renderBot(User $bot, bool $embedded): View
    {
        $welcome = $bot->botIsServing()
            ? "Hi, I'm {$bot->bot_name} — the assistant for ".($bot->company ?: $bot->name).'. How can I help you today?'
            : null;

        return view('bot.page', [
            'bot' => $bot,
            'embedded' => $embedded,
            'serving' => $bot->botIsServing(),
            'welcome' => $welcome,
            'agencySections' => PageSection::query()->forPage(PageSection::PAGE_AGENCY)->get(),
        ]);
    }
}
