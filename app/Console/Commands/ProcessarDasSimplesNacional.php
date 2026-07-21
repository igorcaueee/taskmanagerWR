<?php

namespace App\Console\Commands;

use App\Jobs\ProcessarDasClienteJob;
use App\Models\Cliente;
use App\Models\SimplesDasProcessamento;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('simples:processar-das {periodo : Período de apuração no formato YYYYMM}')]
#[Description('Dispara a apuração/transmissão do DAS via Integra Contador (SERPRO) para os clientes elegíveis do Simples Nacional')]
class ProcessarDasSimplesNacional extends Command
{
    public function handle(): void
    {
        $periodo = $this->argument('periodo');

        if (!preg_match('/^\d{6}$/', $periodo)) {
            $this->error('Período inválido. Use o formato YYYYMM (ex: 202606).');

            return;
        }

        $clientes = Cliente::where('regime_tributario', 'Simples Nacional')
            ->where('status', 'ativo')
            ->get();

        if ($clientes->isEmpty()) {
            $this->info('Nenhum cliente elegível do Simples Nacional encontrado.');

            return;
        }

        $despachados = 0;

        foreach ($clientes as $cliente) {
            $jaProcessado = SimplesDasProcessamento::where('cliente_id', $cliente->id)
                ->where('periodo_apuracao', $periodo)
                ->whereIn('status', ['sucesso', 'ja_transmitido'])
                ->exists();

            if ($jaProcessado) {
                continue;
            }

            ProcessarDasClienteJob::dispatch($cliente, $periodo);
            $despachados++;
        }

        Log::info('[ProcessarDasSimplesNacional] Jobs despachados', ['periodo' => $periodo, 'total' => $despachados]);
        $this->info("{$despachados} cliente(s) despachado(s) para o período {$periodo}.");
    }
}
