<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Services\ContaAzulService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SincronizarEmpresaContaAzul implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(public readonly int $clienteId) {}

    public function handle(ContaAzulService $service): void
    {
        $cliente = Cliente::find($this->clienteId);

        if (! $cliente || ! $cliente->conta_azul_conectada) {
            Log::info("SincronizarEmpresa: cliente #{$this->clienteId} não conectado, pulando.");
            return;
        }

        Log::info("SincronizarEmpresa: iniciando sync para cliente #{$cliente->id} ({$cliente->nome})");

        $etapas = [
            'contas financeiras'     => fn () => $service->syncContasFinanceiras($cliente),
            'categorias e centros'   => fn () => $service->syncCategoriasECentrosCusto($cliente),
            'lançamentos financeiros' => fn () => $service->syncLancamentos($cliente),
        ];

        foreach ($etapas as $nome => $etapa) {
            try {
                $etapa();
                Log::info("SincronizarEmpresa: {$nome} OK — cliente #{$cliente->id}");
            } catch (\Throwable $e) {
                Log::error("SincronizarEmpresa: erro em '{$nome}' — cliente #{$cliente->id}", [
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }
}
