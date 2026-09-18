<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\CofreFiscalImportacao;
use App\Models\DocumentoFiscal;
use App\Services\NfeXmlParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Processa em background o .zip enviado em CofreFiscalController::uploadZip — roda fora
 * do request HTTP (fila `database`, mesmo esquema de EnviarEmailCampanhaJob/ProcessarDasClienteJob)
 * pra não esbarrar em timeout de proxy/PHP-FPM com zips grandes (ex.: 288MB / 35k XMLs).
 *
 * As leituras de "chave já existe?" são feitas em lote (whereIn por chunk de XMLS_POR_LOTE)
 * em vez de 1 SELECT por arquivo — com milhares de XMLs isso reduz drasticamente o número
 * de queries síncronas comparado ao loop original.
 */
class ImportarCofreFiscalZipJob implements ShouldQueue
{
    use Queueable;

    // Sem limite de tempo do request HTTP mais, mas ainda protege contra o worker
    // ficar preso indefinidamente num zip corrompido/absurdo.
    public int $timeout = 3600;

    public int $tries = 1;

    // Mesmo racional do antigo MAX_UPLOAD_XMLS, só que bem mais folgado agora que
    // roda assíncrono — protege só contra abuso grosseiro (zip com milhões de entradas).
    const MAX_XMLS = 50000;

    const XMLS_POR_LOTE = 200;

    public function __construct(public int $importacaoId) {}

    public function handle(): void
    {
        $importacao = CofreFiscalImportacao::find($this->importacaoId);

        if (! $importacao) {
            return;
        }

        $importacao->update(['status' => 'processando', 'iniciado_em' => now()]);

        $caminhoZip = Storage::disk('local')->path($importacao->arquivo_path);

        $zip = new ZipArchive();

        if ($zip->open($caminhoZip) !== true) {
            $importacao->update(['status' => 'falhou', 'erro' => 'Não foi possível abrir o arquivo .zip.', 'finalizado_em' => now()]);
            $this->removerArquivoTemporario($importacao);

            return;
        }

        Log::info('[Cofre Fiscal] ImportarCofreFiscalZipJob: zip aberto', [
            'importacao_id' => $importacao->id,
            'arquivo_original' => $importacao->arquivo_nome_original,
            'num_files' => $zip->numFiles,
        ]);

        $clienteId = $importacao->cliente_id;
        $cnpjCliente = preg_replace('/[.\-\/\s]/', '', Cliente::find($clienteId)?->cpfcnpj ?? '');

        $importados = 0;
        $atualizados = 0;
        $ignoradosInvalidos = 0;
        $ignoradosOutroCliente = 0;
        $ignoradosCnpjDivergente = 0;
        $processados = 0;

        $lote = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nome = $zip->getNameIndex($i);

            if ($nome === false || !str_ends_with(strtolower($nome), '.xml')) {
                continue;
            }

            if ($processados >= self::MAX_XMLS) {
                $ignoradosInvalidos += $zip->numFiles - $i;
                break;
            }
            $processados++;

            $conteudo = $zip->getFromIndex($i);
            $meta = $conteudo !== false ? NfeXmlParser::extrairMetadados($conteudo) : null;

            if ($meta === null) {
                $ignoradosInvalidos++;
                continue;
            }

            // O cliente selecionado precisa ser o emitente, o destinatário, ou (CT-e)
            // o tomador do serviço — senão é nota de outra empresa.
            if ($cnpjCliente !== '') {
                $emitenteDoc = preg_replace('/\D/', '', $meta['emitenteDoc'] ?? '');
                $destinatarioDoc = preg_replace('/\D/', '', $meta['destinatarioDoc'] ?? '');
                $tomadorDoc = preg_replace('/\D/', '', $meta['tomadorDoc'] ?? '');

                if ($emitenteDoc !== $cnpjCliente && $destinatarioDoc !== $cnpjCliente && $tomadorDoc !== $cnpjCliente) {
                    $ignoradosCnpjDivergente++;
                    continue;
                }
            }

            $lote[] = $meta;

            if (count($lote) >= self::XMLS_POR_LOTE) {
                [$importados, $atualizados, $ignoradosOutroCliente] = $this->processarLote($lote, $clienteId, $importados, $atualizados, $ignoradosOutroCliente);
                $lote = [];

                $importacao->update([
                    'processados' => $processados,
                    'importados' => $importados,
                    'atualizados' => $atualizados,
                    'ignorados_invalidos' => $ignoradosInvalidos,
                    'ignorados_outro_cliente' => $ignoradosOutroCliente,
                    'ignorados_cnpj_divergente' => $ignoradosCnpjDivergente,
                ]);
            }
        }

