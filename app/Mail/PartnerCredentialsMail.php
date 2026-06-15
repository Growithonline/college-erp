<?php

namespace App\Mail;

use App\Models\ChannelPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChannelPartner $partner,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Partner Portal Login Credentials');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.partner-credentials');
    }

    public function attachments(): array
    {
        return [];
    }
}
