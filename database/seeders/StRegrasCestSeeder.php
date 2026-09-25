<?php

namespace Database\Seeders;

use App\Models\StRegraCest;
use Illuminate\Database\Seeder;

/**
 * Porta fielmente os dicionários REGRAS_CEST (RS) e REGRAS_CEST_MG (MG) dos
 * motores Python já validados por Igor Caue (calcular_antecipacao_st_rs.py e
 * calcular_antecipacao_st_mg.py) para a tabela st_regras_cest. Os valores
 * (MVA, alíquota, segmento, fundamento) são copiados exatamente dos arquivos
 * de origem — este seeder NUNCA re-deriva ou "arredonda" nada.
 *
 * Rodar manualmente no servidor (não faz parte do fluxo normal de deploy):
 *   php artisan db:seed --class=StRegrasCestSeeder
 * É seguro rodar de novo — usa updateOrCreate por (uf, cest).
 */
class StRegrasCestSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->regrasRs() + $this->materiaisConstrucaoRs() + $this->autopecasRevogadasRs() as $cest => $r) {
            StRegraCest::updateOrCreate(
                ['uf' => 'RS', 'cest' => $cest],
                $r + ['uf' => 'RS', 'cest' => $cest]
            );
        }

        foreach ($this->regrasMg() as $cest => $r) {
            StRegraCest::updateOrCreate(
                ['uf' => 'MG', 'cest' => $cest],
                $r + ['uf' => 'MG', 'cest' => $cest]
            );
        }
    }

    /**
     * RICMS/RS Apêndice II Seção III — Item XXVI (Materiais de Construção),
     * Item XXII (Perfumaria/Cosméticos, 25% + 2% AMPARA/RS) e Item XIX
     * (Rações Pet). Fonte: calcular_antecipacao_st_rs.py::REGRAS_CEST.
     * mva_pct (coluna "operação interna") acrescentado em 25/09/2026 a
     * partir do PDF da Seção III -- é a MVA original que o motor ajusta.
     *
     * @return array<string, array<string, mixed>>
     */
    private function regrasRs(): array
    {
        $materiaisConstrucao = 'Item XXVI - Materiais de Construção e Congêneres';
        $perfumaria = 'Item XXII - Perfumaria, Higiene Pessoal e Cosméticos';
        $racoesPet = 'Item XIX - Rações tipo "pet" para animais domésticos';

        $baseConstrucao = [
            'segmento' => $materiaisConstrucao,
            'aliquota_interna_pct' => 17.00,
            'adicional_tipo' => 'nenhum',
            'adicional_confirmado' => true,
            'aliquota_confirmada' => true,
            'fonte_legal' => 'Art. 27, X, Livro I, RICMS/RS ("demais mercadorias")',
        ];

        $basePerfumaria = [
            'segmento' => $perfumaria,
            'aliquota_interna_pct' => 25.00,
            'adicional_tipo' => 'AMPARA_RS',
            'adicional_pct' => 2.00,
            'adicional_confirmado' => true,
            'aliquota_confirmada' => true,
            'fonte_legal' => 'Art. 27, I c/ Apêndice I Seção I item XI + Art. 27, § único "c", RICMS/RS',
        ];

        return [
            '1006500' => $baseConstrucao + [
                'descricao' => 'Acessórios de cobre p/ tubos, uso na construção (nº61)',
                'mva_pct' => 53.00, 'mva_12_pct' => 62.21, 'mva_4_pct' => 76.96,
            ],
            '1000600' => $baseConstrucao + [
                'descricao' => 'Tubos e acessórios de plástico, uso na construção (nº6)',
                'mva_pct' => 83.00, 'mva_12_pct' => 94.02, 'mva_4_pct' => 111.66,
            ],
            '1007900' => $baseConstrucao + [
                'descricao' => 'Torneiras/válvulas p/ canalizações (nº74)',
                'mva_pct' => 71.00, 'mva_12_pct' => 81.30, 'mva_4_pct' => 97.78,
            ],
            '2001600' => $basePerfumaria + [
                'descricao' => 'Preparações solares e antissolares (nº16) -- NÃO SE APLICA A OPERAÇÕES ORIGINÁRIAS DO RJ',
                'mva_pct' => 62.76, 'mva_12_pct' => 62.76, 'mva_4_pct' => 77.56,
                'nao_aplica_uf_origem' => ['RJ'],
            ],
            '2001100' => $basePerfumaria + [
                'descricao' => 'Outros produtos de maquilagem para os olhos (nº11)',
                'mva_pct' => 96.13, 'mva_12_pct' => 96.13, 'mva_4_pct' => 113.96,
            ],
            '2001300' => $basePerfumaria + [
                'descricao' => 'Pós, incluídos os compactos (nº13)',
                'mva_pct' => 88.17, 'mva_12_pct' => 88.17, 'mva_4_pct' => 105.28,
            ],
            '2001500' => $basePerfumaria + [
                'descricao' => 'Outros produtos de beleza/maquilagem, exceto solares (nº15)',
                'mva_pct' => 62.76, 'mva_12_pct' => 62.76, 'mva_4_pct' => 77.56,
            ],
            '2000900' => $basePerfumaria + [
                'descricao' => 'Produtos de maquilagem para os lábios (nº9)',
                'mva_pct' => 77.14, 'mva_12_pct' => 77.14, 'mva_4_pct' => 93.24,
            ],
            '2001000' => $basePerfumaria + [
                'descricao' => 'Sombra, delineador, lápis p/ sobrancelhas e rímel (nº10)',
                'mva_pct' => 83.33, 'mva_12_pct' => 83.33, 'mva_4_pct' => 100.00,
            ],
            '2001400' => $basePerfumaria + [
                'descricao' => 'Cremes de beleza, cremes nutritivos e loções tônicas (nº14)',
                'mva_pct' => 75.80, 'mva_12_pct' => 75.80, 'mva_4_pct' => 91.78,
            ],
            '2200100' => [
                'segmento' => $racoesPet,
                'descricao' => 'Rações tipo "pet" para animais domésticos (nº1)',
                'mva_pct' => 72.28, 'mva_12_pct' => 82.65, 'mva_4_pct' => 99.26,
                'aliquota_interna_pct' => 17.00,
                'adicional_tipo' => 'nenhum',
                'adicional_confirmado' => true,
                'aliquota_confirmada' => true,
                'fonte_legal' => 'Art. 27, X, Livro I, RICMS/RS ("demais mercadorias") -- não consta no Apêndice I Seção I nem no § único do art. 27',
            ],
        ];
    }

    /**
     * Restante do Item XXVI (Materiais de Construção e Congêneres) da Seção
     * III do Apêndice II do RICMS/RS -- os nº6, nº61 e nº74 já vêm de
     * regrasRs() (motor Python). Fonte: "Seção III Merc ST RS.pdf", lido
     * integralmente em 25/09/2026. mva_pct = coluna "operação interna"
     * (MVA original, a que o motor usa); mva_12_pct/mva_4_pct = colunas
     * 12%/4% da tabela, só referência (já vêm ajustadas -- ver
     * CalculadoraStRs::resolverMva()). Conferido: ajustar mva_pct pela
     * Nota 04 reproduz as duas colunas em todos os itens.
     *
     * - Itens com MVA por carga tributária interna (12% ou 17%): usada a
     *   coluna de 17%, que é a alíquota interna cadastrada ("demais
     *   mercadorias", art. 27, X).
     * - Itens com "a) frete incluído / b) frete não incluído na base de
     *   cálculo" (nº21, nº24, nº25): cadastrada a MVA de frete INCLUÍDO; a
     *   de frete não incluído fica registrada na descrição pra edição
     *   manual pela tela de regras se for o caso do cliente.
     * - nº1 (Cal): a tabela só traz MVA "se a carga tributária interna for
     *   12%" -- cadastrada com alíquota 12% e aliquota_confirmada = false
     *   (bloqueia o cálculo até alguém confirmar a carga interna real).
     * - "Este número não se aplica às operações originárias do Estado de
     *   X" vai em nao_aplica_uf_origem, igual ao nº16 da perfumaria.
     *
     * @return array<string, array<string, mixed>>
     */
    private function materiaisConstrucaoRs(): array
    {
        // cest => [descricao, mva_interna (original), mva_12, mva_4, nao_aplica_uf_origem|null]
        $itens = [
            '1000100' => ['Cal (nº1) -- MVA prevista só p/ carga tributária interna de 12%', 72.00, 72.00, 87.63, ['MG']],
            '1000200' => ['Argamassas (nº2)', 58.00, 67.51, 82.74, null],
            '1000300' => ['Outras argamassas (nº3)', 58.00, 67.51, 82.74, null],
            '1000400' => ['Silicones em formas primárias, para uso na construção (nº4)', 80.00, 90.84, 108.19, ['MG']],
            '1000500' => ['Revestimentos de PVC e outros plásticos; forro, sancas e afins de PVC, para uso na construção (nº5)', 91.00, 102.50, 120.91, null],
            '1000700' => ['Revestimento de pavimento de PVC e outros plásticos (nº7)', 65.00, 74.93, 90.84, null],
            '1000800' => ['Chapas, folhas, tiras, películas e outras formas planas, autoadesivas, de plásticos, '
                . 'mesmo em rolos, para uso na construção (nº8)', 69.00, 79.18, 95.46, null],
            '1000900' => ['Veda rosca, lona plástica para uso na construção, fitas isolantes e afins (nº9)', 54.00, 63.27, 78.12, null],
            '1001000' => ['Telha de plástico, mesmo reforçada com fibra de vidro (nº10)', 103.00, 115.22, 134.79, null],
            '1001200' => ['Chapas, laminados plásticos em bobina, para uso na construção, exceto os descritos nos '
                . 'CEST 10.010.00 e 10.011.00 (nº11)', 103.00, 115.22, 134.79, null],
            '1001300' => ['Banheiras, boxes, pias, lavatórios, bidês, sanitários, caixas de descarga e artigos '
                . 'semelhantes para usos sanitários ou higiênicos, de plásticos (nº12)', 69.00, 79.18, 95.46, null],
            '1001400' => ['Artefatos de higiene/toucador de plástico, para uso na construção (nº13)', 103.00, 115.22, 134.79, null],
            '1001500' => ['Caixa d\'água, inclusive sua tampa, de plástico, mesmo reforçadas com fibra de vidro (nº14)', 61.00, 70.69, 86.21, null],
            '1001600' => ['Outras telhas, cumeeira e caixa d\'água, inclusive sua tampa, de plástico, mesmo '
                . 'reforçadas com fibra de vidro (nº15)', 61.00, 70.69, 86.21, null],
            '1001800' => ['Portas, janelas e seus caixilhos, alizares e soleiras, de plástico (nº16)', 60.00, 69.63, 85.06, null],
            '1001900' => ['Postigos, estores (incluídas as venezianas) e artefatos semelhantes e suas partes (nº17)', 107.00, 119.46, 139.42, ['SP']],
            '1002000' => ['Outras obras de plástico, para uso na construção (nº18)', 69.00, 79.18, 95.46, null],
            '1002100' => ['Papel de parede e revestimentos de parede semelhantes; papel para vitrais (nº19)', 103.00, 115.22, 134.79, null],
            '1002200' => ['Telhas de concreto (nº20)', 54.00, 63.27, 78.12, null],
            '1002400' => ['Caixas d\'água, tanques, reservatórios, telhas, calhas, cumeeiras e afins, de '
                . 'fibrocimento, cimento-celulose ou semelhantes (nº21) -- frete INCLUÍDO na BC; se frete NÃO '
                . 'incluído: 83,42% (12%) / 100,09% (4%)', 68.00, 78.12, 94.31, null],
            '1002500' => ['Tijolos, placas (lajes), ladrilhos e outras peças cerâmicas de farinhas siliciosas '
                . 'fósseis ou de terras siliciosas semelhantes (nº22)', 103.00, 115.22, 134.79, ['SP']],
            '1002600' => ['Tijolos, placas (lajes), ladrilhos e peças cerâmicas semelhantes, para uso na '
                . 'construção, refratários (nº23)', 103.00, 115.22, 134.79, ['SP']],
            '1002700' => ['Tijolos para construção, tijoleiras, tapa-vigas e produtos semelhantes, de cerâmica '
                . '(nº24) -- frete INCLUÍDO na BC; se frete NÃO incluído: 90,84% (12%) / 108,19% (4%)', 54.00, 63.27, 78.12, ['MG', 'SP']],
            '1002800' => ['Telhas, elementos de chaminés, condutores de fumaça, ornamentos arquitetônicos, de '
                . 'cerâmica, e outros produtos cerâmicos para uso na construção (nº25) -- frete INCLUÍDO na BC; '
                . 'se frete NÃO incluído: 115,22% (12%) / 134,79% (4%)', 62.00, 71.75, 87.37, ['SP']],
            '1002900' => ['Tubos, calhas ou algerozes e acessórios para canalizações, de cerâmica (nº26)', 103.00, 115.22, 134.79, null],
            '1003000' => ['Ladrilhos e placas de cerâmica, exclusivamente para pavimentação ou revestimento (nº27)', 44.00, 52.67, 66.55, null],
            '1003100' => ['Pias, lavatórios, colunas para lavatórios, banheiras, bidês, sanitários, caixas de '
                . 'descarga, mictórios e aparelhos fixos semelhantes para usos sanitários, de cerâmica (nº28)', 33.00, 41.01, 53.83, ['MG']],
            '1003200' => ['Artefatos de higiene/toucador de cerâmica (nº29)', 103.00, 115.22, 134.79, null],
            '1003300' => ['Vidro vazado ou laminado, em chapas, folhas ou perfis, sem qualquer outro trabalho (nº30)', 43.00, 51.61, 65.39, ['SP']],
            '1003400' => ['Vidro estirado ou soprado, em folhas, sem qualquer outro trabalho (nº31)', 103.00, 115.22, 134.79, ['SP']],
            '1003500' => ['Vidro flotado e vidro desbastado ou polido, em chapas ou em folhas, sem qualquer outro trabalho (nº32)', 52.00, 61.15, 75.80, ['SP']],
            '1003600' => ['Vidros temperados (nº33)', 36.00, 44.19, 57.30, ['SP']],
            '1003700' => ['Vidros laminados (nº34)', 36.00, 44.19, 57.30, ['SP']],
            '1003800' => ['Vidros isolantes de paredes múltiplas (nº35)', 62.00, 71.75, 87.37, ['SP']],
            '1003900' => ['Blocos, placas, tijolos, ladrilhos, telhas e outros artefatos, de vidro prensado ou '
                . 'moldado, para uso na construção; cubos, pastilhas e semelhantes (nº36)', 61.20, 70.91, 86.44, ['SP', 'RJ']],
            '1004000' => ['Barras próprias para construções, exceto vergalhões (nº37)', 103.00, 115.22, 134.79, null],
            '1004100' => ['Outras barras próprias para construções, exceto vergalhões (nº38)', 103.00, 115.22, 134.79, null],
            '1004101' => ['Outros vergalhões (nº77)', 92.00, 103.56, 122.07, null],
            '1004200' => ['Vergalhões (nº39)', 40.00, 48.43, 61.92, null],
            '1004300' => ['Outros vergalhões (nº40)', 92.00, 103.56, 122.07, null],
            '1004400' => ['Fios de ferro ou aço não ligados, não revestidos; cordas, cabos, tranças, lingas e '
                . 'artefatos semelhantes, de ferro ou aço, não isolados para usos elétricos (nº41)', 64.00, 73.87, 89.68, null],
            '1004501' => ['Outros fios de ferro ou aço, não ligados, galvanizados (nº42)', 74.00, 84.48, 101.25, null],
            '1004600' => ['Acessórios para tubos (uniões, cotovelos, luvas ou mangas), de ferro fundido, ferro ou aço (nº43)', 73.00, 83.42, 100.09, null],
            '1004700' => ['Portas e janelas, e seus caixilhos, alizares e soleiras de ferro fundido, ferro ou aço (nº44)', 62.00, 71.75, 87.37, null],
            '1004800' => ['Material para andaimes, armações (cofragens) e escoramentos, eletrocalhas e perfilados '
                . 'de ferro fundido, ferro ou aço, próprios para construção, exceto treliças de aço (nº45)', 31.00, 38.89, 51.51, null],
            '1004900' => ['Treliças de aço (nº46)', 31.00, 38.89, 51.51, null],
            '1005100' => ['Caixas diversas (correio, entrada de água, energia, instalação) de ferro fundido, '
                . 'ferro ou aço, próprias para a construção (nº47)', 103.00, 115.22, 134.79, null],
            '1005200' => ['Arame farpado, arames ou tiras retorcidos, de ferro ou aço, dos tipos utilizados em cercas (nº48)', 41.00, 49.49, 63.08, null],
            '1005300' => ['Telas metálicas, grades e redes, de fios de ferro ou aço (nº49)', 48.00, 56.91, 71.18, null],
            '1005400' => ['Correntes de rolos, de ferro fundido, ferro ou aço (nº50)', 103.00, 115.22, 134.79, null],
            '1005500' => ['Outras correntes de elos articulados, de ferro fundido, ferro ou aço (nº51)', 103.00, 115.22, 134.79, null],
            '1005600' => ['Correntes de elos soldados, de ferro fundido, de ferro ou aço (nº52)', 103.00, 115.22, 134.79, null],
            '1005700' => ['Tachas, pregos, percevejos, escápulas, grampos e artefatos semelhantes, de ferro '
                . 'fundido, ferro ou aço, exceto cobre (nº53)', 66.00, 76.00, 92.00, null],
            '1005800' => ['Parafusos, pinos ou pernos, porcas, tira-fundos, ganchos, rebites, chavetas, '
                . 'contrapinos, arruelas e artigos semelhantes, de ferro fundido, ferro ou aço (nº54)', 69.00, 79.18, 95.46, ['SP']],
            '1005900' => ['Palha de ferro ou aço, exceto de uso doméstico classificados na posição NCM 7323.10.00 (nº55)', 103.00, 115.22, 134.79, null],
            '1005901' => ['Esponjas, esfregões, luvas e artefatos semelhantes para limpeza, de ferro ou aço, '
                . 'exceto de uso doméstico (nº76)', 103.00, 115.22, 134.79, null],
            '1006000' => ['Artefatos de higiene ou de toucador, e suas partes, de ferro fundido, ferro ou aço, '
                . 'para uso na construção (nº56)', 103.00, 115.22, 134.79, null],
            '1006100' => ['Outras obras moldadas, de ferro fundido, ferro ou aço, para uso na construção (nº57)', 103.00, 115.22, 134.79, null],
            '1006200' => ['Abraçadeiras (nº58)', 103.00, 115.22, 134.79, null],
            '1006300' => ['Barras de cobre (nº59)', 38.00, 46.31, 59.61, ['SP']],
            '1006400' => ['Tubos de cobre e suas ligas, para instalações de água quente e gás, para uso na construção (nº60)', 47.00, 55.85, 70.02, ['SP']],
            '1006600' => ['Tachas, pregos, percevejos, escápulas e artefatos semelhantes, de cobre (nº62)', 103.00, 115.22, 134.79, null],
            '1006700' => ['Artefatos de higiene/toucador de cobre, para uso na construção (nº63)', 57.00, 66.45, 81.59, null],
            '1006800' => ['Manta de subcobertura aluminizada (nº64)', 103.00, 115.22, 134.79, null],
            '1007000' => ['Acessórios para tubos (uniões, cotovelos, luvas ou mangas), de alumínio, para uso na construção (nº65)', 80.00, 90.84, 108.19, null],
            '1007100' => ['Construções e suas partes, de alumínio (portas, janelas, estruturas, chapas, barras, '
                . 'perfis, tubos), próprios para construções (nº66)', 44.00, 52.67, 66.55, null],
            '1007200' => ['Artefatos de higiene/toucador de alumínio, para uso na construção (nº67)', 103.00, 115.22, 134.79, null],
            '1007300' => ['Outras obras de alumínio, próprias para construções, incluídas as persianas (nº68)', 81.00, 91.90, 109.34, ['SP']],
            '1007400' => ['Outras guarnições, ferragens e artigos semelhantes de metais comuns, para '
                . 'construções, inclusive puxadores (nº69)', 81.00, 91.90, 109.34, null],
            '1007500' => ['Fechaduras e ferrolhos, de metais comuns, incluídas as suas partes, fechos e '
                . 'armações com fecho; chaves para estes artigos; exceto os de uso automotivo (nº70)', 95.00, 106.74, 125.54, null],
            '1007600' => ['Dobradiças de metais comuns, de qualquer tipo (nº71)', 108.00, 120.53, 140.57, null],
            '1007700' => ['Tubos flexíveis de metais comuns, mesmo com acessórios, para uso na construção (nº72)', 103.00, 115.22, 134.79, null],
            '1007800' => ['Fios, varetas, tubos, chapas, eletrodos e artefatos semelhantes, de metais comuns ou '
                . 'de carbonetos metálicos, para soldagem ou metalização (nº73)', 49.00, 57.97, 72.33, null],
            '1008000' => ['Espelhos de vidro, mesmo emoldurados, exceto os de uso automotivo (nº75)', 53.00, 62.21, 76.96, ['SP']],
        ];

        $regras = [];
        foreach ($itens as $cest => [$descricao, $mvaInterna, $mva12, $mva4, $naoAplicaUfOrigem]) {
            $regras[$cest] = [
                'segmento' => 'Item XXVI - Materiais de Construção e Congêneres',
                'descricao' => $descricao,
                'mva_pct' => $mvaInterna,
                'mva_12_pct' => $mva12,
                'mva_4_pct' => $mva4,
                'aliquota_interna_pct' => 17.00,
                'adicional_tipo' => 'nenhum',
                'adicional_confirmado' => true,
                'aliquota_confirmada' => true,
                'nao_aplica_uf_origem' => $naoAplicaUfOrigem,
                'fonte_legal' => 'RICMS/RS Apêndice II Seção III, Item XXVI; alíquota: Art. 27, X, Livro I ("demais mercadorias")',
            ];
        }

        // Cal: tabela só prevê MVA p/ carga tributária interna de 12% -- bloqueia até confirmar.
        $regras['1000100']['aliquota_interna_pct'] = 12.00;
        $regras['1000100']['aliquota_confirmada'] = false;
        $regras['1000100']['fonte_legal'] = 'RICMS/RS Apêndice II Seção III, Item XXVI, nº1 -- MVA 72,00%/87,63% '
            . 'só para carga tributária interna de 12%; carga interna efetiva da cal no RS não confirmada.';

        return $regras;
    }

    /**
     * Segmento 1 (Autopeças) foi excluído da Substituição Tributária no RS
     * pelo Decreto nº 57.848/2024 (RS), que revogou o item XX da Seção III
     * do Apêndice II do RICMS/RS, com efeitos a partir de 01/11/2024
     * (pesquisado e confirmado em 23/09/2026, ver fontes: LegisWeb id
     * 467364 -- texto do decreto -- e notícia LegisWeb id 29530). O CEST
     * continua de indicação obrigatória na nota (Convênio ICMS 142/18,
     * cláusula vigésima, I), mas NÃO gera mais antecipação de ICMS-ST no RS.
     *
     * Diferente de "CEST não localizado na tabela" (segmento nunca
     * implementado aqui): isto é uma resposta definitiva e pesquisada --
     * `situacao = 'revogada'` faz o motor marcar o item como "não sujeito a
     * ST" em vez de deixá-lo pendente de validação manual. Os CÓDIGOS/
     * descrições vêm da tabela nacional do Convênio 142/18 (mesma de
     * codigosNacionaisAutopecas(), usada também pelo cadastro vigente de
     * MG) -- não é a MVA/alíquota de MG copiada pro RS, que seria inventar
     * dado tributário; aqui simplesmente não há MVA/alíquota nenhuma
     * porque não há mais ST.
     *
     * ADVERTÊNCIA: uma fonte secundária (blog de contabilidade) menciona
     * que "produtos com mesma descrição/NCM podem ter CEST diferente e
     * constar de outros Protocolos/Convênios vigentes, não se enquadrando
     * nesta exclusão" -- não confirmei essa ressalva na lei em si. Se um
     * cliente específico tiver um caso assim, o Igor precisa revisar antes
     * de confiar cegamente neste "revogada" para 100% dos CEST 01.xxx.xx.
     *
     * @return array<string, array<string, mixed>>
     */
    private function autopecasRevogadasRs(): array
    {
        $base = [
            'situacao' => 'revogada',
            'revogada_desde' => '2024-11-01',
            'segmento' => 'Segmento 1 - Autopeças (RICMS/RS Apêndice II -- REVOGADO)',
            'mva_12_pct' => null,
            'mva_4_pct' => null,
            'mva_pct' => null,
            'aliquota_interna_pct' => null,
            'adicional_tipo' => 'nenhum',
            'adicional_confirmado' => true,
            'aliquota_confirmada' => true,
            'fonte_legal' => 'Decreto nº 57.848/2024 (RS) revogou o item XX da Seção III do Apêndice '
                . 'II do RICMS/RS, com efeitos a partir de 01/11/2024 -- autopeças deixaram de ser '
                . 'sujeitas a ICMS-ST no RS. CEST continua de indicação obrigatória (Convênio ICMS '
                . '142/18, cláusula vigésima, I), mas sem gerar antecipação.',
        ];

        $regras = [];
        foreach ($this->codigosNacionaisAutopecas() as $cest => $descricao) {
            $regras[$cest] = $base + ['descricao' => $descricao];
        }

        $regras['0199900'] = $base + [
            'descricao' => 'Outras peças, partes e acessórios para veículos automotores não '
                . 'relacionados nos demais itens deste anexo (nº999.0)',
        ];

        return $regras;
    }

    /**
     * RICMS/MG Anexo VII Parte 2 — Segmento 1 (Autopeças), Segmento 11
     * (Materiais de Limpeza) e Segmento 20 (Perfumaria/Higiene/Cosméticos) +
     * Decreto nº 48.736/2023 (FEM, "+2 p.p."). Fonte:
     * calcular_antecipacao_st_mg.py::REGRAS_CEST_MG.
     *
     * @return array<string, array<string, mixed>>
     */
    private function regrasMg(): array
    {
        return $this->autopecasMg() + $this->limpezaMg() + $this->perfumariaMg() + $this->ferramentasMg();
    }

    /**
     * Códigos e descrições oficiais do Segmento 1 (Autopeças) do CEST,
     * padronizados nacionalmente pelo Convênio ICMS 142/18 (cláusula
     * vigésima) -- mesma lista usada tanto pelo cadastro vigente de MG
     * quanto pelo cadastro "revogado" do RS (ver autopecasRevogadasRs()):
     * o CÓDIGO/descrição do CEST é nacional, só a aplicabilidade da ST
     * (MVA, alíquota, ou revogação) muda por Estado.
     *
     * @return array<string, string>
     */
    private function codigosNacionaisAutopecas(): array
    {
        return [
            '0100100' => 'Catalisadores em colmeia cerâmica ou metálica p/ conversão catalítica (nº1)',
            '0100200' => 'Tubos e seus acessórios de plásticos (nº2)',
            '0100300' => 'Protetores de caçamba (nº3)',
            '0100400' => 'Reservatórios de óleo (nº4)',
            '0100500' => 'Frisos, decalques, molduras e acabamentos (nº5)',
            '0100600' => 'Correias de transmissão (nº6)',
            '0100700' => 'Juntas, gaxetas e outros elementos de vedação (nº7)',
            '0100800' => 'Partes de veículos automóveis, tratores e máquinas autopropulsadas (nº8)',
            '0100900' => 'Tapetes, revestimentos, batentes, buchas e coxins (nº9)',
            '0101000' => 'Tecidos impregnados/revestidos/estratificados com plástico (nº10)',
            '0101100' => 'Mangueiras e tubos semelhantes de matérias têxteis (nº11)',
            '0101200' => 'Encerados e toldos (nº12)',
            '0101300' => 'Capacetes e artefatos de proteção p/ motocicletas (nº13)',
            '0101400' => 'Guarnições de fricção (freios/embreagens) (nº14)',
            '0101500' => 'Vidros de dimensões e formatos p/ aplicação automotiva (nº15)',
            '0101600' => 'Espelhos retrovisores (nº16)',
            '0101700' => 'Lentes de faróis, lanternas e outros utensílios (nº17)',
            '0101800' => 'Cilindro de aço para GNV (nº18)',
            '0101900' => 'Recipientes p/ gases comprimidos ou liquefeitos, ferro/aço (nº19)',
            '0102000' => 'Molas e folhas de molas, de ferro ou aço (nº20)',
            '0102100' => 'Obras moldadas de ferro fundido/ferro/aço (nº21)',
            '0102200' => 'Peso de chumbo p/ balanceamento de roda (nº22)',
            '0102300' => 'Peso p/ balanceamento de roda de estanho (nº23)',
            '0102400' => 'Fechaduras e partes de fechaduras (nº24)',
            '0102500' => 'Chaves apresentadas isoladamente (nº25)',
            '0102600' => 'Dobradiças, guarnições, ferragens de metais comuns (nº26)',
            '0102700' => 'Triângulo de segurança (nº27)',
            '0102800' => 'Motores de pistão alternativo p/ propulsão de veículos (nº28)',
            '0102900' => 'Motores p/ propulsão de veículos automotores (nº29)',
            '0103000' => 'Partes de motores das posições 8407/8408 (nº30)',
            '0103100' => 'Motores hidráulicos (nº31)',
            '0103200' => 'Bombas p/ combustíveis, lubrificantes ou líquidos de arrefecimento (nº32)',
            '0103300' => 'Bombas de vácuo (nº33)',
            '0103400' => 'Compressores e turbocompressores de ar (nº34)',
            '0103500' => 'Partes das bombas/compressores/turbocompressores (nº35)',
            '0103600' => 'Máquinas e aparelhos de ar condicionado (nº36)',
            '0103700' => 'Aparelhos p/ filtrar óleos minerais nos motores (nº37)',
            '0103800' => 'Filtros a vácuo (nº38)',
            '0103900' => 'Partes dos aparelhos p/ filtrar/depurar líquidos ou gases (nº39)',
            '0104000' => 'Extintores, mesmo carregados (nº40)',
            '0104100' => 'Filtros de entrada de ar p/ motores (nº41)',
            '0104200' => 'Depuradores por conversão catalítica de gases de escape (nº42)',
            '0104300' => 'Macacos (nº43)',
            '0104400' => 'Partes para macacos do CEST 01.043.00 (nº44)',
            '0104500' => 'Partes p/ máquinas agrícolas ou rodoviárias (nº45)',
            '0104501' => 'Partes p/ máquinas agrícolas ou rodoviárias (nº45.1)',
            '0104600' => 'Válvulas redutoras de pressão (nº46)',
            '0104700' => 'Válvulas p/ transmissão óleo-hidráulicas ou pneumáticas (nº47)',
            '0104800' => 'Válvulas solenoides (nº48)',
            '0104900' => 'Rolamentos (nº49)',
            '0105000' => 'Árvores de transmissão, engrenagens, volantes, embreagens etc (nº50)',
            '0105100' => 'Juntas metaloplásticas; jogos/sortidos de juntas (nº51)',
            '0105200' => 'Acoplamentos, embreagens, variadores de velocidade e freios eletromagnéticos (nº52)',
            '0105300' => 'Acumuladores elétricos de chumbo p/ arranque de motores (nº53)',
            '0105301' => 'Acumuladores elétricos de chumbo, <=20Ah e <=12V (nº53.1)',
            '0105400' => 'Aparelhos/dispositivos elétricos de ignição ou arranque (nº54)',
            '0105500' => 'Aparelhos elétricos de iluminação/sinalização, limpadores de para-brisas (nº55)',
            '0105600' => 'Telefones móveis do tipo utilizado em veículos automotores (nº56)',
            '0105700' => 'Alto-falantes, amplificadores elétricos de audiofrequência e partes (nº57)',
            '0105800' => 'Aparelhos elétricos de amplificação de som p/ veículos automotores (nº58)',
            '0105900' => 'Aparelhos de reprodução de som (nº59)',
            '0106000' => 'Aparelhos transmissores (emissores) de radiotelefonia/radiotelegrafia (nº60)',
            '0106100' => 'Aparelhos receptores de radiodifusão só c/ fonte externa, uso automóvel (nº61)',
            '0106200' => 'Outros aparelhos receptores de radiodifusão só c/ fonte externa, uso automóvel (nº62)',
            '0106201' => 'Aparelhos videofônicos de gravação/reprodução, uso exclusivo automotivo (nº62.1)',
            '0106300' => 'Antenas (nº63)',
            '0106400' => 'Circuitos impressos (nº64)',
            '0106500' => 'Interruptores e seccionadores e comutadores (nº65)',
            '0106600' => 'Fusíveis e corta-circuitos de fusíveis (nº66)',
            '0106700' => 'Disjuntores (nº67)',
            '0106800' => 'Relés (nº68)',
            '0106900' => 'Partes p/ aparelhos dos CEST 01.065.00/01.066.00/01.067.00/01.068.00 (nº69)',
            '0107000' => 'Faróis e projetores, em unidades seladas (nº70)',
            '0107100' => 'Lâmpadas e tubos de incandescência, exceto UV/infravermelhos (nº71)',
            '0107200' => 'Cabos coaxiais e outros condutores elétricos coaxiais (nº72)',
            '0107300' => 'Jogos de fios p/ velas de ignição e outros jogos de fios (nº73)',
            '0107400' => 'Carroçarias p/ veículos automóveis das posições 8701 a 8705 (nº74)',
            '0107500' => 'Partes e acessórios dos veículos automóveis das posições 8701 a 8705 (nº75)',
            '0107600' => 'Parte e acessórios de motocicletas (nº76)',
            '0107700' => 'Engates p/ reboques e semirreboques (nº77)',
            '0107800' => 'Medidores de nível; Medidores de vazão (nº78)',
            '0107900' => 'Aparelhos p/ medida ou controle da pressão (nº79)',
            '0108000' => 'Contadores, indicadores de velocidade e tacômetros (nº80)',
            '0108100' => 'Amperímetros (nº81)',
            '0108200' => 'Aparelhos digitais p/ medida/indicação de múltiplas grandezas (computador de bordo) (nº82)',
            '0108300' => 'Controladores eletrônicos (nº83)',
            '0108400' => 'Relógios p/ painéis de instrumentos e relógios semelhantes (nº84)',
            '0108500' => 'Assentos e partes de assentos (nº85)',
            '0108600' => 'Acendedores (nº86)',
            '0108700' => 'Tubos de borracha vulcanizada não endurecida (nº87)',
            '0108800' => 'Juntas de vedação de cortiça natural e de amianto (nº88)',
            '0108900' => 'Papel-diagrama p/ tacógrafo, em disco (nº89)',
            '0109000' => 'Fitas, tiras, adesivos, autocolantes, refletores (nº90)',
            '0109100' => 'Cilindros pneumáticos (nº91)',
            '0109200' => 'Bomba elétrica de lavador de para-brisa (nº92)',
            '0109300' => 'Bomba de assistência de direção hidráulica (nº93)',
            '0109400' => 'Motoventiladores (nº94)',
            '0109500' => 'Filtros de pólen do ar-condicionado (nº95)',
            '0109600' => '"Máquina" de vidro elétrico de porta (nº96)',
            '0109700' => 'Motor de limpador de para-brisa (nº97)',
            '0109800' => 'Bobinas de reatância e de autoindução (nº98)',
            '0109900' => 'Baterias de chumbo e de níquel-cádmio (nº99)',
            '0110000' => 'Aparelhos de sinalização acústica (buzina) (nº100)',
            '0110100' => 'Instrumentos p/ regulação de grandezas não elétricas (nº101)',
            '0110200' => 'Analisadores de gases ou de fumaça (sonda lambda) (nº102)',
            '0110300' => 'Perfilados de borracha vulcanizada não endurecida (nº103)',
            '0110400' => 'Artefatos de pasta de fibra de uso automotivo (nº104)',
            '0110500' => 'Tapetes/carpetes - nailon (nº105)',
            '0110600' => 'Tapetes de matérias têxteis sintéticas (nº106)',
            '0110700' => 'Forração interior capacete (nº107)',
            '0110800' => 'Outros para-brisas (nº108)',
            '0110900' => 'Moldura com espelho (nº109)',
            '0111100' => 'Corrente transmissão (nº111)',
            '0111200' => 'Outras correntes de transmissão (nº112)',
            '0111300' => 'Condensador tubular metálico (nº113)',
            '0111400' => 'Trocadores de calor (nº114)',
            '0111500' => 'Partes de aparelhos mecânicos de pulverizar ou dispersar (nº115)',
            '0111600' => 'Macacos manuais p/ veículos (nº116)',
            '0111700' => 'Caçambas, pás, ganchos e tenazes p/ máquinas rodoviárias (nº117)',
            '0111800' => 'Geradores de corrente alternada, potência <=75kva (nº118)',
            '0111900' => 'Aparelhos elétricos p/ alarme de uso automotivo (nº119)',
            '0112000' => 'Bússolas (nº120)',
            '0112100' => 'Indicadores de temperatura (nº121)',
            '0112200' => 'Partes de indicadores de temperatura (nº122)',
            '0112300' => 'Partes de aparelhos de medida ou controle (nº123)',
            '0112400' => 'Termostatos (nº124)',
            '0112500' => 'Instrumentos e aparelhos p/ regulação (nº125)',
            '0112600' => 'Pressostatos (nº126)',
            '0112700' => 'Peças p/ reboques e semirreboques, exceto CEST 01.077.00 (nº127)',
            '0112800' => 'Geradores de ar quente a combustível líquido, 1.500 a 10.400 kcal/h, uso automotivo (nº128)',
        ];
    }

    private function autopecasMg(): array
    {
        $segmento = 'Segmento 1 - Autopeças (RICMS/MG Anexo VII)';
        $mva = 71.78;
        $itens = $this->codigosNacionaisAutopecas();

        $base = [
            'segmento' => $segmento,
            'mva_pct' => $mva,
            'aliquota_interna_pct' => 18.00,
            'adicional_tipo' => 'nenhum',
            'adicional_confirmado' => true,
            'aliquota_confirmada' => true,
            'fonte_legal' => 'RICMS/MG Anexo VII Parte 2, Segmento 1 (Autopeças) -- Âmbito 1.1',
        ];

        $regras = [];
        foreach ($itens as $cest => $descricao) {
            $regras[$cest] = $base + ['descricao' => $descricao];
        }

        $regras['0199900'] = $base + [
            'descricao' => 'Outras peças, partes e acessórios para veículos automotores não '
                . 'relacionados nos demais itens deste anexo (nº999.0) -- CEST catch-all SEM NCM '
                . 'específico associado na tabela oficial; só pode ser atribuído manualmente via '
                . 'override, nunca por correspondência automática de NCM (pedido expresso de Igor '
                . 'Caue, 22/09/2026)',
        ];

        return $regras;
    }

    private function limpezaMg(): array
    {
        $segmento = 'Segmento 11 - Materiais de Limpeza (RICMS/MG Anexo VII)';
        $itens = [
            '1100100' => [65.00, 'Água sanitária, branqueador e outros alvejantes (nº1)'],
            '1100200' => [40.88, 'Sabões/desinfetantes/sanitizantes em pó/flocos/pálhetas/grânulos p/ lavar roupas (nº2)'],
            '1100300' => [40.88, 'Sabões/desinfetantes/sanitizantes líquidos p/ lavar roupas (nº3)'],
            '1100400' => [40.88, 'Detergentes em pó/flocos/pálhetas/grânulos, inclusive desinfetantes/sanitizantes (nº4)'],
            '1100500' => [40.88, 'Detergentes líquidos, exceto p/ lavar roupa (nº5)'],
            '1100600' => [40.88, 'Detergentes líquidos p/ lavar roupa, inclusive desinfetantes/sanitizantes (nº6)'],
            '1100700' => [40.88, 'Outros agentes orgânicos de superfície/preparações tensoativas/preparações para '
                . 'limpeza (inclusive multiuso e limpadores), mesmo contendo sabão, exceto CEST '
                . '11.001.00/11.004.00/11.005.00/11.006.00; embalagem <=50L ou 50kg (nº7)'],
            '1100800' => [35.00, 'Amaciante/suavizante (nº8)'],
            '1100900' => [55.00, 'Esponjas para limpeza (nº9)'],
            '1101000' => [35.00, 'Álcool etílico para limpeza (nº10)'],
            '1101100' => [35.00, 'Esponjas e palhas de aço; esponjas p/ limpeza/polimento/uso semelhante, uso doméstico (nº11)'],
            '1101200' => [65.00, 'Sacos de lixo, conteúdo <=100 litros (nº12)'],
        ];

        $base = [
            'segmento' => $segmento,
            'aliquota_interna_pct' => 18.00,
            'adicional_tipo' => 'nenhum',
            'adicional_confirmado' => true,
            'aliquota_confirmada' => true,
            'fonte_legal' => 'RICMS/MG Anexo VII Parte 2, Segmento 11 (Materiais de Limpeza) -- Âmbito 11.1',
        ];

        $regras = [];
        foreach ($itens as $cest => [$mva, $descricao]) {
            $regras[$cest] = $base + ['mva_pct' => $mva, 'descricao' => $descricao];
        }

        return $regras;
    }

    /**
     * RICMS/MG Anexo VII Parte 2, Segmento 8 (Ferramentas) -- Âmbito 8.1
     * (interno + Alagoas, Paraná, Rio de Janeiro e São Paulo, Protocolo
     * ICMS 193/09 e 27/09). MVA uniforme 45% em todos os 24 CEST (23 itens
     * numerados + o subitem 19.1).
     * Alíquota interna: geral 18% (Anexo I Parte 1, item 7.1) -- ferramentas
     * não constam nos itens de alíquota diferenciada nem no Decreto do FEM
     * (48.736/2023), então sem adicional. Fonte: "Anexo VII - ST em MG.pdf",
     * lido integralmente em 23/09/2026.
     *
     * Alguns itens têm nota "(Exceção: SP)" na tabela oficial -- o âmbito
     * 8.1 (que cobre a operação INTERNA em MG, o caso deste motor) não é
     * afetado; a exceção só derruba SP como UF de origem interestadual
     * para aqueles itens específicos, registrado em nao_aplica_uf_origem.
     */
    private function ferramentasMg(): array
    {
        $segmento = 'Segmento 8 - Ferramentas (RICMS/MG Anexo VII)';
        $mva = 45.00;
        // cest => [descricao, nao_aplica_uf_origem|null]
        $itens = [
            '0800100' => ['Ferramentas de borracha vulcanizada não endurecida (nº1)', null],
            '0800200' => ['Ferramentas, armações e cabos de ferramentas, de madeira (nº2)', null],
            '0800300' => ['Mós e artefatos semelhantes, sem armação, para moer, triturar, amolar, polir, '
                . 'retificar ou cortar; pedras para amolar ou para polir, manualmente, e suas partes, de '
                . 'pedras naturais, de abrasivos naturais ou artificiais aglomerados ou de cerâmica, mesmo '
                . 'com partes de outras matérias (nº3)', null],
            '0800400' => ['Pás, alviões, picaretas, enxadas, sachos, forcados e forquilhas, ancinhos e '
                . 'raspadeiras; machados, podões e ferramentas semelhantes com gume; tesouras de podar de '
                . 'todos os tipos; foices e foicinhas, facas para feno ou para palha, tesouras para sebes, '
                . 'cunhas e outras ferramentas manuais para agricultura, horticultura ou silvicultura (nº4)', null],
            '0800500' => ['Folhas de serras de fita (nº5)', ['SP']],
            '0800600' => ['Lâminas de serras máquinas (nº6)', ['SP']],
            '0800700' => ['Serras manuais e outras folhas de serras (incluídas as fresas-serras e as folhas '
                . 'não dentadas para serrar), exceto as classificadas nos CEST 08.005.00 e 08.006.00 (nº7)', null],
            '0800800' => ['Limas, grosas, alicates (mesmo cortantes), tenazes, pinças, cisalhas para metais, '
                . 'corta-tubos, corta-pinos, saca-bocados e ferramentas semelhantes, manuais, exceto as '
                . 'pinças para sobrancelhas classificadas na posição 8203.20.90 (nº8)', null],
            '0800900' => ['Chaves de porcas, manuais (incluídas as chaves dinamométricas); chaves de caixa '
                . 'intercambiáveis, mesmo com cabos (nº9)', null],
            '0801000' => ['Ferramentas manuais (incluídos os diamantes de vidraceiro) não especificadas nem '
                . 'compreendidas em outras posições, lamparinas ou lâmpadas de soldar (maçaricos) e '
                . 'semelhantes; tornos de apertar, sargentos e semelhantes, exceto os acessórios ou partes '
                . 'de máquinas-ferramentas; bigornas; forjas-portáteis; mós com armação, manuais ou de '
                . 'pedal (nº10)', null],
            '0801100' => ['Ferramentas de pelo menos duas das posições 8202 a 8205, acondicionadas em '
                . 'sortidos para venda a retalho (nº11)', null],
            '0801200' => ['Ferramentas de roscar interior ou exteriormente; de mandrilar ou de fresar (nº12)', ['SP']],
            '0801300' => ['Outras ferramentas intercambiáveis para ferramentas manuais, mesmo mecânicas, ou '
                . 'para máquinas-ferramentas (por exemplo, de embutir, estampar, puncionar, furar, tornear, '
                . 'aparafusar), incluídas as fieiras de estiragem ou de extrusão, para metais, e as '
                . 'ferramentas de perfuração ou de sondagem, exceto forma ou gabarito de produtos em '
                . 'epoxy, exceto as classificadas no CEST 08.012.00 (nº13)', null],
            '0801400' => ['Facas e lâminas cortantes, para máquinas ou para aparelhos mecânicos (nº14)', null],
            '0801500' => ['Plaquetas ou pastilhas intercambiáveis (nº15)', ['SP']],
            '0801600' => ['Outras plaquetas, varetas, pontas e objetos semelhantes para ferramentas, não '
                . 'montados, de ceramais ("cermets"), exceto as classificadas no CEST 08.015.00 (nº16)', null],
            '0801700' => ['Facas de lâmina cortante ou serrilhada, incluídas as podadeiras de lâmina móvel, '
                . 'e suas lâminas, exceto as de uso doméstico (nº17)', null],
            '0801800' => ['Tesouras e suas lâminas (nº18)', null],
            '0801900' => ['Ferramentas pneumáticas, hidráulicas ou com motor (elétrico ou não elétrico) '
                . 'incorporado, de uso manual, exceto o descrito no CEST 08.019.01 (nº19)', ['SP']],
            '0801901' => ['Moto-serras portáteis de corrente, com motor incorporado, não elétrico, de uso '
                . 'agrícola (nº19.1)', ['SP']],
            '0802000' => ['Instrumentos e aparelhos de geodésia, topografia, agrimensura, nivelamento, '
                . 'fotogrametria, hidrografia, oceanografia, hidrologia, meteorologia ou de geofísica, '
                . 'exceto bússolas; telêmetros (nº20)', null],
            '0802100' => ['Instrumentos de desenho, de traçado ou de cálculo; metros, micrômetros, '
                . 'paquímetros, calibres e semelhantes; partes e acessórios (nº21)', null],
            '0802200' => ['Termômetros, suas partes e acessórios (nº22)', null],
            '0802300' => ['Pirômetros, suas partes e acessórios (nº23)', null],
        ];

        $regras = [];
        foreach ($itens as $cest => [$descricao, $naoAplicaUfOrigem]) {
            $regras[$cest] = [
                'segmento' => $segmento,
                'descricao' => $descricao,
                'mva_pct' => $mva,
                'aliquota_interna_pct' => 18.00,
                'adicional_tipo' => 'nenhum',
                'adicional_confirmado' => true,
                'aliquota_confirmada' => true,
                'nao_aplica_uf_origem' => $naoAplicaUfOrigem,
                'fonte_legal' => 'RICMS/MG Anexo VII Parte 2, Segmento 8 (Ferramentas) -- Âmbito 8.1',
            ];
        }

        return $regras;
    }

    private function perfumariaMg(): array
    {
        $segmento = 'Segmento 20 - Perfumaria, Higiene Pessoal e Cosméticos (RICMS/MG Anexo VII)';
        // cest => [mva, descricao, cosmetico_3303_3307 (25% em vez de 18%), fem_provavel]
        $itens = [
            '2000100' => [80.05, 'Henna, embalagem <=200g (nº1)', false, false],
            '2000101' => [80.00, 'Henna, embalagem >200g (nº1.1)', false, false],
            '2000200' => [51.65, 'Vaselina (nº2)', false, false],
            '2000300' => [53.60, 'Amoníaco em solução aquosa (nº3)', false, false],
            '2000400' => [51.24, 'Peróxido de hidrogênio, embalagem <=500ml (nº4)', false, false],
            '2000500' => [63.44, 'Lubrificação íntima (nº5)', false, false],
            '2000600' => [57.15, 'Óleos essenciais e resinoides (nº6)', false, false],
            '2000700' => [52.37, 'Perfumes (extratos) (nº7)', true, true],
            '2000800' => [57.15, 'Águas-de-colônia (nº8)', true, true],
            '2000900' => [65.52, 'Produtos de maquiagem para os lábios (nº9)', true, true],
            '2001000' => [65.52, 'Sombra, delineador, lápis p/ sobrancelhas e rímel (nº10)', true, true],
            '2001100' => [65.52, 'Outros produtos de maquiagem para os olhos (nº11)', true, true],
            '2001200' => [65.52, 'Preparações p/ manicuros e pedicuros (nº12)', true, true],
            '2001300' => [65.52, 'Pós, incluídos os compactos (nº13)', true, true],
            '2001400' => [59.60, 'Cremes de beleza, cremes nutritivos e loções tônicas (nº14)', true, true],
            '2001500' => [32.24, 'Outros produtos de beleza/maquiagem, exceto solares/antissolares (nº15)', true, true],
            '2001600' => [32.24, 'Preparações solares e antissolares (nº16) -- EXCEÇÃO do Decreto FCP (não é FEM)', true, false],
            '2001700' => [37.93, 'Xampus para o cabelo (nº17) -- EXCEÇÃO expressa do Decreto FCP (não é FEM)', true, false],
            '2001800' => [49.36, 'Preparações p/ ondulação/alisamento, permanentes, dos cabelos (nº18)', true, true],
            '2001900' => [52.77, 'Laquês para o cabelo (nº19)', true, true],
            '2002000' => [53.93, 'Outras preparações capilares, incl. máscaras e finalizadores (nº20)', true, true],
            '2002100' => [53.93, 'Condicionadores (nº21)', true, true],
            '2002200' => [34.55, 'Tintura para o cabelo (nº22)', true, true],
            '2002300' => [35.27, 'Dentifrícios (nº23) -- EXCEÇÃO do Decreto FCP: higiene bucal/dentária (não é FEM)', true, false],
            '2002400' => [61.93, 'Fios p/ limpar espaços interdentais (fios dentais) (nº24) -- EXCEÇÃO expressa (não é FEM)', true, false],
            '2002500' => [44.93, 'Outras preparações p/ higiene bucal ou dentária (nº25) -- EXCEÇÃO: higiene bucal (não é FEM)', true, false],
            '2002600' => [67.18, 'Preparações p/ barbear (antes, durante ou após) (nº26)', true, true],
            '2002700' => [50.88, 'Desodorantes corporais líquidos, exceto CEST 20.027.01 (nº27)', true, true],
            '2002701' => [50.88, 'Loções e óleos desodorantes hidratantes líquidos (nº27.1)', true, true],
            '2002800' => [50.88, 'Antiperspirantes líquidos (nº28)', true, true],
            '2002900' => [52.15, 'Outros desodorantes corporais, exceto CEST 20.029.01 (nº29)', true, true],
            '2002901' => [52.15, 'Outras loções e óleos desodorantes hidratantes (nº29.1)', true, true],
            '2003000' => [52.15, 'Outros antiperspirantes (nº30)', true, true],
            '2003100' => [52.15, 'Sais perfumados e outras preparações para banhos (nº31)', true, true],
            '2003200' => [52.15, 'Outros produtos de perfumaria preparados (nº32)', true, true],
            '2003201' => [52.15, 'Outros produtos de toucador preparados (nº32.1)', true, true],
            '2003300' => [45.00, 'Soluções para lentes de contato ou para olhos artificiais (nº33)', true, true],
            '2003400' => [24.80, 'Sabões de toucador em barras/pedaços/figuras moldados, exceto CEST 20.034.01 (nº34)', false, false],
            '2003401' => [56.55, 'Lenços umedecidos (nº34.1)', false, false],
            '2003500' => [75.00, 'Outros sabões, produtos e preparações, em barras/pedaços/figuras moldados (nº35)', false, false],
            '2003600' => [45.61, 'Sabões de toucador sob outras formas (nº36)', false, false],
            '2003700' => [45.61, 'Produtos e preparações orgânicas tensoativas p/ lavagem da pele, líquido/creme, '
                . 'venda a retalho, mesmo com sabão (nº37)', false, false],
            '2003800' => [66.79, 'Bolsa para gelo ou para água quente (nº38)', false, false],
            '2003900' => [73.69, 'Chupetas e bicos para mamadeiras e para chupetas, de borracha (nº39)', false, false],
            '2004000' => [73.69, 'Chupetas e bicos para mamadeiras e para chupetas, de silicone (nº40)', false, false],
            '2004100' => [58.04, 'Malas e maletas de toucador (nº41)', false, false],
            '2004200' => [53.01, 'Papel higiênico - folha simples (nº42)', false, false],
            '2004300' => [50.54, 'Papel higiênico - folha dupla, tripla e quádrupla (nº43)', false, false],
            '2004400' => [81.71, 'Lenços (incl. os de maquiagem) e toalhas de mão (nº44)', false, false],
            '2004500' => [53.27, 'Papel toalha uso institucional (nº45)', false, false],
            '2004600' => [71.55, 'Toalhas e guardanapos de mesa (nº46)', false, false],
            '2004700' => [71.55, 'Toalhas de cozinha (papel toalha uso doméstico) (nº47)', false, false],
            '2004800' => [42.65, 'Fraldas, exceto CEST 20.048.01 (nº48)', false, false],
            '2004801' => [42.65, 'Fraldas de fibras têxteis (nº48.1)', false, false],
            '2004900' => [59.92, 'Tampões higiênicos (nº49)', false, false],
            '2005000' => [65.37, 'Absorventes higiênicos externos (nº50)', false, false],
            '2005100' => [51.49, 'Hastes flexíveis (uso não medicinal) (nº51)', false, false],
            '2005200' => [53.60, 'Sutiã descartável, assemelhados e papel p/ depilação (nº52)', false, false],
            '2005300' => [59.68, 'Pinças para sobrancelhas (nº53)', false, false],
            '2005400' => [59.68, 'Espátulas (artigos de cutelaria) (nº54)', false, false],
            '2005500' => [59.68, 'Utensílios e sortidos p/ manicuros ou pedicuros (nº55)', false, false],
            '2005600' => [59.20, 'Termômetros, inclusive o digital (nº56)', false, false],
            '2005700' => [58.04, 'Escovas e pincéis de barba/cabelos/cílios/unhas e outras escovas de toucador (nº57)', false, false],
            '2005800' => [61.26, 'Escovas de dentes, incluídas as p/ dentaduras (nº58) -- higiene bucal (não é FEM)', false, false],
            '2005900' => [58.04, 'Pincéis para aplicação de produtos cosméticos (nº59)', false, false],
            '2006000' => [58.04, 'Sortidos de viagem, para toucador de pessoas/costura/limpeza de calçado ou roupas (nº60)', false, false],
            '2006100' => [58.04, 'Pentes, travessas p/ cabelo e artigos semelhantes; grampos; pinças; bobes (nº61)', false, false],
            '2006200' => [58.04, 'Borlas ou esponjas p/ pós ou p/ aplicação de outros cosméticos/toucador (nº62)', false, false],
            '2006300' => [73.69, 'Mamadeiras (nº63)', false, false],
            '2006400' => [64.00, 'Aparelhos e lâminas de barbear (nº64)', false, false],
            '2006500' => [82.49, 'Algodão hidrófilo, não estéril, destinado à higiene pessoal (nº65)', false, false],
        ];

        $regras = [];
        foreach ($itens as $cest => [$mva, $descricao, $in9_7, $femProvavel]) {
            $regras[$cest] = [
                'segmento' => $segmento,
                'descricao' => $descricao,
                'mva_pct' => $mva,
                'aliquota_interna_pct' => $in9_7 ? 25.00 : 18.00,
                'adicional_tipo' => $femProvavel ? 'FEM_MG' : 'nenhum',
                'adicional_pct' => $femProvavel ? 2.00 : null,
                // itens onde o FEM é provável (NBM 33.03-33.07, sem exceção expressa)
                // ficam pendentes de confirmação explícita antes de somar o adicional.
                'adicional_confirmado' => ! $femProvavel,
                'aliquota_confirmada' => true,
                'fonte_legal' => $in9_7
                    ? 'RICMS/MG Anexo VII Parte 2, Segmento 20; Anexo I Parte 1 item 9.7 (25% p/ NBM 33.03-33.07); Decreto nº 48.736/2023 (FEM)'
                    : 'RICMS/MG Anexo VII Parte 2, Segmento 20 -- Âmbito 20.1 (alíquota geral 18%)',
            ];
        }

        return $regras;
    }
}
