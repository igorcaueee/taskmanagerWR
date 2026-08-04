<?php

namespace App\Support;

/**
 * Catálogo de atividades do PGDASD (43 valores) e os tributos aplicáveis a
 * cada uma, para montar a tela de "Receitas por Atividade" (réplica do
 * assistente do e-CAC: PA → RPA → Atividades → Receitas → Resumo).
 *
 * O catálogo de atividades (id/descrição) é confirmado na doc oficial —
 * apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
 * solucoes/integra-sn/pgdasd/dados_de_dominio/ (tabela "Atividades").
 *
 * Já o mapeamento "quais tributos aparecem para cada atividade" é DERIVADO
 * das regras gerais de composição dos Anexos I-V do Simples Nacional
 * (conhecimento contábil padrão: Anexo I/II têm ICMS mas não ISS; Anexo
 * III/IV/V têm ISS mas não ICMS; Anexo IV não tem INSS/CPP no DAS; locação
 * de bens móveis é Anexo III sem ISS; etc.) — NÃO é uma tabela literal da
 * SERPRO por atividade. Vale conferir com um contador/e-CAC antes de confiar
 * 100% nos casos de borda (exportação, incidência simultânea IPI+ISS).
 */
class PgdasdAtividades
{
    // Códigos de tributo confirmados na doc oficial (mesma página acima).
    const TRIBUTO_IRPJ = 1001;
    const TRIBUTO_CSLL = 1002;
    const TRIBUTO_COFINS = 1004;
    const TRIBUTO_PIS = 1005;
    const TRIBUTO_INSS_CPP = 1006;
    const TRIBUTO_ICMS = 1007;
    const TRIBUTO_IPI = 1008;
    const TRIBUTO_ISS = 1010;

    /**
     * Atividades "sujeitas ao fator r" (tributadas pelo Anexo V em vez do
     * III quando a relação folha/receita for baixa) — exigem informar o
     * valor da folha de salário do período na transmissão, confirmado em
     * produção (2026-08-03): a API rejeitou o TRANSDECLARACAO11 com "Existe
     * atividade com folha de salário obrigatória" quando uma atividade 11
     * foi lançada sem esse valor.
     */
    const ATIVIDADES_FATOR_R = [10, 11, 12, 29];

