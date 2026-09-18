<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovoArquivoPortalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomeCliente,
        public string $nomeArquivo,
        public string $categoria,
        public string $linkPortal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo arquivo disponível no seu Portal WR Assessoria',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.novo-arquivo-portal',
        );
    }
}
