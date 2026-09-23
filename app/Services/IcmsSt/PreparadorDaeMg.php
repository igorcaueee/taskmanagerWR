<?php

namespace App\Services\IcmsSt;

use App\Models\DocumentoFiscal;
use App\Models\StCalculo;

/**
 * Prepara os dados para preenchimento do DAE (MG/SIARE) — porta fiel de
 * gerar_dae_mg.py. Portal próprio de MG (SIARE/SEF-MG), diferente da GNRE
 * nacional. Contribuinte = destinatário mineiro (autolançamento). Período
 * de Referência = Mensal, mês/ano da DATA DE VENCIMENTO (nunca emissão nem
 * pagamento) — regra explícita de Igor Caue, 22/09/2026.
 */
class PreparadorDaeMg
{
    use XmlHelpers;

    public const RECEITA_PADRAO = '0313-7 - ICMS ST RECOLHIMENTO ANTECIPADO';

    private const MESES_PT = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    /**
     * @return array{
     *   chave: string, nNF: ?string, dhEmi: ?string,
     *   contribuinte_cnpj: ?string, contribuinte_ie: ?string, contribuinte_nome: ?string,
     *   contribuinte_municipio: ?string, contribuinte_uf: ?string,
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

        [$icmsSt, $adicional, $pendentes] = $this->somarStDaNfe($chaveAcesso);

        return [
            'chave' => $chaveAcesso,
            'nNF' => self::txt($ide, 'nNF') ?? $documento->numero,
            'dhEmi' => self::txt($ide, 'dhEmi'),
            'contribuinte_cnpj' => self::txt($dest, 'CNPJ') ?? self::txt($dest, 'CPF'),
            'contribuinte_ie' => self::txt($dest, 'IE'),
            'contribuinte_nome' => self::txt($dest, 'xNome'),
            'contribuinte_municipio' => self::txt($enderDest, 'xMun'),
            'contribuinte_uf' => self::txt($enderDest, 'UF'),
            'emitente_nome' => self::txt($emit, 'xNome'),
            'emitente_uf' => self::txt($enderEmit, 'UF'),
            'valor_icms_st' => $icmsSt,
            'valor_adicional' => $adicional,
            'valor_total' => $icmsSt + $adicional,
            'pendentes' => $pendentes,
        ];
    }

    /**
     * Período de Referência = Mensal, mês/ano do VENCIMENTO -- nunca da
     * emissão da NF-e nem do pagamento (regra explícita de Igor Caue).
     *
     * @return array{0: string, 1: string} [nome do mês em português, ano]
     */
    public function periodoReferencia(string $dataVencimento): array
    {
        $data = \Carbon\Carbon::parse($dataVencimento);

        return [self::MESES_PT[$data->month], (string) $data->year];
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
