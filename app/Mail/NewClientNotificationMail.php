<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewClientNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Client $client) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New client from your bot: '.($this->client->name ?: 'Unknown'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-client',
            with: [
                'client' => $this->client,
                'crmUrl' => route('crm.clients.show', $this->client),
            ],
        );
    }
}
