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
use Illuminate\Support\Facades\Log;

/**
 * Reconsulta uma janela de NSU anterior ao checkpoint de cada cliente, pras
 * três fases (NF-e, NFC-e e CT-e), pra recapturar documentos que a Sefaz-RS
 * entregou fora de ordem e por isso ficaram pra trás da sincronização
 * sequencial normal (ver CteIntegracaoRsService::buscarPorChave — a própria
 * SVRS reconhece que a recepção de NSU não é estritamente sequencial; o
 * mesmo padrão de webservice — e o mesmo risco — vale pra NFeIntegracao).
 *
 * Complementa `fiscal:sincronizar-notas-rs`: aquele avança os checkpoints pra
 * frente todo dia; este volta um pouco atrás (--janela, padrão 50000000) e
 * reconsulta até o NSU atual de novo em cada fase, sem nunca regredir o
 * checkpoint salvo (ver *IntegracaoRsService::atualizarCheckpoint). Documentos
 * já salvos são apenas re-verificados (updateOrCreate por chave de acesso é
 * idempotente); só os que faltavam entram como novidade.
 */
#[Signature('fiscal:reconsultar-notas-rs {--cliente= : ID ou CNPJ do cliente (opcional — se omitido, reconsulta TODOS os clientes elegíveis)} {--janela= : Quantas posições de NSU voltar a partir do checkpoint (padrão 50000000)} {--fase= : nfe, nfce ou cte (opcional — se omitido, reconsulta as três)}')]
#[Description('Reconsulta uma janela de NSU anterior ao checkpoint de cada cliente (NF-e, NFC-e e CT-e) pra recapturar documentos que chegaram fora de ordem na Sefaz-RS.')]
class ReconsultarNotasFiscaisRs extends Command
{
    // Quantas posições de NSU voltar a partir do checkpoint atual, por padrão
    // (sobrescrito por --janela). NSU não tem relação fixa com tempo, mas
    // numa janela dessa magnitude cobre praticamente todo o histórico do
    // cliente — reconsultar de mais só custa tempo de execução (documentos
    // repetidos são no-op), nunca risco de dado incorreto.
    private const JANELA_NSU_PADRAO = 50000000;

    private const PAUSA_ENTRE_CLIENTES_SEGUNDOS = 2;

    // Coluna de checkpoint por fase — usada tanto pra filtrar quem já tem
    // algo sincronizado quanto pra calcular o NSU de início da reconsulta.
    private const CAMPO_NSU = [
        'nfe'  => 'ultimo_nsu_nfe_rs',
        'nfce' => 'ultimo_nsu_nfce_rs',
        'cte'  => 'ultimo_nsu_cte_rs',
    ];

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

        $janela = (int) ($this->option('janela') ?: self::JANELA_NSU_PADRAO);

        $fasesFiltro = $this->option('fase');
        if ($fasesFiltro && ! isset(self::CAMPO_NSU[$fasesFiltro])) {
            $this->error("Fase inválida: \"{$fasesFiltro}\" (use nfe, nfce ou cte).");

            return self::FAILURE;
        }
        $fases = $fasesFiltro ? [$fasesFiltro] : ['nfe', 'nfce', 'cte'];

        $filtroCliente = $this->option('cliente');
        $clienteUnico = null;

        if ($filtroCliente) {
            $clienteUnico = ctype_digit((string) $filtroCliente)
                ? Cliente::find($filtroCliente)
                : Cliente::whereRaw("REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?", [preg_replace('/[.\-\/\s]/', '', (string) $filtroCliente)])->first();

            if (! $clienteUnico) {
                $this->error("Cliente não encontrado para \"{$filtroCliente}\" (tente o ID ou o CNPJ).");

                return self::FAILURE;
            }
        }

        // Elegível = tem pelo menos uma das fases pedidas já com algum NSU sincronizado
        // (senão não há checkpoint de onde voltar — a sincronização normal ainda nem
        // rodou uma vez pra esse cliente/fase).
        $clientes = Cliente::where('status', 'ativo')
            ->where('importar_notas_fiscais', true)
            ->where(function ($q) use ($fases) {
                foreach ($fases as $fase) {
                    $q->orWhere(self::CAMPO_NSU[$fase], '>', 0);
                }
            })
            ->when($clienteUnico, fn ($q) => $q->where('id', $clienteUnico->id))
            ->get();

        if ($clientes->isEmpty()) {
            $this->info('Nenhum cliente com documento já sincronizado via Sefaz-RS nas fases pedidas.');

            return self::SUCCESS;
        }

        $this->info('Janela de reconsulta: '.$janela.' NSU. Fases: '.implode(', ', $fases).'.');

        $totalSucesso = 0;
        $totalErro = 0;

        foreach ($clientes as $indice => $cliente) {
            $this->info("Reconsultando: {$cliente->nome}");

            foreach ($fases as $fase) {
                $campoNsu = self::CAMPO_NSU[$fase];
                $checkpointAtual = (int) $cliente->{$campoNsu};

                if ($checkpointAtual <= 0) {
                    $this->line("  – {$fase}: sem checkpoint ainda, pulando");
                    continue;
                }

                $nsuInicio = max(1, $checkpointAtual - $janela);

                try {
                    $concluido = false;

                    while (! $concluido) {
                        $resultado = match ($fase) {
                            'nfe'  => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFE, $nsuInicio, modoBackfill: true),
                            'nfce' => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFCE, $nsuInicio, modoBackfill: true),
                            'cte'  => $this->cteRs->sincronizarChunk($cert, $cliente, $nsuInicio, modoBackfill: true),
                        };

                        $concluido = $resultado['concluido'];
                        $nsuInicio = $resultado['proximoNsu'];
                    }

                    SincronizacaoFiscalRs::create([
                        'cliente_id' => $cliente->id,
                        'fase' => "{$fase}_backfill",
                        'status' => 'sucesso',
                        'executado_em' => now(),
                    ]);

                    $totalSucesso++;
                    $this->line("  ✓ {$fase} (a partir do NSU {$checkpointAtual})");
                } catch (\Throwable $e) {
                    Log::error('[fiscal:reconsultar-notas-rs] Falha ao reconsultar fase', [
                        'cliente_id' => $cliente->id,
                        'fase' => $fase,
                        'erro' => $e->getMessage(),
                    ]);

                    SincronizacaoFiscalRs::create([
                        'cliente_id' => $cliente->id,
                        'fase' => "{$fase}_backfill",
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

        $this->info("Concluído: {$totalSucesso} fase(s) com sucesso, {$totalErro} com erro.");

        return self::SUCCESS;
    }
}
