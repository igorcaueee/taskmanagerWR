<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\ContaAzulService;
use Illuminate\Console\Command;

class SincronizarContaAzul extends Command
{
    protected $signature = 'financeiro:sincronizar
                            {cliente? : ID do cliente (omita para sincronizar todos os conectados)}
                            {--etapa= : Etapa específica: contas|categorias|lancamentos}';

    protected $description = 'Sincroniza dados financeiros da Conta Azul para um ou todos os clientes conectados';

    public function handle(ContaAzulService $service): int
    {
        $clienteId = $this->argument('cliente');
        $etapa     = $this->option('etapa');

        $clientes = $clienteId
            ? Cliente::where('id', $clienteId)->where('conta_azul_conectada', true)->get()
            : Cliente::where('conta_azul_conectada', true)->get();

        if ($clientes->isEmpty()) {
            $this->warn('Nenhum cliente conectado à Conta Azul encontrado.');
            return self::FAILURE;
        }

        foreach ($clientes as $cliente) {
            $this->info("▶  [{$cliente->id}] {$cliente->nome}");

            try {
                $service->refreshTokenIfNeeded($cliente);
                $this->line('   ✔ Token OK');
            } catch (\Throwable $e) {
                $this->error("   ✘ Falha no token: {$e->getMessage()}");
                continue;
            }

            $etapas = [
                'contas'      => fn () => $service->syncContasFinanceiras($cliente),
                'categorias'  => fn () => $service->syncCategoriasECentrosCusto($cliente),
                'lancamentos' => fn () => $service->syncLancamentos($cliente),
            ];

            $rodar = $etapa ? [$etapa => $etapas[$etapa] ?? null] : $etapas;

            foreach ($rodar as $nome => $fn) {
                if (! $fn) {
                    $this->error("   Etapa inválida: {$nome}. Use: contas, categorias ou lancamentos.");
                    continue;
                }
                try {
                    $fn();
                    $this->line("   ✔ {$nome}");
                } catch (\Throwable $e) {
                    $this->error("   ✘ {$nome}: {$e->getMessage()}");
                }
            }
        }

        $this->info('Sincronização concluída.');
        return self::SUCCESS;
    }
}
