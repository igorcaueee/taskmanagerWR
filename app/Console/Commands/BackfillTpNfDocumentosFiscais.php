<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-tp-nf')]
#[Description('Preenche a coluna tp_nf (0=entrada, 1=saída) de NF-e/NFC-e já sincronizados, lendo do XML já salvo em cofre — não bate na Sefaz de novo.')]
class BackfillTpNfDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->whereIn('tipo', ['nfe', 'nfce'])
            ->whereNull('tp_nf')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos a processar: {$total}");

        $atualizados  = 0;
        $semTpNf      = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semTpNf) {
            foreach ($docs as $doc) {
                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (!$obj) {
                    continue;
                }

                $nos = $obj->xpath("//*[local-name()='tpNF']");
                $tpNfStr = $nos ? trim((string) $nos[0]) : '';

                if ($tpNfStr === '') {
                    $semTpNf++;
                    continue;
                }

                $doc->update(['tp_nf' => (int) $tpNfStr]);
                $atualizados++;
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem tpNF no XML (ex.: resumo antigo sem esse campo): {$semTpNf}");

        return self::SUCCESS;
    }
}
