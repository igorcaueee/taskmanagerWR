<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Corrige `emitente_doc` (e `emitente_nome`) de NF-e/NFC-e já sincronizadas que
 * foram gravadas com o documento do DESTINATÁRIO por engano.
 *
 * Causa: o parse antigo lia o emitente com um xpath global (`//CNPJ`). Quando o
 * emitente é CPF (produtor rural), o `<emit>` não tem `<CNPJ>` e o primeiro
 * `<CNPJ>` do XML é o do `<dest>` — ou seja, o nosso próprio cliente. Isso fazia
 * `DocumentoFiscal::direcao()` achar que o cliente era o emitente e classificar a
 * nota como saída, sendo entrada. Agora o parse lê escopado ao `<emit>`; este
 * comando reprocessa o histórico a partir do `xml_content` já salvo (não bate na
 * Sefaz).
 */
#[Signature('fiscal:backfill-emitente-doc {--dry-run : Só mostra quantas notas seriam corrigidas, sem gravar}')]
#[Description('Corrige emitente_doc/emitente_nome de NF-e/NFC-e gravados com o dado do destinatário (emitente CPF), relendo o <emit> do XML já salvo.')]
class BackfillEmitenteDocDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = DocumentoFiscal::query()
            ->whereIn('tipo', ['nfe', 'nfce'])
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

                $emitNode = $obj->xpath("//*[local-name()='emit']")[0]
                    ?? $obj->xpath("//*[local-name()='resNFe']")[0]
                    ?? null;

                if ($emitNode === null) {
                    // Resumo antigo sem <resNFe> nomeado ou XML incompleto.
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
        $this->info("Sem <emit>/<resNFe> no XML: {$semEmit}");

        return self::SUCCESS;
    }
}
