<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-xml-cancelado')]
#[Description('Reescreve cStat/xMotivo para 101/"Cancelamento homologado" no xml_content de documentos já marcados como cancelados, cujo XML salvo ainda está congelado no cStat de autorização original.')]
class BackfillXmlCanceladoDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->where('situacao', 'cancelada')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos cancelados a processar: {$total}");

        $atualizados = 0;
        $semCStat    = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semCStat) {
            foreach ($docs as $doc) {
                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (!$obj) {
                    continue;
                }

                $cStatNos   = $obj->xpath("//*[local-name()='infProt']/*[local-name()='cStat']");
                $xMotivoNos = $obj->xpath("//*[local-name()='infProt']/*[local-name()='xMotivo']");

                if (empty($cStatNos)) {
                    $semCStat++;
                    continue;
                }

                if ((string) $cStatNos[0] === '101') {
                    continue; // já reescrito
                }

                $cStatNos[0][0] = '101';

                if (!empty($xMotivoNos)) {
                    $xMotivoNos[0][0] = 'Cancelamento homologado';
                }

                $novoXml = $obj->asXML();

                if ($novoXml !== false) {
                    $doc->update(['xml_content' => $novoXml]);
                    $atualizados++;
                }
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem grupo infProt/cStat no XML (não foi possível reescrever): {$semCStat}");

        return self::SUCCESS;
    }
}
