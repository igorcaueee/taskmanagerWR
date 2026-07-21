<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\SimplesDasProcessamento;
use App\Services\SimplesNacional\PgdasdService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

/**
 * Processa a apuração/transmissão do DAS de um cliente para um período,
 * respeitando o limite de chamadas simultâneas da SERPRO (fila dedicada
 * 'simples-nacional' + rate limiter 'integra-contador').
 */
class ProcessarDasClienteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public Cliente $cliente,
        public string $periodoApuracao,
    ) {
        $this->onQueue('simples-nacional');
    }

    public function middleware(): array
    {
        return [new RateLimited('integra-contador')];
    }

    public function handle(PgdasdService $pgdasd): void
    {
        Log::info('[ProcessarDasClienteJob] Iniciando', [
            'cliente_id' => $this->cliente->id,
            'periodo' => $this->periodoApuracao,
        ]);

        try {
            $processamento = $pgdasd->transmitirDeclaracaoDoCliente($this->cliente, $this->periodoApuracao);

            Log::info('[ProcessarDasClienteJob] Concluído', [
                'cliente_id' => $this->cliente->id,
                'periodo' => $this->periodoApuracao,
                'status' => $processamento->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProcessarDasClienteJob] Falha', [
                'cliente_id' => $this->cliente->id,
                'periodo' => $this->periodoApuracao,
                'erro' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        SimplesDasProcessamento::updateOrCreate(
            ['cliente_id' => $this->cliente->id, 'periodo_apuracao' => $this->periodoApuracao],
            [
                'status' => 'erro',
                'mensagem_erro' => $exception->getMessage(),
                'processado_em' => now(),
            ]
        );
    }
}
