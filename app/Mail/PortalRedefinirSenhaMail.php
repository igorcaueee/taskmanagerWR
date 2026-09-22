<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalRedefinirSenhaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomeUsuario,
        public string $linkRedefinir,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Redefinição de senha — Portal do Cliente WR Assessoria',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portal-redefinir-senha',
        );
    }
}
