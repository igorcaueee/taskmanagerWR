<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-emitente-crt')]
#[Description('Preenche a coluna emitente_crt (1=Simples, 2=Simples sublimite, 3=Normal, 4=MEI) de NF-e já sincronizadas, lendo <emit><CRT> do XML já salvo — não bate na Sefaz de novo.')]
class BackfillEmitenteCrtDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->whereIn('tipo', ['nfe', 'nfce'])
            ->whereNull('emitente_crt')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos a processar: {$total}");

        $atualizados = 0;
        $semCrt = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semCrt) {
            foreach ($docs as $doc) {
                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (! $obj) {
                    continue;
                }

                $nos = $obj->xpath("//*[local-name()='emit']/*[local-name()='CRT']");
                $crt = $nos ? trim((string) $nos[0]) : '';

                if ($crt === '') {
                    // Resumo (resNFe) ou XML antigo sem o grupo <emit> completo.
                    $semCrt++;

                    continue;
                }

                $doc->update(['emitente_crt' => (int) $crt]);
                $atualizados++;
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem CRT no XML (resumo/XML incompleto): {$semCrt}");

        return self::SUCCESS;
    }
}
