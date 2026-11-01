<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super admin view of all clients captured by the platform.
 * Delete-only management per requirement.
 */
class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->with('user')
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

        return view('admin.clients.index', [
            'clients' => $clients,
            'unreadCount' => Client::query()->where('is_read', false)->count(),
        ]);
    }

    public function show(Client $client): View
    {
        $client->load(['user', 'conversations.messages' => fn ($query) => $query->latest()->limit(50)]);

        if (! $client->is_read) {
            $client->update(['is_read' => true]);
        }

        return view('admin.clients.show', [
            'client' => $client,
            'orders' => $client->orders()->latest()->get(),
        ]);
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }

    public function markAllRead(): RedirectResponse
    {
        Client::query()->where('is_read', false)->update(['is_read' => true]);

        return back()->with('status', 'All clients marked as read.');
    }
}
