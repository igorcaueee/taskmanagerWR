<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $assunto,
        public string $conteudoHtml,
        public string $nomeDestinatario,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->assunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