    /**
     * Atividades cuja PRÓPRIA descrição já declara o tratamento do ISS
     * (sem retenção devido a outro/próprio Município, ou com retenção/
     * substituição) — confirmado em produção (2026-08-04): a API rejeitou
     * TRANSDECLARACAO11 com "Qualificação tributária inválida: ISENÇÃO não é
     * permitida para o idAtividade 15 com o tributo ISS" ao tentar marcar o
     * ISS como isento/imune numa atividade que já diz "com retenção/
     * substituição de ISS" — as duas informações são conflitantes (o
     * tratamento do ISS já está fixado pela escolha do idAtividade, não dá
     * pra sobrepor com uma qualificação tributária independente por cima).
     * Não inclui a atividade 9/28 (ISS fixo) porque essas nem têm ISS na
     * lista de tributos. Atividades de exportação de serviço (29/30/31) NÃO
     * entram aqui de propósito: elas não têm essa distinção de retenção na
     * própria descrição, então marcar o ISS como imune (não incidência por
     * exportação) continua válido nelas.
     */
    const ATIVIDADES_ISS_TRATAMENTO_PROPRIO = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];

    /**
     * Subconjunto de ATIVIDADES_ISS_TRATAMENTO_PROPRIO cuja descrição diz
     * "com retenção/substituição de ISS" — nessas, quando o relatório do
     * Domínio mostra "Não incidência" pro ISS, a leitura mais provável é que
     * o ISS foi retido/recolhido pelo TOMADOR do serviço (por isso não
     * compõe o ISS devido pelo prestador na ótica do PGDASD) — ou seja, o
     * qualificador certo é "retenção de ISS" (identificador 11), não
     * "imunidade"/"isenção". HIPÓTESE, ainda não confirmada contra a API
     * real (só sabemos que "imunidade" foi rejeitada pra essas atividades,
     * 2026-08-04) — revalidar no próximo teste.
     */
    const ATIVIDADES_ISS_COM_RETENCAO = [12, 15, 18, 21, 24, 27];

    /**
     * Mesmo padrão de ATIVIDADES_ISS_TRATAMENTO_PROPRIO, mas pro ICMS:
     * Anexo I Comércio (1/2) e Anexo II Indústria (4/5) têm um par de
     * atividades "Sem substituição tributária/tributação monofásica/
     * antecipação com encerramento (substituto tributário do ICMS)" (1/4) e
     * "Com substituição tributária/tributação monofásica/antecipação com
     * encerramento (substituído tributário do ICMS)" (2/5) — o tratamento do
     * ICMS já é definido pela ESCOLHA do idAtividade, não dá pra sobrepor com
     * uma qualificação tributária independente no tributo ICMS por cima.
     * CONFIRMADO em produção (2026-08-04, WEIAND): idAtividade 5 + ICMS
     * marcado como substituicao_tributaria (identificador 8) foi rejeitado
     * com MSG_ISN_008 "Campo 'isencao/identificacao' inválido".
     */
    const ATIVIDADES_ICMS_TRATAMENTO_PROPRIO = [1, 2, 4, 5];

    const NOMES_TRIBUTOS = [
        self::TRIBUTO_IRPJ => 'IRPJ',
        self::TRIBUTO_CSLL => 'CSLL',
        self::TRIBUTO_COFINS => 'COFINS',
        self::TRIBUTO_PIS => 'PIS/Pasep',
        self::TRIBUTO_INSS_CPP => 'INSS/CPP',
        self::TRIBUTO_ICMS => 'ICMS',
        self::TRIBUTO_IPI => 'IPI',
        self::TRIBUTO_ISS => 'ISS',
    ];

    // Conjuntos de tributos por padrão de Anexo, para não repetir a lista em cada atividade.
    private const ANEXO_I_COMERCIO = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_ICMS, self::TRIBUTO_INSS_CPP];
    private const ANEXO_II_INDUSTRIA = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_ICMS, self::TRIBUTO_IPI, self::TRIBUTO_INSS_CPP];
    private const ANEXO_III_SEM_ISS = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_INSS_CPP]; // locação de bens móveis
    private const ANEXO_III_V_SERVICO = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_ISS, self::TRIBUTO_INSS_CPP]; // Anexo III/V com INSS/CPP no DAS
    private const ANEXO_IV_SERVICO = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_ISS]; // Anexo IV: sem INSS/CPP no DAS
    private const ANEXO_III_TRANSPORTE_COMUNICACAO = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_ICMS, self::TRIBUTO_INSS_CPP]; // transporte/comunicação: ICMS, não ISS
    private const IPI_E_ISS = [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_IPI, self::TRIBUTO_ISS, self::TRIBUTO_INSS_CPP];

    /**
     * @return array<int, array{descricao: string, categoria: string, tributos: array<int>}>
     */
    public static function catalogo(): array
    {
        return [
            1 => ['categoria' => 'Revenda de mercadorias, exceto para o exterior', 'descricao' => 'Sem substituição tributária/tributação monofásica/antecipação com encerramento de tributação (substituto tributário do ICMS)', 'tributos' => self::ANEXO_I_COMERCIO],
            2 => ['categoria' => 'Revenda de mercadorias, exceto para o exterior', 'descricao' => 'Com substituição tributária/tributação monofásica/antecipação com encerramento de tributação (substituído tributário do ICMS)', 'tributos' => self::ANEXO_I_COMERCIO],
            3 => ['categoria' => 'Revenda de mercadorias', 'descricao' => 'Revenda de mercadorias para o exterior', 'tributos' => self::ANEXO_I_COMERCIO],

            4 => ['categoria' => 'Venda de mercadorias industrializadas pelo contribuinte, exceto para o exterior', 'descricao' => 'Sem substituição tributária/tributação monofásica/antecipação com encerramento de tributação (substituto tributário do ICMS)', 'tributos' => self::ANEXO_II_INDUSTRIA],
            5 => ['categoria' => 'Venda de mercadorias industrializadas pelo contribuinte, exceto para o exterior', 'descricao' => 'Com substituição tributária/tributação monofásica/antecipação com encerramento de tributação (substituído tributário do ICMS)', 'tributos' => self::ANEXO_II_INDUSTRIA],
            6 => ['categoria' => 'Venda de mercadorias industrializadas pelo contribuinte', 'descricao' => 'Venda de mercadorias industrializadas pelo contribuinte para o exterior', 'tributos' => self::ANEXO_II_INDUSTRIA],

            7 => ['categoria' => 'Locação de bens móveis', 'descricao' => 'Locação de bens móveis, exceto para o exterior', 'tributos' => self::ANEXO_III_SEM_ISS],
            8 => ['categoria' => 'Locação de bens móveis', 'descricao' => 'Locação de bens móveis para o exterior', 'tributos' => self::ANEXO_III_SEM_ISS],

            9 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Escritórios de serviços contábeis autorizados pela legislação municipal a pagar o ISS em valor fixo em guia do Município', 'tributos' => [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_INSS_CPP]], // ISS fixo, fora do DAS
            10 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao fator "r", sem retenção/substituição de ISS, com ISS devido a outro(s) Município(s)', 'tributos' => self::ANEXO_III_V_SERVICO],
            11 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao fator "r", sem retenção/substituição de ISS, com ISS devido ao próprio Município', 'tributos' => self::ANEXO_III_V_SERVICO],
            12 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao fator "r", com retenção/substituição tributária de ISS', 'tributos' => self::ANEXO_III_V_SERVICO],
            13 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Não sujeitos ao fator "r", tributados pelo Anexo III, sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::ANEXO_III_V_SERVICO],
            14 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Não sujeitos ao fator "r", tributados pelo Anexo III, sem retenção de ISS, devido ao próprio Município', 'tributos' => self::ANEXO_III_V_SERVICO],
            15 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Não sujeitos ao fator "r", tributados pelo Anexo III, com retenção/substituição de ISS', 'tributos' => self::ANEXO_III_V_SERVICO],
            16 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao Anexo IV, sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::ANEXO_IV_SERVICO],
            17 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao Anexo IV, sem retenção de ISS, devido ao próprio Município', 'tributos' => self::ANEXO_IV_SERVICO],
            18 => ['categoria' => 'Prestação de serviços, exceto para o exterior', 'descricao' => 'Sujeitos ao Anexo IV, com retenção/substituição tributária de ISS', 'tributos' => self::ANEXO_IV_SERVICO],
            19 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo III, sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::ANEXO_III_V_SERVICO],
            20 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo III, sem retenção de ISS, devido ao próprio Município', 'tributos' => self::ANEXO_III_V_SERVICO],
            21 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo III, com retenção/substituição de ISS', 'tributos' => self::ANEXO_III_V_SERVICO],
            22 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo IV, sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::ANEXO_IV_SERVICO],
            23 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo IV, sem retenção de ISS, devido ao próprio Município', 'tributos' => self::ANEXO_IV_SERVICO],
            24 => ['categoria' => 'Construção civil (LC 116/2003, subitens 7.02/7.05)', 'descricao' => 'Anexo IV, com retenção/substituição de ISS', 'tributos' => self::ANEXO_IV_SERVICO],
            25 => ['categoria' => 'Transporte coletivo municipal', 'descricao' => 'Sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::ANEXO_III_V_SERVICO],
            26 => ['categoria' => 'Transporte coletivo municipal', 'descricao' => 'Sem retenção de ISS, devido ao próprio Município', 'tributos' => self::ANEXO_III_V_SERVICO],
            27 => ['categoria' => 'Transporte coletivo municipal', 'descricao' => 'Com retenção/substituição de ISS', 'tributos' => self::ANEXO_III_V_SERVICO],

            28 => ['categoria' => 'Prestação de serviços para o exterior', 'descricao' => 'Escritórios de serviços contábeis autorizados pela legislação municipal a pagar o ISS em valor fixo em guia do Município', 'tributos' => [self::TRIBUTO_IRPJ, self::TRIBUTO_CSLL, self::TRIBUTO_COFINS, self::TRIBUTO_PIS, self::TRIBUTO_INSS_CPP]],
            29 => ['categoria' => 'Prestação de serviços para o exterior', 'descricao' => 'Sujeitos ao fator "r"', 'tributos' => self::ANEXO_III_V_SERVICO],
            30 => ['categoria' => 'Prestação de serviços para o exterior', 'descricao' => 'Não sujeitos ao fator "r", tributados pelo Anexo III', 'tributos' => self::ANEXO_III_V_SERVICO],
            31 => ['categoria' => 'Prestação de serviços para o exterior', 'descricao' => 'Sujeitos ao Anexo IV', 'tributos' => self::ANEXO_IV_SERVICO],

            32 => ['categoria' => 'Serviços do LC 116/2003 (7.02/7.05), para o exterior', 'descricao' => 'Construção civil tributados pelo Anexo III', 'tributos' => self::ANEXO_III_V_SERVICO],
            33 => ['categoria' => 'Serviços do LC 116/2003 (7.02/7.05), para o exterior', 'descricao' => 'Construção civil tributados pelo Anexo IV', 'tributos' => self::ANEXO_IV_SERVICO],

            34 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, exceto para o exterior', 'descricao' => 'Transporte sem substituição tributária de ICMS', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],
            35 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, exceto para o exterior', 'descricao' => 'Transporte com substituição tributária de ICMS', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],
            36 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, exceto para o exterior', 'descricao' => 'Comunicação sem substituição tributária de ICMS', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],
            37 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, exceto para o exterior', 'descricao' => 'Comunicação com substituição tributária de ICMS', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],
            38 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, para o exterior', 'descricao' => 'Transporte', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],
            39 => ['categoria' => 'Comunicação/transporte intermunicipal e interestadual, para o exterior', 'descricao' => 'Comunicação', 'tributos' => self::ANEXO_III_TRANSPORTE_COMUNICACAO],

            40 => ['categoria' => 'Incidência simultânea de IPI e ISS, exceto para o exterior', 'descricao' => 'Sem retenção de ISS, devido a outro(s) Município(s)', 'tributos' => self::IPI_E_ISS],
            41 => ['categoria' => 'Incidência simultânea de IPI e ISS, exceto para o exterior', 'descricao' => 'Sem retenção de ISS, devido ao próprio Município', 'tributos' => self::IPI_E_ISS],
            42 => ['categoria' => 'Incidência simultânea de IPI e ISS, exceto para o exterior', 'descricao' => 'Com retenção/substituição tributária de ISS', 'tributos' => self::IPI_E_ISS],
            43 => ['categoria' => 'Incidência simultânea de IPI e ISS', 'descricao' => 'Incidência simultânea de IPI e ISS para o exterior', 'tributos' => self::IPI_E_ISS],
        ];
    }

    public static function tributosDaAtividade(int $idAtividade): array
    {
        return self::catalogo()[$idAtividade]['tributos'] ?? [];
    }

    public static function nomeTributo(int $codTributo): string
    {
        return self::NOMES_TRIBUTOS[$codTributo] ?? (string) $codTributo;
    }

    /**
     * Atividade classificada como "mercado externo" (categoria "para o
     * exterior", sem o "exceto") — confirmado em produção (2026-08-04): a
     * API rejeitou o TRANSDECLARACAO11 com "A soma das receitas no mercado
     * interno... informadas em cada atividade é diferente da receita total
     * informada no campo 'Receitas no mercado interno'" porque a receita da
     * atividade 30 ("Prestação de serviços para o exterior") estava sendo
     * somada dentro de receitaPaCompetenciaInterno junto com as atividades
     * domésticas, em vez de compor receitaPaCompetenciaExterno — ver
     * PgdasdService::montarDeclaracao.
     */
    public static function ehParaExterior(int $idAtividade): bool
    {
        $categoria = self::catalogo()[$idAtividade]['categoria'] ?? '';

        return str_contains($categoria, 'para o exterior') && ! str_contains($categoria, 'exceto');
    }
}
