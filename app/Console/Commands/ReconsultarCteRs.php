<?php

namespace App\Console\Commands;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\SincronizacaoFiscalRs;
use App\Services\CteIntegracaoRsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reconsulta uma janela de NSU anterior ao checkpoint de cada cliente pra
 * recapturar CT-e que a Sefaz-RS entregou fora de ordem e por isso ficaram
 * pra trás da sincronização sequencial normal (ver CteIntegracaoRsService::
 * buscarPorChave — a própria SVRS reconhece que a recepção de NSU não é
 * estritamente sequencial).
 *
 * Complementa `fiscal:sincronizar-notas-rs`: aquele avança o checkpoint pra
 * frente todo dia; este volta um pouco atrás (--janela, padrão 50000) e reconsulta até
 * o NSU atual de novo, sem nunca regredir o checkpoint salvo (ver
 * CteIntegracaoRsService::atualizarCheckpoint). Documentos já salvos são
 * apenas re-verificados (updateOrCreate por chave de acesso é idempotente);
 * só os que faltavam entram como novidade.
 */
#[Signature('fiscal:reconsultar-cte-rs {--cliente= : ID ou CNPJ do cliente (opcional — se omitido, reconsulta TODOS os clientes elegíveis)} {--janela= : Quantas posições de NSU voltar a partir do checkpoint (padrão 50000)}')]
#[Description('Reconsulta uma janela de NSU anterior ao checkpoint de cada cliente pra recapturar CT-e que chegaram fora de ordem na Sefaz-RS.')]
class ReconsultarCteRs extends Command
{
    // Quantas posições de NSU voltar a partir do checkpoint atual, por padrão
    // (sobrescrito por --janela). NSU não tem relação fixa com tempo, mas
    // numa janela dessa magnitude cobre folgadamente alguns dias de emissão
    // mesmo pra clientes de alto volume — reconsultar de mais só custa tempo
    // de execução (documentos repetidos são no-op), nunca risco de dado
    // incorreto.
    private const JANELA_NSU_PADRAO = 50000;

    private const PAUSA_ENTRE_CLIENTES_SEGUNDOS = 2;

    public function __construct(
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

        $clientes = Cliente::where('status', 'ativo')
            ->where('importar_notas_fiscais', true)
            ->where('ultimo_nsu_cte_rs', '>', 0)
            ->when($clienteUnico, fn ($q) => $q->where('id', $clienteUnico->id))
            ->get();

        if ($clientes->isEmpty()) {
            $this->info('Nenhum cliente com CT-e já sincronizado via Sefaz-RS.');

            return self::SUCCESS;
        }

        $this->info("Janela de reconsulta: {$janela} NSU.");

        $totalSucesso = 0;
        $totalErro = 0;

        foreach ($clientes as $indice => $cliente) {
            $nsuInicio = max(1, (int) $cliente->ultimo_nsu_cte_rs - $janela);

            $this->info("Reconsultando: {$cliente->nome} (a partir do NSU {$nsuInicio})");

            try {
                $concluido = false;

                while (! $concluido) {
                    $resultado = $this->cteRs->sincronizarChunk($cert, $cliente, $nsuInicio, modoBackfill: true);

                    $concluido = $resultado['concluido'];
                    $nsuInicio = $resultado['proximoNsu'];
                }

                SincronizacaoFiscalRs::create([
                    'cliente_id' => $cliente->id,
                    'fase' => 'cte_backfill',
                    'status' => 'sucesso',
                    'executado_em' => now(),
                ]);

                $totalSucesso++;
                $this->line('  ✓ reconsulta concluída');
            } catch (\Throwable $e) {
                Log::error('[fiscal:reconsultar-cte-rs] Falha ao reconsultar cliente', [
                    'cliente_id' => $cliente->id,
                    'erro' => $e->getMessage(),
                ]);

                SincronizacaoFiscalRs::create([
                    'cliente_id' => $cliente->id,
                    'fase' => 'cte_backfill',
                    'status' => 'erro',
                    'mensagem_erro' => $e->getMessage(),
                    'executado_em' => now(),
                ]);

                $totalErro++;
                $this->error("  ✗ {$e->getMessage()}");
            }

            if ($indice < $clientes->count() - 1) {
                sleep(self::PAUSA_ENTRE_CLIENTES_SEGUNDOS);
            }
        }

        $this->info("Concluído: {$totalSucesso} cliente(s) com sucesso, {$totalErro} com erro.");

        return self::SUCCESS;
    }
}
