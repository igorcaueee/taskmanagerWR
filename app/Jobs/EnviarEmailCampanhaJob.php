<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\EmailCampanha;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailCampanhaJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public EmailCampanha $campanha,
        public string $emailDestinatario,
        public string $nomeDestinatario,
    ) {}

    public function handle(): void
    {
        Log::info('[EnviarEmailJob] Iniciando envio', [
            'campanha_id' => $this->campanha->id,
            'email' => $this->emailDestinatario,
            'nome' => $this->nomeDestinatario,
        ]);

        try {
            Mail::to($this->emailDestinatario, $this->nomeDestinatario)
                ->send(new NewsletterMail(
                    assunto: $this->campanha->assunto,
                    conteudoHtml: $this->campanha->conteudo_html,
                    nomeDestinatario: $this->nomeDestinatario,
                ));

            $this->campanha->increment('total_enviados');

            Log::info('[EnviarEmailJob] E-mail enviado com sucesso', [
                'campanha_id' => $this->campanha->id,
                'email' => $this->emailDestinatario,
            ]);

            $this->verificarConclusao();
        } catch (\Throwable $e) {
            Log::error('[EnviarEmailJob] Falha ao enviar e-mail', [
                'campanha_id' => $this->campanha->id,
                'email' => $this->emailDestinatario,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[EnviarEmailJob] Job falhou após todas as tentativas', [
            'campanha_id' => $this->campanha->id,
            'email' => $this->emailDestinatario,
            'erro' => $exception->getMessage(),
        ]);

        $this->campanha->increment('total_falhas');
        $this->verificarConclusao();
    }

    private function verificarConclusao(): void
    {
        $this->campanha->refresh();

        if (! $this->campanha->isConcluida()) {
            return;
        }

        $status = $this->campanha->total_falhas > 0 ? 'falha_parcial' : 'enviada';

        $this->campanha->update([
            'status' => $status,
            'enviada_em' => now(),
        ]);

        Log::info('[EnviarEmailJob] Campanha concluída', [
            'campanha_id' => $this->campanha->id,
            'status' => $status,
            'total_enviados' => $this->campanha->total_enviados,
            'total_falhas' => $this->campanha->total_falhas,
        ]);
    }
}
