<?php

namespace App\Services\IcmsSt;

use App\Models\StRegraCest;

/**
 * Motor de ICMS-ST para destinatários no RS. Porta fiel de
 * calcular_antecipacao_st_rs.py — ver docstring do arquivo original para o
 * histórico completo das decisões (Igor Caue, 21-22/09/2026).
 */
class CalculadoraStRs extends AbstractCalculadoraSt
{
    protected function ufSuportada(): string
    {
        return 'RS';
    }

    /**
     * As colunas "sujeita à alíquota de 12%/4%" do Apêndice II Seção III
     * (mva_12_pct/mva_4_pct) JÁ SÃO MVA ajustadas -- pré-calculadas pela
     * fórmula da Nota 04 supondo alíquota interna de 17% (ou 12% no item
     * XXII). Reajustá-las no motor aplicava o Convênio 142/18 duas vezes.
     * Por isso o RS usa a coluna "operação interna" (mva_pct, a MVA ST
     * original) e deixa ajustarMva() aplicar a fórmula com a alíquota
     * interna real da regra; as colunas 12%/4% ficam só de referência.
     */
    protected function resolverMva(StRegraCest $regra, ?float $pIcmsInterestadual): array
    {
        if ($pIcmsInterestadual !== 4.0 && $pIcmsInterestadual !== 12.0) {
            return [
                'mva' => null,
                'status' => 'pendente_aliquota',
                'detalhe' => 'Alíquota interestadual (pICMS = '
                    . ($pIcmsInterestadual !== null ? number_format($pIcmsInterestadual, 2) : 'ausente')
                    . '%) fora do previsto no Apêndice II RICMS/RS (12% ou 4%) -- validação manual.',
            ];
        }

        if ($regra->mva_pct === null) {
            return [
                'mva' => null,
                'status' => 'pendente_aliquota',
                'detalhe' => "MVA original (coluna \"operação interna\") não cadastrada para o CEST {$regra->cest} "
                    . 'no RS -- necessária para calcular a MVA ajustada.',
            ];
        }

        return ['mva' => (float) $regra->mva_pct, 'status' => null, 'detalhe' => null];
    }

    protected function notaResponsavel(StRegraCest $regra, string $ufOrigem): string
    {
        $responsavel = 'destinatário (antecipação -- regra definida por Igor Caue em 21/09/2026)';

        if (in_array($ufOrigem, $regra->nao_aplica_uf_origem ?? [], true)) {
            $responsavel .= "; UF de origem ({$ufOrigem}) não é parte do acordo/protocolo deste CEST -- "
                . 'remetente não destaca ST por falta de convênio, mas a mercadoria é ST no RS, então cabe '
                . 'antecipação pelo destinatário.';
        }

        return $responsavel;
    }
}
