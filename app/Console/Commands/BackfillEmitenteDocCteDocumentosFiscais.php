<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Corrige `emitente_doc`/`emitente_nome` de CT-e já sincronizados que foram
 * gravados com o CNPJ/nome de outro participante do frete (ex.: tomador em
 * `<toma4>`) por engano.
 *
 * Causa: o parse de CT-e lia o emitente com um xpath global (`//CNPJ`,
 * `//xNome`), que pega o PRIMEIRO nó desse nome no documento inteiro. No CT-e
 * completo, o grupo `<ide>/<toma4>` (tomador terceiro do serviço) vem antes de
 * `<emit>` no XML — então, quando o CT-e tem tomador terceiro, o CNPJ/nome do
 * tomador era gravado como se fosse o emitente. Agora o parse lê escopado ao
 * `<emit>`; este comando reprocessa o histórico a partir do `xml_content` já
 * salvo (não bate na Sefaz).
 */
#[Signature('fiscal:backfill-emitente-doc-cte {--dry-run : Só mostra quantos CT-e seriam corrigidos, sem gravar}')]
#[Description('Corrige emitente_doc/emitente_nome de CT-e gravados com o dado de outro participante (ex.: tomador em toma4), relendo o <emit> do XML já salvo.')]
class BackfillEmitenteDocCteDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = DocumentoFiscal::query()
            ->where('tipo', 'cte')
            ->whereNotNull('xml_content');

        $total = $query->count();
        $this->info("Documentos a verificar: {$total}".($dryRun ? ' (dry-run)' : ''));

        $corrigidos = 0;
        $semEmit = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$corrigidos, &$semEmit, $dryRun) {
            foreach ($docs as $doc) {
                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (! $obj) {
                    continue;
                }

                $emitNode = $obj->xpath("//*[local-name()='emit']")[0] ?? null;

                if ($emitNode === null) {
                    // resCTe (resumo) não tem <emit> — só um CNPJ/xNome soltos, que já
                    // são os do emitente, então o parse original já estava correto.
                    $semEmit++;

                    continue;
                }

                $getEmit = fn (string $tag) => trim((string) ($emitNode->xpath(".//*[local-name()='{$tag}']")[0] ?? ''));

                $emitenteDoc = $getEmit('CNPJ') ?: $getEmit('CPF');
                $emitenteNome = trim(mb_convert_encoding($getEmit('xNome'), 'UTF-8', 'UTF-8')) ?: null;

                if ($emitenteDoc === '') {
                    $semEmit++;

                    continue;
                }

                $novo = [];

                if ($emitenteDoc !== (string) $doc->emitente_doc) {
                    $novo['emitente_doc'] = $emitenteDoc;
                }

                if ($emitenteNome !== null && $emitenteNome !== (string) $doc->emitente_nome) {
                    $novo['emitente_nome'] = $emitenteNome;
                }

                if ($novo === []) {
                    continue;
                }

                $corrigidos++;

                if (! $dryRun) {
                    $doc->update($novo);
                }
            }
        });

        $this->info(($dryRun ? 'Seriam corrigidos' : 'Corrigidos').": {$corrigidos}");
        $this->info("Sem <emit> no XML (resCTe/resumo): {$semEmit}");

        return self::SUCCESS;
    }
}
