<?php

namespace Database\Seeders;

use App\Models\PrecificacaoIcmsInterestadual;
use Illuminate\Database\Seeder;

class PrecificacaoIcmsInterestadualSeeder extends Seeder
{
    /**
     * UFs do Sul/Sudeste (exceto ES) que aplicam alíquota interestadual de 7%
     * quando o destino é Norte, Nordeste, Centro-Oeste ou ES (Resolução do
     * Senado nº 22/1989). Todas as demais combinações interestaduais usam 12%.
     */
    private const SUL_SUDESTE_SEM_ES = ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS'];

    private const NORTE_NORDESTE_CO_ES = [
        'AC', 'AP', 'AM', 'PA', 'RO', 'RR', 'TO',
        'AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE',
        'DF', 'GO', 'MT', 'MS',
        'ES',
    ];

    private const TODAS_UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
        'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
        'SP', 'SE', 'TO',
    ];

    public function run(): void
    {
        foreach (self::TODAS_UFS as $origem) {
            foreach (self::TODAS_UFS as $destino) {
                if ($origem === $destino) {
                    continue;
                }

                $aliquota = (in_array($origem, self::SUL_SUDESTE_SEM_ES, true)
                    && in_array($destino, self::NORTE_NORDESTE_CO_ES, true))
                    ? 7.00
                    : 12.00;

                PrecificacaoIcmsInterestadual::updateOrCreate(
                    ['uf_origem' => $origem, 'uf_destino' => $destino],
                    ['aliquota' => $aliquota]
                );
            }
        }
    }
}
