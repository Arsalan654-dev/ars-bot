<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?Payment $payment = null,
        public ?string $plainPassword = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' bot CRM access',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-credentials',
            with: [
                'user' => $this->user,
                'payment' => $this->payment,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => route('login'),
                'paymentUrl' => $this->payment ? route('payment.show', $this->payment->reference) : null,
            ],
        );
    }
}
