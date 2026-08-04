<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-data-saida-entrada')]
#[Description('Preenche a coluna data_saida_entrada de NF-e/NFC-e já sincronizados, lendo dhSaiEnt/dSaiEnt do XML já salvo em cofre — não bate na Sefaz de novo.')]
class BackfillDataSaidaEntradaDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->whereIn('tipo', ['nfe', 'nfce'])
            ->whereNull('data_saida_entrada')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos a processar: {$total}");

        $atualizados = 0;
        $semCampo    = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semCampo) {
            foreach ($docs as $doc) {
                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (!$obj) {
                    continue;
                }

                $nos = $obj->xpath("//*[local-name()='dhSaiEnt']") ?: $obj->xpath("//*[local-name()='dSaiEnt']");
                $valor = $nos ? trim((string) $nos[0]) : '';

                if ($valor === '') {
                    $semCampo++;
                    continue;
                }

                $doc->update(['data_saida_entrada' => substr($valor, 0, 10)]);
                $atualizados++;
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem dhSaiEnt/dSaiEnt no XML (nota sem data de saída/entrada separada): {$semCampo}");

        return self::SUCCESS;
    }
}
