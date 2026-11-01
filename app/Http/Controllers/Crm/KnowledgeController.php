<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function index(Request $request): View
    {
        return view('crm.knowledge.index', [
            'entries' => $request->user()->knowledgeEntries()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $request->user()->knowledgeEntries()->create($validated);

        return back()->with('status', 'Knowledge entry added.');
    }

    public function update(Request $request, KnowledgeEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($request, $entry);

        $entry->update($this->validated($request));

        return back()->with('status', 'Knowledge entry updated.');
    }

    public function destroy(Request $request, KnowledgeEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($request, $entry);

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

    private function authorizeEntry(Request $request, KnowledgeEntry $entry): void
    {
        abort_unless($entry->user_id === $request->user()->id, 403);
    }
}
