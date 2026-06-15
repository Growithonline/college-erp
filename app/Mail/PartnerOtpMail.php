<?php

namespace App\Mail;

use App\Models\ChannelPartner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartnerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ChannelPartner $partner,
        public string $otp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Partner Login OTP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.partner-otp');
    }

    public function attachments(): array
    {
        return [];
    }
}
