<?php

namespace App\Services;

class NfseXmlParser
{
    private static array $statusMap = [
        '100' => '100 - NFS-e Gerada',
        '101' => '101 - NFS-e Cancelada',
        '102' => '102 - NFS-e Substituida',
    ];

    private static array $retMap = [
        '1' => '1 - Exigivel',
        '2' => '2 - Retido pelo Tomador',
        '3' => '3 - Retido pelo Intermediario',
        '4' => '4 - Nao Incide',
        '5' => '5 - Imune',
        '6' => '6 - Exigivel Suspensa por Decisao Judicial',
        '7' => '7 - Exigivel Suspensa por Proc. Administrativo',
    ];

    public static function parse(string $xmlString): ?array
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xmlString)) {
            return null;
        }

        $get = function (string $tag) use ($doc): ?string {
            $nodes = $doc->getElementsByTagNameNS('*', $tag);
            if ($nodes->length === 0) {
                $nodes = $doc->getElementsByTagName($tag);
            }
            return $nodes->length > 0 ? (trim($nodes->item(0)->textContent) ?: null) : null;
        };

        $float = fn($tag) => (float) ($get($tag) ?? 0);

        $date = function (?string $iso): string {
            if (!$iso) return '';
            $d = substr($iso, 0, 10);
            $parts = explode('-', $d);
            if (count($parts) !== 3) return $iso;
            [$y, $m, $day] = $parts;
            return "$day/$m/$y";
        };

        // Emitente
        $emitDoc = $emitNome = '';
        $emitNodes = $doc->getElementsByTagNameNS('*', 'emit');
        if ($emitNodes->length === 0) {
            $emitNodes = $doc->getElementsByTagName('emit');
        }
        if ($emitNodes->length) {
            $emit = $emitNodes->item(0);
            foreach (['CNPJ', 'CPF'] as $docTag) {
                $node = $emit->getElementsByTagNameNS('*', $docTag);
                if ($node->length === 0) $node = $emit->getElementsByTagName($docTag);
                if ($node->length) { $emitDoc = $node->item(0)->textContent; break; }
            }
            // Tenta PrestadorServico se emit não teve resultado
            if (!$emitDoc) {
                $prestNode = $doc->getElementsByTagNameNS('*', 'PrestadorServico');
                if ($prestNode->length === 0) $prestNode = $doc->getElementsByTagName('PrestadorServico');
                if ($prestNode->length) {
                    $prest = $prestNode->item(0);
                    foreach (['CNPJ', 'CPF'] as $docTag) {
                        $node = $prest->getElementsByTagNameNS('*', $docTag);
                        if ($node->length) { $emitDoc = $node->item(0)->textContent; break; }
                    }
                    $razao = $prest->getElementsByTagNameNS('*', 'RazaoSocial');
                    if ($razao->length) $emitNome = $razao->item(0)->textContent;
                }
            }
            $nomeNode = $emit->getElementsByTagNameNS('*', 'xNome');
            if ($nomeNode->length === 0) $nomeNode = $emit->getElementsByTagName('xNome');
            if ($nomeNode->length) $emitNome = $nomeNode->item(0)->textContent;
        }

        // Tomador
        $tomaDoc = $tomaNome = '';
        $tomaNodes = $doc->getElementsByTagNameNS('*', 'toma');
        if ($tomaNodes->length === 0) {
            $tomaNodes = $doc->getElementsByTagName('toma');
        }
        if ($tomaNodes->length) {
            $toma = $tomaNodes->item(0);
            foreach (['CNPJ', 'CPF', 'NIF'] as $docTag) {
                $node = $toma->getElementsByTagNameNS('*', $docTag);
                if ($node->length === 0) $node = $toma->getElementsByTagName($docTag);
                if ($node->length) { $tomaDoc = $node->item(0)->textContent; break; }
            }
            $nomeNode = $toma->getElementsByTagNameNS('*', 'xNome');
            if ($nomeNode->length === 0) $nomeNode = $toma->getElementsByTagName('xNome');
            if ($nomeNode->length) $tomaNome = $nomeNode->item(0)->textContent;
        }
        // Fallback para TomadorServico
        if (!$tomaDoc) {
            $tomSvcNode = $doc->getElementsByTagNameNS('*', 'TomadorServico');
            if ($tomSvcNode->length === 0) $tomSvcNode = $doc->getElementsByTagName('TomadorServico');
            if ($tomSvcNode->length) {
                $tomSvc = $tomSvcNode->item(0);
                foreach (['CNPJ', 'CPF', 'NIF'] as $docTag) {
                    $node = $tomSvc->getElementsByTagNameNS('*', $docTag);
                    if ($node->length) { $tomaDoc = $node->item(0)->textContent; break; }
                }
                $razao = $tomSvc->getElementsByTagNameNS('*', 'RazaoSocial');
                if ($razao->length) $tomaNome = $razao->item(0)->textContent;
            }
        }

        // Chave de acesso
        $chave = '';
        $infNodes = $doc->getElementsByTagNameNS('*', 'infNFSe');
        if ($infNodes->length === 0) $infNodes = $doc->getElementsByTagName('infNFSe');
        if ($infNodes->length) {
            $chave = preg_replace('/^NFS/i', '', $infNodes->item(0)->getAttribute('Id'));
        }

        $cStat = $get('cStat') ?? '';
        $tpRet = $get('tpRetISSQN') ?? '';

        $vPis     = $float('vPis');
        $vCofins  = $float('vCofins');
        $vRetCP   = $float('vRetCP');
        $vRetIRRF = $float('vRetIRRF');
        $vRetCSLL = $float('vRetCSLL');

        return [
            'nNFSe'          => $get('nNFSe') ?? $get('Numero') ?? '',
            'cStat'          => self::$statusMap[$cStat] ?? $cStat,
            'dCompet'        => $date($get('dCompet') ?? $get('Competencia')),
            'dhEmi'          => $date($get('dhEmi') ?? $get('DataEmissao')),
            'emitDoc'        => trim($emitDoc),
            'emitNome'       => trim($emitNome),
            'tomaCNPJ'       => trim($tomaDoc),
            'tomaNome'       => trim($tomaNome),
            'xLocIncid'      => $get('xLocIncid') ?? $get('MunicipioIncidencia') ?? '-',
            'tpRetISSQN'     => self::$retMap[$tpRet] ?? ($tpRet ?: '-'),
            'vBC'            => $float('vBC'),
            'pAliqAplic'     => $float('pAliqAplic'),
            'vISSQN'         => $float('vISSQN'),
            'cst'            => $get('CST') ?? '-',
            'vBCPisCofins'   => $float('vBCPisCofins'),
            'pAliqPis'       => $get('pAliqPis') ?? '-',
            'pAliqCofins'    => $get('pAliqCofins') ?? '-',
            'vPis'           => $vPis,
            'vCofins'        => $vCofins,
            'tpRetPisCofins' => $get('tpRetPisCofins') ?? '-',
            'vRetCP'         => $vRetCP,
            'vRetIRRF'       => $vRetIRRF,
            'vRetCSLL'       => $vRetCSLL,
            'vServ'          => $float('vServ') ?: $float('ValorServicos'),
            'vTotalRet'      => $vRetCP + $vRetIRRF + $vRetCSLL + $vPis + $vCofins,
            'vLiq'           => $float('vLiq') ?: $float('ValorLiquidoNfse'),
            'chaveAcesso'    => $chave,
        ];
    }
}
