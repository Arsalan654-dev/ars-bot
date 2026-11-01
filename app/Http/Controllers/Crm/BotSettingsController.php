<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BotSettingsController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function edit(Request $request): View
    {
        return view('crm.bot.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_name' => ['required', 'string', 'max:60'],
            'bot_color' => ['required', 'string', 'max:16'],
            'bot_theme' => ['required', 'string', 'max:32'],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'knowledge_base' => ['nullable', 'string', 'max:20000'],
        ]);

        $user = $request->user();

        $user->fill([
            'bot_name' => $validated['bot_name'],
            'bot_color' => $validated['bot_color'],
            'bot_theme' => $validated['bot_theme'],
            'knowledge_base' => $validated['knowledge_base'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return back()->with('status', 'Bot profile updated.');
    }

    public function regenerateApiKey(Request $request): RedirectResponse
    {
        $request->user()->update(['api_key' => User::generateApiKey()]);

        return back()->with('status', 'API key regenerated.');
    }

    /**
     * Download an order invoice PDF from the client CRM.
     */
    public function invoice(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response($this->invoices->pdfContents($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$order->number.'.pdf"',
        ]);
    }

    public function invoiceArtwork(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->invoice_image_path && Storage::disk('local')->exists($order->invoice_image_path), 404);

        return response(Storage::disk('local')->get($order->invoice_image_path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($order->invoice_image_path) ?: 'image/png',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function embed(Request $request): View
    {
        return view('crm.bot.embed', ['user' => $request->user()]);
    }
}
