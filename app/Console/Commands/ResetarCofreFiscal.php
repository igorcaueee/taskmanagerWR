<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('fiscal:resetar-cofre {--force : Pula a confirmação interativa}')]
#[Description('Apaga todos os documentos_fiscais e zera os NSU de todos os clientes, para ressincronizar do zero.')]
class ResetarCofreFiscal extends Command
{
    public function handle(): int
    {
        $totalDocumentos = DocumentoFiscal::count();

        $this->warn("Isso vai apagar {$totalDocumentos} documento(s) fiscal(is) já sincronizado(s) e zerar o NSU de "
            . "todos os clientes (nacional, NF-e/NFC-e RS e CT-e RS). É irreversível — os documentos só voltam "
            . "reprocessando a Sefaz, e o que já tiver saído da janela de retenção dela não volta mais.");

        if (!$this->option('force') && !$this->confirm('Confirma que quer apagar tudo e recomeçar do zero?')) {
            $this->info('Operação cancelada.');
            return self::SUCCESS;
        }

        $documentosApagados = DocumentoFiscal::query()->delete();

        $clientesAtualizados = Cliente::query()->update([
            'ultimo_nsu_nfe_rs'  => 0,
            'ultimo_nsu_nfce_rs' => 0,
            'ultimo_nsu_cte_rs'  => 0,
        ]);

        $certificadosAtualizados = ClienteCertificadoNfse::query()->update(['ultimo_nsu_nfe' => 0]);

        Log::warning('[Cofre Fiscal] resetado via comando fiscal:resetar-cofre', [
            'documentos_apagados'      => $documentosApagados,
            'clientes_atualizados'     => $clientesAtualizados,
            'certificados_atualizados' => $certificadosAtualizados,
        ]);

        $this->info("Concluído: {$documentosApagados} documento(s) apagado(s), {$clientesAtualizados} cliente(s) "
            . "com NSU (RS/CT-e) zerado, {$certificadosAtualizados} certificado(s) nacional(is) com NSU zerado.");

        return self::SUCCESS;
    }
}
