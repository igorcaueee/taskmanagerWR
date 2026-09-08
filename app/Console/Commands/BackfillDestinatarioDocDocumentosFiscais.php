<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-destinatario-doc')]
#[Description('Preenche a coluna destinatario_doc (CNPJ/CPF do grupo <dest>) dos documentos já sincronizados, lendo do XML já salvo em cofre — não bate na Sefaz de novo. Usada para separar matriz x filial na consulta de NF-e.')]
class BackfillDestinatarioDocDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->whereNull('destinatario_doc')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos a processar: {$total}");

        $atualizados = 0;
        $semDest     = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semDest) {
            foreach ($docs as $doc) {
                $dest = DocumentoFiscal::extrairDestinatarioDoc($doc->xml_content);

                if ($dest === null) {
                    $semDest++;
                    continue;
                }

                // updateQuietly: pula o hook saving() (que só rederivaria o mesmo valor).
                $doc->destinatario_doc = $dest;
                $doc->saveQuietly();
                $atualizados++;
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem <dest> no XML (resumo/evento — esperado): {$semDest}");

        return self::SUCCESS;
    }
}
