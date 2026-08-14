<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('fiscal:resetar-cofre {--cliente= : ID ou CNPJ do cliente (opcional — se omitido, reseta TODOS os clientes)} {--force : Pula a confirmação interativa}')]
#[Description('Apaga os documentos_fiscais e zera os NSU — de todos os clientes, ou só de um (--cliente), para ressincronizar do zero.')]
class ResetarCofreFiscal extends Command
{
    public function handle(): int
    {
        $filtroCliente = $this->option('cliente');
        $cliente = null;

        if ($filtroCliente) {
            $cliente = ctype_digit((string) $filtroCliente)
                ? Cliente::find($filtroCliente)
                : Cliente::whereRaw("REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?", [preg_replace('/[.\-\/\s]/', '', (string) $filtroCliente)])->first();

            if (! $cliente) {
                $this->error("Cliente não encontrado para \"{$filtroCliente}\" (tente o ID ou o CNPJ).");

                return self::FAILURE;
            }
        }

        $queryDocumentos = DocumentoFiscal::query()->when($cliente, fn ($q) => $q->where('cliente_id', $cliente->id));
        $totalDocumentos = (clone $queryDocumentos)->count();
        $alvo = $cliente ? "do cliente \"{$cliente->nome}\"" : 'de TODOS os clientes';

        $this->warn("Isso vai apagar {$totalDocumentos} documento(s) fiscal(is) já sincronizado(s) {$alvo} e zerar o NSU "
            . "correspondente (nacional, NF-e/NFC-e RS e CT-e RS). É irreversível — os documentos só voltam "
            . "reprocessando a Sefaz, e o que já tiver saído da janela de retenção dela não volta mais.");

        if (!$this->option('force') && !$this->confirm('Confirma que quer apagar e recomeçar do zero?')) {
            $this->info('Operação cancelada.');
            return self::SUCCESS;
        }

        $documentosApagados = $queryDocumentos->delete();

        $clientesAtualizados = Cliente::query()
            ->when($cliente, fn ($q) => $q->where('id', $cliente->id))
            ->update([
                'ultimo_nsu_nfe_rs'  => 0,
                'ultimo_nsu_nfce_rs' => 0,
                'ultimo_nsu_cte_rs'  => 0,
            ]);

        $certificadosAtualizados = ClienteCertificadoNfse::query()
            ->when($cliente, fn ($q) => $q->where('cliente_id', $cliente->id))
            ->update(['ultimo_nsu_nfe' => 0]);

        Log::warning('[Cofre Fiscal] resetado via comando fiscal:resetar-cofre', [
            'cliente_id'               => $cliente?->id,
            'documentos_apagados'      => $documentosApagados,
            'clientes_atualizados'     => $clientesAtualizados,
            'certificados_atualizados' => $certificadosAtualizados,
        ]);

        $this->info("Concluído: {$documentosApagados} documento(s) apagado(s), {$clientesAtualizados} cliente(s) "
            . "com NSU (RS/CT-e) zerado, {$certificadosAtualizados} certificado(s) nacional(is) com NSU zerado.");

        return self::SUCCESS;
    }
}