        if ($lote) {
            [$importados, $atualizados, $ignoradosOutroCliente] = $this->processarLote($lote, $clienteId, $importados, $atualizados, $ignoradosOutroCliente);
        }

        $zip->close();
        $this->removerArquivoTemporario($importacao);

        $importacao->update([
            'status' => 'concluido',
            'processados' => $processados,
            'importados' => $importados,
            'atualizados' => $atualizados,
            'ignorados_invalidos' => $ignoradosInvalidos,
            'ignorados_outro_cliente' => $ignoradosOutroCliente,
            'ignorados_cnpj_divergente' => $ignoradosCnpjDivergente,
            'finalizado_em' => now(),
        ]);

        Log::info('[Cofre Fiscal] ImportarCofreFiscalZipJob: concluído', [
            'importacao_id' => $importacao->id,
            'importados' => $importados,
            'atualizados' => $atualizados,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $importacao = CofreFiscalImportacao::find($this->importacaoId);

        if (! $importacao) {
            return;
        }

        Log::error('[Cofre Fiscal] ImportarCofreFiscalZipJob: falhou', [
            'importacao_id' => $importacao->id,
            'erro' => $exception->getMessage(),
        ]);

        $importacao->update([
            'status' => 'falhou',
            'erro' => 'Falha inesperada ao processar o .zip: ' . $exception->getMessage(),
            'finalizado_em' => now(),
        ]);

        $this->removerArquivoTemporario($importacao);
    }

    /**
     * Faz 1 SELECT (whereIn) pra checar as chaves já existentes do lote inteiro, em
     * vez de 1 SELECT por XML — mesmo padrão de idempotência do updateOrCreate original
     * (reenviar o mesmo zip não duplica nada), só que com bem menos queries.
     */
    private function processarLote(array $lote, int $clienteId, int $importados, int $atualizados, int $ignoradosOutroCliente): array
    {
        $chaves = array_column($lote, 'chaveAcesso');
        $existentes = DocumentoFiscal::whereIn('chave_acesso', $chaves)->get()->keyBy('chave_acesso');

        foreach ($lote as $meta) {
            $existente = $existentes->get($meta['chaveAcesso']);

            if ($existente && $existente->cliente_id !== $clienteId) {
                $ignoradosOutroCliente++;
                continue;
            }

            DocumentoFiscal::updateOrCreate(
                ['chave_acesso' => $meta['chaveAcesso']],
                [
                    'cliente_id'         => $clienteId,
                    'tipo'               => $meta['tipo'],
                    'origem'             => $existente->origem ?? 'manual',
                    'numero'             => $meta['numero'] ?: null,
                    'data_emissao'       => !empty($meta['dataEmissao']) ? substr($meta['dataEmissao'], 0, 10) : null,
                    'data_saida_entrada' => !empty($meta['dataSaidaEntrada']) ? substr($meta['dataSaidaEntrada'], 0, 10) : null,
                    'emitente_nome'      => $meta['emitenteNome'],
                    'emitente_doc'       => $meta['emitenteDoc'] ?: null,
                    'valor'              => $meta['valor'] ?: null,
                    'situacao'           => $existente?->situacao === 'cancelada' ? 'cancelada' : ($meta['situacao'] ?? null),
                    'tp_nf'              => $meta['tpNf'] ?? $existente?->tp_nf,
                    'xml_content'        => $meta['xmlContent'],
                ]
            );

            $existente ? $atualizados++ : $importados++;
        }

        return [$importados, $atualizados, $ignoradosOutroCliente];
    }

    private function removerArquivoTemporario(CofreFiscalImportacao $importacao): void
    {
        Storage::disk('local')->delete($importacao->arquivo_path);
    }
}
