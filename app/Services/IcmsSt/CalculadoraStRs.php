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

    protected function resolverMva(StRegraCest $regra, ?float $pIcmsInterestadual): array
    {
        if ($pIcmsInterestadual === 4.0) {
            return ['mva' => $regra->mva_4_pct !== null ? (float) $regra->mva_4_pct : null, 'status' => null, 'detalhe' => null];
        }

        if ($pIcmsInterestadual === 12.0) {
            return ['mva' => $regra->mva_12_pct !== null ? (float) $regra->mva_12_pct : null, 'status' => null, 'detalhe' => null];
        }

        return [
            'mva' => null,
            'status' => 'pendente_aliquota',
            'detalhe' => 'Alíquota interestadual (pICMS = '
                . ($pIcmsInterestadual !== null ? number_format($pIcmsInterestadual, 2) : 'ausente')
                . '%) fora do previsto no Apêndice II RICMS/RS (12% ou 4%) -- validação manual.',
        ];
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
