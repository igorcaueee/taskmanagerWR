<?php

namespace App\Console\Commands;

use App\Jobs\EnviarEmailCampanhaJob;
use App\Models\Cliente;
use App\Models\EmailCampanha;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('campanhas:enviar-agendadas')]
#[Description('Envia campanhas de e-mail agendadas que já atingiram o horário de envio')]
class EnviarCampanhasAgendadas extends Command
{
    public function handle(): void
    {
        $campanhas = EmailCampanha::query()
            ->where('status', 'agendada')
            ->where('enviar_em', '<=', now())
            ->get();

        if ($campanhas->isEmpty()) {
            $this->info('Nenhuma campanha agendada para enviar.');

            return;
        }

        foreach ($campanhas as $campanha) {
            $this->processarCampanha($campanha);
        }
    }

    private function processarCampanha(EmailCampanha $campanha): void
    {
        Log::info('[CampanhasAgendadas] Iniciando campanha agendada', ['campanha_id' => $campanha->id]);

        $clienteIds = $campanha->destinatarios ?? [];

        if (empty($clienteIds)) {
            Log::error('[CampanhasAgendadas] Campanha sem destinatários', ['campanha_id' => $campanha->id]);
            $campanha->update(['status' => 'rascunho']);

            return;
        }

        $clientes = Cliente::whereIn('id', $clienteIds)
            ->with(['contatoClientes', 'socios'])
            ->get();

        $destinatariosList = [];

        foreach ($clientes as $cliente) {
            $emails = $this->resolverEmailsDoCliente($cliente);

            foreach ($emails as $destinatario) {
                $destinatariosList[] = $destinatario;
            }
        }

        $totalJobs = count($destinatariosList);

        $campanha->update([
            'status' => 'enviando',
            'total_destinatarios' => $totalJobs,
            'total_enviados' => 0,
            'total_falhas' => 0,
        ]);

        foreach ($destinatariosList as $destinatario) {
            EnviarEmailCampanhaJob::dispatch($campanha, $destinatario['email'], $destinatario['nome']);
        }

        Log::info('[CampanhasAgendadas] Jobs despachados', ['campanha_id' => $campanha->id, 'total_jobs' => $totalJobs]);
        $this->info("Campanha #{$campanha->id} '{$campanha->titulo}' — {$totalJobs} e-mail(s) despachados.");
    }

    /**
     * @return array<int, array{email: string, nome: string}>
     */
    private function resolverEmailsDoCliente(Cliente $cliente): array
    {
        $emails = [];

        foreach ($cliente->contatoClientes ?? [] as $contato) {
            if (! empty($contato->email)) {
                $emails[] = ['email' => $contato->email, 'nome' => $cliente->nome];
            }
        }

        if (empty($emails) && ! empty($cliente->email)) {
            $emails[] = ['email' => $cliente->email, 'nome' => $cliente->nome];
        }

        return $emails;
    }
}
