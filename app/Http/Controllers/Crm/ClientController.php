<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $clients = $user->clients()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company', 'like', $term));
            })
            ->when($request->filled('filter'), function ($query) use ($request): void {
                match ($request->string('filter')->toString()) {
                    'unread' => $query->where('is_read', false),
                    'orders' => $query->where('order_confirmed', true),
                    default => null,
                };
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('crm.clients.index', [
            'clients' => $clients,
            'unreadCount' => $user->clients()->where('is_read', false)->count(),
        ]);
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorizeClient($request, $client);

        $client->load(['conversations.messages' => fn ($query) => $query->latest()->limit(60)]);

        if (! $client->is_read) {
            $client->update(['is_read' => true]);
        }

        return view('crm.clients.show', [
            'client' => $client,
            'orders' => $client->orders()->latest()->get(),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);

        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,won,lost'],
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:120'],
        ]);

        $client->update($validated + ['last_contacted_at' => now()]);

        return back()->with('status', 'Client updated.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);

        $client->delete();

        return redirect()->route('crm.clients.index')->with('status', 'Client deleted.');
    }

    public function conversation(Request $request, Conversation $conversation): View
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        return view('crm.conversations.show', [
            'conversation' => $conversation->load(['client', 'messages' => fn ($query) => $query->orderBy('id')]),
        ]);
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        abort_unless($client->user_id === $request->user()->id, 403);
    }
}
