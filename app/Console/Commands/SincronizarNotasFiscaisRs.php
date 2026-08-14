<?php

namespace App\Console\Commands;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\SincronizacaoFiscalRs;
use App\Services\CteIntegracaoRsService;
use App\Services\NfeIntegracaoRsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Busca NF-e/NFC-e/CT-e via webservice de contabilistas da SEFAZ-RS
 * (NfeIntegracaoRsService/CteIntegracaoRsService) de todos os clientes com a
 * flag `importar_notas_fiscais` ativada, um cliente por vez — o certificado
 * da contabilidade é único e compartilhado entre todos os clientes, e rajadas
 * de requisições nele fazem a Sefaz-RS bloquear por "consumo indevido" (ver
 * NfeController::sincronizarRsChunk).
 *
 * Roda só fora do horário comercial (agendado às 18:30 em routes/console.php)
 * e se auto-interrompe às 07:00 do dia seguinte, mesmo que ainda reste
 * cliente na fila — o que não coube numa janela continua na próxima, já que
 * a ordem prioriza quem está há mais tempo sem sincronizar (ver $clientes).
 */
#[Signature('fiscal:sincronizar-notas-rs')]
#[Description('Busca NF-e/NFC-e/CT-e via SEFAZ-RS de todos os clientes com a flag "Importar notas" ativada, um cliente por vez, respeitando a janela fora do horário comercial.')]
class SincronizarNotasFiscaisRs extends Command
{
    // Pausa entre clientes (não entre fases) — margem extra de segurança
    // contra "consumo indevido" no certificado compartilhado da contabilidade.
    private const PAUSA_ENTRE_CLIENTES_SEGUNDOS = 2;

    // Horário limite (do dia seguinte ao início) para interromper — o comando
    // é agendado para começar às 18:30, e não pode invadir o expediente.
    private const HORA_LIMITE = '07:00';

    public function __construct(
        private NfeIntegracaoRsService $nfeRs,
        private CteIntegracaoRsService $cteRs,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $cert = CertificadoContabilidade::first();

        if (! $cert) {
            $this->error('Certificado da contabilidade não configurado. Cadastre-o na tela de NF-e antes de rodar este comando.');

            return self::FAILURE;
        }

        $limite = Carbon::now()->addDay()->setTimeFromTimeString(self::HORA_LIMITE);

        $clientes = Cliente::where('status', 'ativo')
            ->where('importar_notas_fiscais', true)
            ->withMax('sincronizacoesFiscaisRs as ultima_sincronizacao', 'executado_em')
            ->orderBy('ultima_sincronizacao')
            ->get();

        if ($clientes->isEmpty()) {
            $this->info('Nenhum cliente com a flag "Importar notas" ativada.');

            return self::SUCCESS;
        }

        $totalSucesso = 0;
        $totalErro = 0;
        $totalPulados = 0;

        foreach ($clientes as $indice => $cliente) {
            if (now()->greaterThanOrEqualTo($limite)) {
                $totalPulados = $clientes->count() - $indice;
                $this->warn("Limite de horário ({$limite->format('d/m/Y H:i')}) atingido — {$totalPulados} cliente(s) ficaram para a próxima execução.");
                break;
            }

            $this->info("Sincronizando: {$cliente->nome}");

            foreach (['nfe', 'nfce', 'cte'] as $fase) {
                try {
                    $this->sincronizarFase($cert, $cliente, $fase);

                    SincronizacaoFiscalRs::create([
                        'cliente_id' => $cliente->id,
                        'fase' => $fase,
                        'status' => 'sucesso',
                        'executado_em' => now(),
                    ]);

                    $totalSucesso++;
                    $this->line("  ✓ {$fase}");
                } catch (\Throwable $e) {
                    Log::error('[fiscal:sincronizar-notas-rs] Falha ao sincronizar fase', [
                        'cliente_id' => $cliente->id,
                        'fase' => $fase,
                        'erro' => $e->getMessage(),
                    ]);

                    SincronizacaoFiscalRs::create([
                        'cliente_id' => $cliente->id,
                        'fase' => $fase,
                        'status' => 'erro',
                        'mensagem_erro' => $e->getMessage(),
                        'executado_em' => now(),
                    ]);

                    $totalErro++;
                    $this->error("  ✗ {$fase}: {$e->getMessage()}");
                }
            }

            if ($indice < $clientes->count() - 1) {
                sleep(self::PAUSA_ENTRE_CLIENTES_SEGUNDOS);
            }
        }

        $this->info("Concluído: {$totalSucesso} fase(s) com sucesso, {$totalErro} com erro"
            .($totalPulados > 0 ? ", {$totalPulados} cliente(s) pulado(s) por limite de horário." : '.'));

        return self::SUCCESS;
    }

    private function sincronizarFase(CertificadoContabilidade $cert, Cliente $cliente, string $fase): void
    {
        $concluido = false;
        $nsuInicio = null;

        while (! $concluido) {
            $resultado = match ($fase) {
                'nfe' => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFE, $nsuInicio),
                'nfce' => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFCE, $nsuInicio),
                'cte' => $this->cteRs->sincronizarChunk($cert, $cliente, $nsuInicio),
            };

            $concluido = $resultado['concluido'];
            $nsuInicio = $resultado['proximoNsu'];
        }
    }
}
