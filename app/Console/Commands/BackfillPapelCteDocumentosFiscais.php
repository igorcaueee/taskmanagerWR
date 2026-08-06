<?php

namespace App\Console\Commands;

use App\Models\DocumentoFiscal;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('fiscal:backfill-papel-cte')]
#[Description('Preenche a coluna papel_cte (Emitente/Tomador/Remetente/Destinatário/Expedidor/Recebedor) dos CT-e já sincronizados, lendo do XML já salvo em cofre — não bate na Sefaz de novo.')]
class BackfillPapelCteDocumentosFiscais extends Command
{
    public function handle(): int
    {
        $query = DocumentoFiscal::query()
            ->where('tipo', 'cte')
            ->whereNull('papel_cte')
            ->whereNotNull('xml_content')
            ->with('cliente:id,cpfcnpj');

        $total = $query->count();
        $this->info("CT-e a processar: {$total}");

        $atualizados = 0;
        $semPapel    = 0;

        $query->orderBy('id')->chunkById(500, function ($docs) use (&$atualizados, &$semPapel) {
            foreach ($docs as $doc) {
                $cnpjCliente = preg_replace('/\D/', '', $doc->cliente?->cpfcnpj ?? '');

                if ($cnpjCliente === '') {
                    continue;
                }

                libxml_use_internal_errors(true);
                $obj = @new \SimpleXMLElement($doc->xml_content);

                if (!$obj) {
                    continue;
                }

                $papel = $this->identificarPapelCte($obj, $cnpjCliente);

                if ($papel === null) {
                    $semPapel++;
                    continue;
                }

                $doc->update(['papel_cte' => $papel]);
                $atualizados++;
            }
        });

        $this->info("Atualizados: {$atualizados}");
        $this->info("Sem papel identificável (nenhum CNPJ do XML bate com o do cliente): {$semPapel}");

        return self::SUCCESS;
    }

    /**
     * Mesma lógica de CteIntegracaoRsService::identificarPapelCte /
     * CteDistribuicaoDFeService::identificarPapelCte.
     */
    private function identificarPapelCte(\SimpleXMLElement $obj, string $cnpjCliente): ?string
    {
        $docCnpj = fn (string $grupo) => trim((string) ($obj->xpath("//*[local-name()='{$grupo}']/*[local-name()='CNPJ']")[0] ?? ''));

        $emitCnpj  = $docCnpj('emit');
        $remCnpj   = $docCnpj('rem');
        $destCnpj  = $docCnpj('dest');
        $expedCnpj = $docCnpj('exped');
        $recebCnpj = $docCnpj('receb');

        $tomaNos    = $obj->xpath("//*[local-name()='toma']");
        $tomaCodigo = $tomaNos ? trim((string) $tomaNos[0]) : '';

        $tomadorCnpj = match ($tomaCodigo) {
            '0'     => $remCnpj,
            '1'     => $expedCnpj,
            '2'     => $recebCnpj,
            '3'     => $destCnpj,
            '4'     => $docCnpj('toma4'),
            default => null,
        };

        return match (true) {
            $cnpjCliente === $emitCnpj                            => 'Emitente',
            $tomadorCnpj !== null && $cnpjCliente === $tomadorCnpj => 'Tomador',
            $cnpjCliente === $remCnpj                              => 'Remetente',
            $cnpjCliente === $destCnpj                             => 'Destinatário',
            $cnpjCliente === $expedCnpj                            => 'Expedidor',
            $cnpjCliente === $recebCnpj                            => 'Recebedor',
            default                                                => null,
        };
    }
}
