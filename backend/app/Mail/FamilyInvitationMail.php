<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FamilyInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $familyName,
        public string $inviterName,
        public string $inviteUrl,
        public \DateTimeInterface $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->familyName} on Kids TP",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.family-invitation',
        );
    }
}
