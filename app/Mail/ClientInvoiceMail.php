<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ClientInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your invoice '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-invoice',
            with: ['order' => $this->order],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->order->invoice_path || ! Storage::disk('local')->exists($this->order->invoice_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->order->invoice_path)
                ->as($this->order->number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
