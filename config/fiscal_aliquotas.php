<?php

/*
|--------------------------------------------------------------------------
| Alíquotas de ICMS por UF — usado pela auditoria DIFAL / FCP (Fase 2)
|--------------------------------------------------------------------------
|
| Serve só para RECALCULAR o valor esperado de DIFAL/FCP a consumidor final
| não contribuinte (LC 190/2022) e comparar com o que a NF-e destacou no
| grupo <ICMSUFDest>. Não substitui a legislação de cada estado: são as
| alíquotas GERAIS de mercadoria. Produtos com alíquota específica (cesta
| básica, energia, combustível, comunicação, supérfluos, etc.) vão gerar
| divergência aqui — por isso a auditoria trata como "conferir", não "erro".
|
| Fontes (consultadas em 09/2026):
|  - https://simtax.com.br/tabela-icms-2025-aliquotas-de-todos-estados-atualizada/
|  - https://simplifique.contmatic.com.br/blogs/tabela-icms-2026
|  - https://simtax.com.br/fundo-combate-pobreza-fcp/
|
| Como manter:
|  - `internas`: lista por UF ordenada por `desde` (Y-m-d). A auditoria pega a
|    última faixa cujo `desde` <= data de emissão da nota. Ao mudar uma
|    alíquota, ADICIONE uma faixa nova, não edite a antiga (senão as notas
|    velhas passam a ser auditadas com a alíquota nova).
|  - `fcp_max`: teto de FCP do estado (a maioria 2%). A auditoria só acusa
|    quando a nota destaca FCP ACIMA do teto — o FCP em si é por NCM.
|
*/

return [

    // Tolerância (R$) na comparação item a item do valor destacado vs recalculado.
    'tolerancia_valor' => 0.05,

    // Divergência mínima (pontos percentuais) entre a alíquota interna destacada
    // e a da tabela para a auditoria apontar "conferir alíquota".
    'tolerancia_aliquota_pp' => 0.5,

    'internas' => [
        'AC' => [['desde' => '2019-01-01', 'aliquota' => 19.0]],
        'AL' => [['desde' => '2019-01-01', 'aliquota' => 19.0], ['desde' => '2026-04-01', 'aliquota' => 20.5]],
        'AM' => [['desde' => '2024-01-01', 'aliquota' => 20.0]],
        'AP' => [['desde' => '2019-01-01', 'aliquota' => 18.0]],
        'BA' => [['desde' => '2019-01-01', 'aliquota' => 19.0], ['desde' => '2024-02-07', 'aliquota' => 20.5]],
        'CE' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-01-01', 'aliquota' => 20.0]],
        'DF' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-01-21', 'aliquota' => 20.0]],
        'ES' => [['desde' => '2019-01-01', 'aliquota' => 17.0]],
        'GO' => [['desde' => '2019-01-01', 'aliquota' => 17.0], ['desde' => '2024-04-01', 'aliquota' => 19.0]],
        'MA' => [['desde' => '2019-01-01', 'aliquota' => 20.0], ['desde' => '2024-02-19', 'aliquota' => 22.0], ['desde' => '2025-02-23', 'aliquota' => 23.0]],
        'MG' => [['desde' => '2019-01-01', 'aliquota' => 18.0]],
        'MS' => [['desde' => '2019-01-01', 'aliquota' => 17.0]],
        'MT' => [['desde' => '2019-01-01', 'aliquota' => 17.0]],
        'PA' => [['desde' => '2019-01-01', 'aliquota' => 17.0], ['desde' => '2024-03-16', 'aliquota' => 19.0]],
        'PB' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-01-01', 'aliquota' => 20.0]],
        'PE' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-01-01', 'aliquota' => 20.5]],
        'PI' => [['desde' => '2019-01-01', 'aliquota' => 21.0], ['desde' => '2025-04-01', 'aliquota' => 22.5]],
        'PR' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-03-16', 'aliquota' => 19.5]],
        'RJ' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-03-20', 'aliquota' => 20.0]],
        'RN' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2025-03-20', 'aliquota' => 20.0]],
        'RO' => [['desde' => '2019-01-01', 'aliquota' => 17.5], ['desde' => '2024-01-01', 'aliquota' => 19.5]],
        'RR' => [['desde' => '2019-01-01', 'aliquota' => 20.0]],
        'RS' => [['desde' => '2019-01-01', 'aliquota' => 17.0], ['desde' => '2024-01-01', 'aliquota' => 17.0]],
        'SC' => [['desde' => '2019-01-01', 'aliquota' => 17.0]],
        'SE' => [['desde' => '2019-01-01', 'aliquota' => 18.0], ['desde' => '2024-01-01', 'aliquota' => 19.0]],
        'SP' => [['desde' => '2019-01-01', 'aliquota' => 18.0]],
        'TO' => [['desde' => '2019-01-01', 'aliquota' => 20.0]],
    ],

    // Alíquotas interestaduais (origem → destino) do Senado: 4% para importados,
    // 7% do Sul/Sudeste (exceto ES) para N/NE/CO+ES, 12% no restante. A auditoria
    // aceita o pICMSInter que a própria nota informa; esta lista é só fallback.
    'interestadual_padrao' => 12.0,

    'fcp_max' => [
        'AC' => 0.0, 'AL' => 2.0, 'AM' => 2.0, 'AP' => 0.0, 'BA' => 2.0, 'CE' => 2.0,
        'DF' => 2.0, 'ES' => 2.0, 'GO' => 2.0, 'MA' => 2.0, 'MG' => 2.0, 'MS' => 2.0,
        'MT' => 2.0, 'PA' => 0.0, 'PB' => 2.0, 'PE' => 2.0, 'PI' => 2.0, 'PR' => 2.0,
        'RJ' => 4.0, 'RN' => 2.0, 'RO' => 2.0, 'RR' => 2.0, 'RS' => 2.0, 'SC' => 0.0,
        'SP' => 2.0, 'SE' => 2.0, 'TO' => 2.0,
    ],
];
