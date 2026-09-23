<?php

namespace App\Services\IcmsSt;

use App\Models\StRegraCest;

/**
 * Motor de ICMS-ST para destinatários em MG. Porta fiel de
 * calcular_antecipacao_st_mg.py — ver docstring do arquivo original para o
 * histórico completo das decisões (Igor Caue, 22/09/2026). Diferente do RS,
 * a MVA é uma única coluna (não varia por alíquota interestadual).
 */
class CalculadoraStMg extends AbstractCalculadoraSt
{
    protected function ufSuportada(): string
    {
        return 'MG';
    }

    protected function resolverMva(StRegraCest $regra, ?float $pIcmsInterestadual): array
    {
        if ($regra->mva_pct === null) {
            return [
                'mva' => null,
                'status' => 'pendente_aliquota',
                'detalhe' => "MVA não cadastrada para o CEST {$regra->cest} em MG.",
            ];
        }

        return ['mva' => (float) $regra->mva_pct, 'status' => null, 'detalhe' => null];
    }

    protected function notaResponsavel(StRegraCest $regra, string $ufOrigem): string
    {
        return 'destinatário (antecipação -- item sem ICMS-ST destacado na origem, regra definida '
            . 'por Igor Caue em 22/09/2026)';
    }
}
