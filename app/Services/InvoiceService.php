<?php

namespace App\Services;

use App\Mail\ClientInvoiceMail;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(private readonly GoogleImageService $images) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForClient(User $bot, Client $client, Conversation $conversation, array $data): Order
    {
        $items = $this->normalizeItems($data['items'] ?? []);
        $itemsTotal = collect($items)->sum(fn (array $item): float => $item['qty'] * $item['price']);
        $total = $items !== []
            ? $itemsTotal
            : max(0, (float) ($data['order_total'] ?? 0));

        $order = Order::query()->create([
            'user_id' => $bot->id,
            'client_id' => $client->id,
            'conversation_id' => $conversation->id,
            'number' => Order::generateNumber(),
            'payment_reference' => Order::generatePaymentReference(),
            'items' => json_encode($items),
            'total' => $total,
            'currency' => 'USD',
            'status' => Order::STATUS_CONFIRMED,
            'payment_status' => 'unpaid',
        ]);

        return $order;
    }

    public function prepareInvoice(Order $order): void
    {
        if (! $order->invoice_image_path || ! Storage::disk('local')->exists($order->invoice_image_path)) {
            try {
                $order->update([
                    'invoice_image_path' => $this->images->generateInvoiceArtwork($order),
                ]);
            } catch (\Throwable $exception) {
                // Artwork is decorative. Any failure (rate limits, quota, bad
                // payloads) must never block the invoice PDF or its delivery.
                Log::error('Invoice artwork generation failed; continuing without it.', [
                    'order_id' => $order->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $this->renderInvoicePdf($order->refresh());
    }

    public function emailAddressNeeded(Order $order): bool
    {
        $order->loadMissing('client');

        return ! is_string($order->client?->email)
            || filter_var($order->client->email, FILTER_VALIDATE_EMAIL) === false;
    }

    public function sendInvoiceIfReady(Order $order): bool
    {
        $order->loadMissing(['client', 'user']);

        if ($order->invoice_sent_at || $this->emailAddressNeeded($order)) {
            return false;
        }

        if (! $order->invoice_path || ! Storage::disk('local')->exists($order->invoice_path)) {
            throw new \RuntimeException('The invoice PDF must be generated before it can be emailed.');
        }

        Mail::to($order->client->email)->send(new ClientInvoiceMail($order));
        $order->update(['invoice_sent_at' => now()]);

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{name: string, qty: int, price: float}>
     */
    private function normalizeItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item): bool => is_array($item)
                && is_string($item['name'] ?? null)
                && trim($item['name']) !== '')
            ->map(fn (array $item): array => [
                'name' => trim((string) $item['name']),
                'qty' => max(1, (int) ($item['qty'] ?? 1)),
                'price' => max(0, (float) ($item['price'] ?? 0)),
            ])
            ->values()
            ->all();
    }

    public function renderInvoicePdf(Order $order): string
    {
        $order->loadMissing(['client', 'user']);

        $artwork = null;
        if ($order->invoice_image_path && Storage::disk('local')->exists($order->invoice_image_path)) {
            $mimeType = Storage::disk('local')->mimeType($order->invoice_image_path);
            $artwork = 'data:'.$mimeType.';base64,'.base64_encode(
                Storage::disk('local')->get($order->invoice_image_path)
            );
        }

        $items = $order->itemsList();
        if ($items === [] && (float) $order->total > 0) {
            $items = [[
                'name' => $order->client?->needs ?: 'Project services',
                'qty' => 1,
                'price' => (float) $order->total,
            ]];
        }

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            'artwork' => $artwork,
            'items' => $items,
        ]);
        $path = "invoices/{$order->number}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        $order->update([
            'invoice_path' => $path,
            'invoiced_at' => now(),
        ]);

        return $path;
    }

    public function pdfContents(Order $order): string
    {
        if ($order->invoice_path && Storage::disk('local')->exists($order->invoice_path)) {
            return Storage::disk('local')->get($order->invoice_path);
        }

        $this->renderInvoicePdf($order);

        return Storage::disk('local')->get($order->invoice_path);
    }

    /**
     * Public payment link for this order (goes through the active gateway).
     */
    public function paymentLink(Order $order): ?string
    {
        if (! $order->payment_reference) {
            return null;
        }

        return route('order.payment.show', $order->payment_reference);
    }
}
