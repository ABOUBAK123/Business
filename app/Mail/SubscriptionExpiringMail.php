<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public int $daysRemaining,
    ) {
    }

    public function envelope(): Envelope
    {
        $unit = $this->daysRemaining > 1 ? 'jours' : 'jour';

        return new Envelope(
            subject: "Votre abonnement expire dans {$this->daysRemaining} {$unit} — " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-expiring',
            with: [
                'tenant' => $this->tenant,
                'daysRemaining' => $this->daysRemaining,
                'ownerName' => $this->tenant->owner?->name,
            ],
        );
    }
}
