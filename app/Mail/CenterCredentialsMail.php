<?php

namespace App\Mail;

use App\Models\Center;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CenterCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Center $center,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Center Portal Login Credentials');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.center-credentials');
    }

    public function attachments(): array
    {
        return [];
    }
}
