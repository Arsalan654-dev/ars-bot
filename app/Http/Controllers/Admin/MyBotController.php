<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "My Bot" — the super admin's own platform bot.
 * Mirrors the customer CRM experience: bot profile, knowledge base
 * entries and API/embed details, all in one place.
 */
class MyBotController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('admin.my-bot.index', [
            'user' => $user,
            'entries' => $user->knowledgeEntries()->latest()->get(),
            'clientsCount' => $user->clients()->count(),
            'conversationsCount' => $user->conversations()->count(),
        ]);
    }

    public function storeKnowledge(Request $request): RedirectResponse
    {
        $request->user()->knowledgeEntries()->create($this->validated($request));

        return back()->with('status', 'Knowledge entry added.');
    }

    public function updateKnowledge(Request $request, KnowledgeEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->update($this->validated($request));

        return back()->with('status', 'Knowledge entry updated.');
    }

    public function destroyKnowledge(Request $request, KnowledgeEntry $entry): RedirectResponse
    {
        abort_unless($entry->user_id === $request->user()->id, 403);

        $entry->delete();

        return back()->with('status', 'Knowledge entry deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:20000'],
            'type' => ['required', 'in:text,faq,product,service,policy'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
