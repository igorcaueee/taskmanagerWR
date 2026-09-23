<?php

namespace App\Services\IcmsSt;

use App\Models\DocumentoFiscal;
use App\Models\StCalculo;

/**
 * Prepara os dados para preenchimento da GNRE (RS) — porta fiel de
 * gerar_gnre.py. O sistema NUNCA emite a guia; só monta os campos para o
 * usuário copiar/digitar no site oficial. Contribuinte = destinatário
 * gaúcho (autolançamento — mesma regra do motor de cálculo, Fase 1).
 */
class PreparadorGnreRs
{
    use XmlHelpers;

    /** Códigos de receita vigentes na tela "Gerar GNRE" do site oficial (22/09/2026). Nunca escolhido automaticamente. */
    public const RECEITAS_GNRE = [
        '100013' => 'ICMS Comunicação',
        '100021' => 'ICMS Energia Elétrica',
        '100030' => 'ICMS Transporte',
        '100048' => 'ICMS Substituição Tributária por Apuração',
        '100056' => 'ICMS Importação',
        '100080' => 'ICMS Recolhimentos Especiais',
        '100099' => 'ICMS Substituição Tributária por Operação',
        '100102' => 'ICMS Consumidor Final Não Contribuinte Outra UF por Operação',
        '100110' => 'ICMS Consumidor Final Não Contribuinte Outra UF por Apuração',
        '100129' => 'ICMS Fundo Estadual de Combate à Pobreza por Operação',
        '100137' => 'ICMS Fundo Estadual de Combate à Pobreza por Apuração',
        '600016' => 'Taxa',
    ];

    /**
     * @return array{
     *   chave: string, nNF: ?string, dhEmi: ?string,
     *   contribuinte_cnpj: ?string, contribuinte_ie: ?string, contribuinte_nome: ?string,
     *   contribuinte_endereco: string, contribuinte_municipio: ?string, contribuinte_uf: ?string,
     *   contribuinte_cep: ?string, contribuinte_fone: ?string, uf_favorecida: ?string,
     *   emitente_nome: ?string, emitente_uf: ?string,
     *   valor_icms_st: float, valor_adicional: float, valor_total: float,
     *   pendentes: array<int, array{item: string, produto: ?string, status: string}>,
     * }
     */
    public function preparar(string $chaveAcesso): array
    {
        $documento = DocumentoFiscal::where('chave_acesso', $chaveAcesso)->firstOrFail();

        libxml_use_internal_errors(true);
        $raiz = new \SimpleXMLElement($documento->xml_content);
        $infNFe = self::descendente($raiz, 'infNFe');

        $ide = self::filho($infNFe, 'ide');
        $emit = self::filho($infNFe, 'emit');
        $dest = self::filho($infNFe, 'dest');
        $enderEmit = self::filho($emit, 'enderEmit');
        $enderDest = self::filho($dest, 'enderDest');

        $endereco = trim(sprintf(
            '%s, %s %s - %s',
            self::txt($enderDest, 'xLgr') ?? '',
            self::txt($enderDest, 'nro') ?? '',
            self::txt($enderDest, 'xCpl') ?? '',
            self::txt($enderDest, 'xBairro') ?? '',
        ));

        [$icmsSt, $adicional, $pendentes] = $this->somarStDaNfe($chaveAcesso);

        return [
            'chave' => $chaveAcesso,
            'nNF' => self::txt($ide, 'nNF') ?? $documento->numero,
            'dhEmi' => self::txt($ide, 'dhEmi'),
            'contribuinte_cnpj' => self::txt($dest, 'CNPJ') ?? self::txt($dest, 'CPF'),
            'contribuinte_ie' => self::txt($dest, 'IE'),
            'contribuinte_nome' => self::txt($dest, 'xNome'),
            'contribuinte_endereco' => $endereco,
            'contribuinte_municipio' => self::txt($enderDest, 'xMun'),
            'contribuinte_uf' => self::txt($enderDest, 'UF'),
            'contribuinte_cep' => self::txt($enderDest, 'CEP'),
            'contribuinte_fone' => self::txt($enderDest, 'fone'),
            'uf_favorecida' => self::txt($enderDest, 'UF'),
            'emitente_nome' => self::txt($emit, 'xNome'),
            'emitente_uf' => self::txt($enderEmit, 'UF'),
            'valor_icms_st' => $icmsSt,
            'valor_adicional' => $adicional,
            'valor_total' => $icmsSt + $adicional,
            'pendentes' => $pendentes,
        ];
    }

    /** @return array{0: float, 1: float, 2: array<int, array{item: string, produto: ?string, status: string}>} */
    private function somarStDaNfe(string $chaveAcesso): array
    {
        $itens = StCalculo::where('chave_acesso', $chaveAcesso)->get();

        $icmsSt = 0.0;
        $adicional = 0.0;
        $pendentes = [];

        foreach ($itens as $item) {
            if ($item->status === 'calculado') {
                $icmsSt += (float) $item->icms_st_devido;
                $adicional += (float) $item->adicional_valor;
            } elseif (in_array($item->status, StCalculo::STATUS_PENDENTES, true)) {
                $pendentes[] = ['item' => $item->nfe_item, 'produto' => $item->produto, 'status' => $item->status];
            }
        }

        return [round($icmsSt, 2), round($adicional, 2), $pendentes];
    }
}
