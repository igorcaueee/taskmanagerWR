<?php

namespace App\Services\SimplesNacional;

use App\Support\PgdasdAtividades;

/**
 * Parser do relatório de apuração do Simples Nacional exportado em .txt pelo
 * sistema Domínio — permite importar receita por atividade sem digitar tudo
 * manualmente na tela de transmissão.
 *
 * Formato observado (não documentado oficialmente pelo Domínio, inferido a
 * partir de arquivos reais): linhas separadas por TAB, primeira coluna é um
 * "tipo de linha" que indica o conteúdo. A coluna 4 (índice 0) NÃO é um
 * índice estável de estabelecimento como se assumiu inicialmente — ela
 * incrementa a cada NOVO BLOCO do relatório (cada atividade dentro de um
 * mesmo estabelecimento também ganha um valor novo nessa coluna), então usá-la
 * pra agrupar por estabelecimento descartava silenciosamente toda atividade
 * além da primeira (confirmado com um relatório real em 2026-08-03, que tinha
 * 4 atividades e só 1 aparecia). Por isso o estabelecimento atual é rastreado
 * pela ordem real das linhas "tipo 3" (início de estabelecimento), e a
 * atividade atual pela ordem real das linhas "tipo 4" com rótulo "Anexo:"
 * (todo bloco de atividade observado começa com uma linha "Anexo:").
 *
 * Vale revalidar esse parser se o Domínio mudar o formato do relatório.
 */
class DominioImportParser
{
    private const LABELS_TEXTO_ATIVIDADE = ['Anexo:', 'Seção:', 'Tabela:'];

    /**
     * @return array{
     *     periodo_apuracao: ?string,
     *     estabelecimentos: array<int, array{
     *         cnpj: string,
     *         nome: string,
     *         rbt12: ?float,
     *         rba_atual: ?float,
     *         rba_anterior: ?float,
     *         rpa_competencia: ?float,
     *         rpa_caixa: ?float,
     *         anexo_sugerido: ?string,
     *         atividades: array<int, array{
     *             tabela_texto: ?string,
     *             secao_texto: ?string,
     *             anexo_texto: ?string,
     *             anexo_romano: ?string,
     *             id_atividade_sugerido: ?int,
     *             confianca_match: float,
     *             receita_tributada_total: ?float,
     *             tributos: array<int, array{cod_tributo: int, situacao: string}>,
     *         }>,
     *     }>,
     * }
     */
    public function parse(string $conteudo): array
    {
        $conteudo = $this->paraUtf8($conteudo);
        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);

        $periodoApuracao = null;
        $estabelecimentos = [];
        $dadosNivelDeclaracao = ['rbt12' => null, 'rba_atual' => null, 'rba_anterior' => null, 'rpa_competencia' => null, 'rpa_caixa' => null];
        $pendingLabel = null;
        $estabAtual = null;
        $atividadeAtual = null;

        foreach ($linhas as $linha) {
            if (trim($linha) === '') {
                continue;
            }

            $cols = explode("\t", $linha);
            array_pop($cols); // última coluna é sempre um marcador de agrupamento do relatório, não é conteúdo
            $tipo = trim($cols[0] ?? '');

            if ($tipo === '') {
                continue;
            }

            if ($periodoApuracao === null) {
                $periodoStr = trim($cols[18] ?? ''); // dd/mm/yyyy, primeiro dia do período
                if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $periodoStr, $m)) {
                    $periodoApuracao = $m[3].$m[2];
                }
            }

            $tail = [];
            foreach (array_slice($cols, 20) as $v) {
                $v = trim($v);
                if ($v !== '') {
                    $tail[] = $v;
                }
            }

            switch ($tipo) {
                case '17':
                    // Depois do rótulo vêm 3 números nessa ordem fixa: Mercado
                    // Interno, Mercado Externo, Total (confirmado contra o
                    // relatório em PDF, que tem essas 3 colunas explícitas) —
                    // usamos SEMPRE o Total ($tail[3]), não o Interno
                    // ($tail[1]), senão empresa com receita de exportação
                    // ficava com RPA/RBT12/RBA sem a parte externa. Usar uma
                    // posição fixa (não o ÚLTIMO item da linha) também evita
                    // quebrar quando o Domínio repete esse mesmo "tipo 17"
                    // numa seção de recap no fim do relatório com um "Anexo:"
                    // grudado no final da linha (confirmado com um relatório
                    // real em 2026-08-03): o último item vira "Anexo:" (não
                    // numérico), mas a posição 3 continua sendo o Total.
                    $label = $tail[0] ?? '';
                    $total = $this->paraFloat($tail[3] ?? $tail[1] ?? null);
                    $labelNormalizado = $this->normalizar($label);

                    if (str_contains($labelNormalizado, 'competencia')) {
                        $dadosNivelDeclaracao['rpa_competencia'] = $total;
                    } elseif (str_contains($labelNormalizado, 'caixa')) {
                        $dadosNivelDeclaracao['rpa_caixa'] = $total;
                    } elseif (str_contains($labelNormalizado, 'rbt12')) {
                        $dadosNivelDeclaracao['rbt12'] = $total;
                    } elseif (str_contains($labelNormalizado, 'corrente')) {
                        $dadosNivelDeclaracao['rba_atual'] = $total;
                    } elseif (str_contains($labelNormalizado, 'anterior')) {
                        $dadosNivelDeclaracao['rba_anterior'] = $total;
                    }
                    break;

                case '3': // início de um estabelecimento: tail = [id, nome, cnpj]
                    if (count($tail) >= 3) {
                        $estabAtual = count($estabelecimentos);
                        $estabelecimentos[$estabAtual] = [
                            'cnpj' => preg_replace('/\D/', '', $tail[2]),
                            'nome' => $tail[1],
                            'atividades' => [],
                        ];
                        $atividadeAtual = null;
                    }
                    $pendingLabel = null;
                    break;

                case '4': // texto Anexo/Seção/Tabela (pode continuar em linhas sem rótulo)
                    if ($estabAtual === null || empty($tail)) {
                        break;
                    }

                    $ultimo = end($tail);

                    if (in_array($ultimo, self::LABELS_TEXTO_ATIVIDADE, true)) {
                        $label = rtrim($ultimo, ':');
                        $texto = implode(' ', array_slice($tail, 0, -1));
                        $chave = $label === 'Anexo' ? 'anexo_texto' : ($label === 'Tabela' ? 'tabela_texto' : 'secao_texto');

                        if ($chave === 'anexo_texto') {
                            // toda atividade observada começa com uma linha "Anexo:" —
                            // marca o início de um novo bloco de atividade.
                            $atividadeAtual = count($estabelecimentos[$estabAtual]['atividades']);
                            $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual] = [];
                        }

                        if ($atividadeAtual === null) {
                            break; // texto solto antes de qualquer "Anexo:" nesse estabelecimento
                        }

                        $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual][$chave] = $texto;
                        $pendingLabel = $chave;
                    } elseif ($pendingLabel && $atividadeAtual !== null) {
                        $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual][$pendingLabel] .= ' '.implode(' ', $tail);
                    }
                    break;

                case '5': // Receita Tributada Total / Alíquota / Simples Nacional Total
                    if ($estabAtual === null || $atividadeAtual === null) {
                        break;
                    }

                    $mapa = $this->extrairPorLabel($tail, ['Receita Tributada Total:', 'Alíquota:', 'Simples Nacional Total:']);
                    $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual]['receita_tributada_total'] = $this->paraFloat($mapa['Receita Tributada Total:'][0] ?? null);
                    break;

                case '6': // Partilha (nomes dos tributos) / Situação
                    if ($estabAtual === null || $atividadeAtual === null) {
                        break;
                    }

                    $label = $tail[0] ?? '';
                    $valores = array_slice($tail, 1);

                    if (str_starts_with($label, 'Partilha')) {
                        $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual]['_ordem_tributos'] = $valores;
                    } elseif (str_starts_with($label, 'Situação')) {
                        $ordem = $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual]['_ordem_tributos'] ?? [];
                        $tributos = [];

                        foreach ($ordem as $i => $nomeTributo) {
                            $codTributo = $this->codigoTributoPorNome($nomeTributo);

                            if ($codTributo) {
                                $situacao = $valores[$i] ?? 'Tributado';

                                $tributos[] = [
                                    'cod_tributo' => $codTributo,
                                    'situacao' => $situacao,
                                    'tipo_ajuste_sugerido' => $this->mapearSituacaoParaTipoAjuste($situacao),
                                ];
                            }
                        }

                        $estabelecimentos[$estabAtual]['atividades'][$atividadeAtual]['tributos'] = $tributos;
                    }
                    break;
            }
        }

        // Remove campos internos de controle e monta o resultado final, já com
        // a sugestão de idAtividade calculada por similaridade de texto.
        $resultado = ['periodo_apuracao' => $periodoApuracao, 'estabelecimentos' => []];

        foreach ($estabelecimentos as $dados) {
            $atividadesFinal = [];

            foreach ($dados['atividades'] as $atividade) {
                $tabelaTexto = $atividade['tabela_texto'] ?? null;
                $secaoTexto = $atividade['secao_texto'] ?? null;
                $anexoTexto = $atividade['anexo_texto'] ?? null;
                $receitaTotal = $atividade['receita_tributada_total'] ?? null;

                // Descarta blocos que não são atividade de verdade (ex.: um recap
                // de RBT12/RBA que o Domínio repete com um "Anexo:" solto no fim
                // do relatório, sem Tabela nem Receita Tributada Total nenhuma).
                if ($tabelaTexto === null && $receitaTotal === null) {
                    continue;
                }

                [$idSugerido, $confianca] = $tabelaTexto
                    ? $this->sugerirIdAtividade($anexoTexto, $secaoTexto, $tabelaTexto)
                    : [null, 0.0];

                $atividadesFinal[] = [
                    'tabela_texto' => $tabelaTexto,
                    'secao_texto' => $secaoTexto,
                    'anexo_texto' => $anexoTexto,
                    'anexo_romano' => $this->extrairAnexoRomano($anexoTexto),
                    'id_atividade_sugerido' => $idSugerido,
                    'confianca_match' => $confianca,
                    'receita_tributada_total' => $receitaTotal,
                    'tributos' => $atividade['tributos'] ?? [],
                ];
            }

            $resultado['estabelecimentos'][] = array_merge($dadosNivelDeclaracao, [
                'cnpj' => $dados['cnpj'],
                'nome' => $dados['nome'],
                'anexo_sugerido' => $this->anexoMaisFrequente($atividadesFinal),
                'atividades' => $atividadesFinal,
            ]);
        }

        return $resultado;
    }

    /**
     * Procura, dentro dos tokens de uma linha, cada rótulo em $labels e retorna
     * os tokens que vêm logo depois dele até o próximo rótulo conhecido.
     */
    private function extrairPorLabel(array $tokens, array $labels): array
    {
        $resultado = [];
        $labelAtual = null;

        foreach ($tokens as $token) {
            if (in_array($token, $labels, true)) {
                $labelAtual = $token;
                $resultado[$labelAtual] = [];

                continue;
            }

            if ($labelAtual !== null) {
                $resultado[$labelAtual][] = $token;
            }
        }

        return $resultado;
    }

    private function codigoTributoPorNome(string $nome): ?int
    {
        $nome = strtoupper(trim($nome));

        return match ($nome) {
            'IRPJ' => PgdasdAtividades::TRIBUTO_IRPJ,
            'CSLL' => PgdasdAtividades::TRIBUTO_CSLL,
            'COFINS' => PgdasdAtividades::TRIBUTO_COFINS,
            'PIS', 'PIS/PASEP' => PgdasdAtividades::TRIBUTO_PIS,
            'INSS/CPP', 'INSS', 'CPP' => PgdasdAtividades::TRIBUTO_INSS_CPP,
            'ICMS' => PgdasdAtividades::TRIBUTO_ICMS,
            'IPI' => PgdasdAtividades::TRIBUTO_IPI,
            'ISS' => PgdasdAtividades::TRIBUTO_ISS,
            default => null,
        };
    }

    /**
     * Traduz a "Situação" do tributo no relatório do Domínio pro
     * "tipo_ajuste" usado em SimplesReceitaAtividadeTributo (mesmo enum da
     * tela manual de "Atividades e receitas").
     *
     * "Não incidência" mapeia pra "imunidade" — não existe um código
     * "não incidência" separado na tabela oficial "Qualificação Tributária"
     * do PGDASD (só imunidade/lançamento de ofício/substituição tributária/
     * tributação monofásica/antecipação com encerramento/retenção de ISS);
     * a imunidade constitucional sobre exportação (o caso mais comum de
     * "não incidência" nos relatórios) é justamente coberta por esse código.
     * "Retenção"/"Retido" mapeia pra retencao_iss — só faz sentido pro ISS,
     * mas não há problema em aplicar o mesmo tipo pra outro tributo aqui
     * (o valor final só é usado se o código de tributo permitir na API).
     *
     * Qualquer situação não reconhecida cai em "normal" (comportamento
     * anterior, quando isso só virava um aviso pra revisão manual) — o
     * chamador ainda pode usar $situacaoReconhecida para saber se precisa
     * avisar o usuário.
     */
    private function mapearSituacaoParaTipoAjuste(string $situacao): string
    {
        $normalizado = $this->normalizar($situacao);

        return match (true) {
            str_contains($normalizado, 'tributado') => 'normal',
            str_contains($normalizado, 'incidencia') => 'imunidade', // "Não incidência"
            str_contains($normalizado, 'monof') => 'tributacao_monofasica', // "Trib. Monof."
            str_contains($normalizado, 'subst') => 'substituicao_tributaria', // "Subst. Trib."
            str_contains($normalizado, 'retid') || str_contains($normalizado, 'retenc') => 'retencao_iss',
            str_contains($normalizado, 'isent') => 'isencao',
            str_contains($normalizado, 'imune') => 'imunidade',
            default => 'normal',
        };
    }

    /**
     * Extrai o algarismo romano do Anexo (I a V) do texto "Anexo I - Comércio" etc.
     */
    private function extrairAnexoRomano(?string $anexoTexto): ?string
    {
        if ($anexoTexto && preg_match('/\bAnexo\s+(IV|III|II|I|V)\b/ui', $anexoTexto, $m)) {
            return mb_strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Anexo predominante entre as atividades de um estabelecimento — usado pra
     * sugerir preenchimento do campo "Anexo" cadastrado do cliente.
     */
    private function anexoMaisFrequente(array $atividades): ?string
    {
        $contagem = array_count_values(array_filter(array_column($atividades, 'anexo_romano')));

        if (empty($contagem)) {
            return null;
        }

        arsort($contagem);

        return array_key_first($contagem);
    }

    /**
     * Palavra-chave do texto do relatório (Anexo/Seção) => trecho que precisa
     * aparecer na "categoria" do catálogo — usado pra restringir o fuzzy
     * match a só um grupo de atividades plausível antes de comparar por
     * similaridade de texto. Sem esse filtro, uma descrição curta e não
     * relacionada (ex.: atividade 36, "Comunicação... sem substituição
     * tributária de ICMS") pode bater mais caracteres com o texto de entrada
     * do que a atividade certa, só por acaso de forma — confirmado com um
     * relatório real em 2026-08-03 (ver docblock de sugerirIdAtividade).
     * Checado nessa ordem (mais específico primeiro) pra não deixar um termo
     * genérico como "servic" capturar um caso mais específico como
     * "comunicac"/"transport"/"construc".
     */
    private const CATEGORIA_POR_PALAVRA_CHAVE = [
        // "fator" ("fator r") e "retencao" (retenção de ISS) só existem no
        // catálogo pra atividades de prestação de serviços — checados ANTES
        // de "locac", porque o Anexo III se chama oficialmente "Locação de
        // Bens Móveis E Prestação de Serviços" no relatório do Domínio (as
        // duas palavras aparecem juntas no nome do Anexo mesmo quando a
        // atividade real é só prestação de serviço), o que fazia o match
        // cair sempre no grupo errado (locação, ids 7/8) só por conter a
        // palavra "locação" no texto — confirmado com um relatório real em
        // 2026-08-03.
        'fator' => 'prestacao de servicos',
        'retencao' => 'prestacao de servicos',
        'industrializad' => 'venda de mercadorias industrializadas',
        'comunicac' => 'comunicacao',
        'transport' => 'transporte',
        'construc' => 'construcao civil',
        'locac' => 'locacao de bens moveis',
        'comerc' => 'revenda de mercadorias',
        'servic' => 'prestacao de servicos',
    ];

    /**
     * Casa o texto Anexo+Seção+Tabela do relatório do Domínio com a descrição
     * correspondente no catálogo oficial de atividades do PGDASD, por
     * similaridade de texto (normalizado, sem acento/pontuação).
     *
     * Usa Anexo+Seção+Tabela combinados (não só a Tabela) por dois motivos,
     * confirmados com um relatório real em 2026-08-03: (1) o mesmo texto de
     * Tabela pode se repetir em Seções diferentes com significados opostos
     * (ex.: "Tabela 1" aparece tanto na Seção "não sujeitas a substituição"
     * quanto na Seção "sujeitas a substituição" do mesmo Anexo/relatório);
     * (2) a comparação por caractere (similar_text) penaliza descrições
     * corretas mas longas do catálogo quando o texto de entrada é curto — ex.
     * a Tabela "Sem substituição tributária" batia mais com a atividade 34
     * ("Transporte sem substituição tributária de ICMS", 74%) do que com a
     * atividade 1 (correta, mas com uma descrição bem mais longa, por isso
     * proporcionalmente "menos parecida"). Combinar Seção+Anexo ajuda, mas
     * não resolve sozinho — o pré-filtro por categoria (CATEGORIA_POR_
     * PALAVRA_CHAVE) que efetivamente evita comparar "Comércio" com
     * "Comunicação"/"Transporte".
     *
     * @return array{0: ?int, 1: float} [idAtividade, confiança 0-1]
     */
    private function sugerirIdAtividade(?string $anexoTexto, ?string $secaoTexto, string $tabelaTexto): array
    {
        $alvo = $this->normalizar(implode(' ', array_filter([$anexoTexto, $secaoTexto, $tabelaTexto])));
        $catalogo = PgdasdAtividades::catalogo();

        $catalogoFiltrado = null;
        $categoriaFiltrada = null;
        foreach (self::CATEGORIA_POR_PALAVRA_CHAVE as $palavraChave => $trechoCategoria) {
            if (! str_contains($alvo, $palavraChave)) {
                continue;
            }

            $subset = array_filter($catalogo, fn (array $info) => str_contains($this->normalizar($info['categoria']), $trechoCategoria));

            if (! empty($subset)) {
                $catalogoFiltrado = $subset;
                $categoriaFiltrada = $trechoCategoria;
                break;
            }
        }

        $melhorId = null;
        $melhorScore = 0.0;

        // similar_text() é baseado em caractere, não em sentido — "Não sujeitos ao
        // fator r" e "Sujeitos ao fator r" têm ~90% de overlap textual apesar de
        // significarem o oposto. Filtra por polaridade (negado ou não) antes de
        // comparar por similaridade, senão o match pode escolher a opção errada
        // com confiança alta.
        $alvoNegado = $this->contemNegacao($alvo);
        $alvoSubstituicao = $this->polaridadeSubstituicao($alvo);
        $alvoExterior = $this->polaridadeExterior($alvo);

        // Para revenda/venda de mercadorias, o texto da Seção é ambíguo: tanto
        // "Substituição tributária somente do ICMS" quanto "... somente do
        // PIS/PASEP e da COFINS" ficam sob a mesma Seção II ("sujeitas a
        // substituição tributária, exceto as receitas decorrentes de
        // exportação"), então polaridadeSubstituicao($alvo) sempre bate como
        // "com substituição" pras duas — mesmo quando só PIS/COFINS são
        // monofásicos e o ICMS continua Tributado/Normal (confirmado com um
        // relatório real em 2026-08-07: Tabela 7 "somente do PIS/PASEP e da
        // COFINS" casava com a atividade 2, cuja descrição fala de ICMS
        // SUBSTITUÍDO). Quem decide de fato se o ICMS está substituído é o
        // texto da Tabela, não da Seção — sobrescreve o sinal genérico só
        // quando a categoria já filtrada é de comércio/indústria (únicas com
        // a distinção ATIVIDADES_ICMS_SEM_SUBSTITUICAO/SUBSTITUIDO); em
        // outras categorias (ex.: "substituição/retenção de ISS") o texto da
        // Tabela não fala de ICMS e esse sinal não se aplica.
        if (in_array($categoriaFiltrada, ['revenda de mercadorias', 'venda de mercadorias industrializadas'], true)) {
            $polaridadeIcms = $this->polaridadeSubstituicaoIcms($tabelaTexto);
            if ($polaridadeIcms !== null) {
                $alvoSubstituicao = $polaridadeIcms;
            }
        }

        $sobreviventes = 0;

        foreach ($catalogoFiltrado ?? $catalogo as $id => $info) {
            $candidato = $this->normalizar($info['categoria'].' '.$info['descricao']);

            if ($this->contemNegacao($candidato) !== $alvoNegado && $this->mencionaConceitoNegavel($alvo, $candidato)) {
                continue;
            }

            $candSubstituicao = $this->polaridadeSubstituicao($candidato);
            if ($alvoSubstituicao !== null && $candSubstituicao !== null && $alvoSubstituicao !== $candSubstituicao) {
                continue;
            }

            $candExterior = $this->polaridadeExterior($candidato);
            if ($alvoExterior !== null && $candExterior !== null && $alvoExterior !== $candExterior) {
                continue;
            }

            $sobreviventes++;
            similar_text($alvo, $candidato, $percentual);
            $score = $percentual / 100;

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhorId = $id;
            }
        }

        // Se o pré-filtro de categoria + os filtros de polaridade (substituição,
        // comércio exterior) deixaram só 1 atividade possível, confiamos nela
        // mesmo com score bruto abaixo de 0.5 — a similaridade por caractere
        // penaliza descrições corretas mas verbosas do catálogo (ex.: atividade 1
        // menciona "tributação monofásica"/"antecipação com encerramento" que não
        // aparecem no relatório do Domínio), e nesse ponto já não é mais uma
        // questão de "parecença de texto", é a única opção estruturalmente
        // compatível.
        if ($sobreviventes === 1 && $melhorId !== null) {
            return [$melhorId, max($melhorScore, 0.75)];
        }

        // Com múltiplos sobreviventes, o score bruto de similaridade de texto
        // continua penalizando descrições verbosas mesmo dentro do grupo já
        // restrito por categoria/polaridade — confirmado com um relatório
        // real em 2026-08-03 (Anexo III "Locação de Bens Móveis e Prestação
        // de Serviços" citado no nome do próprio Anexo mesmo pra atividades
        // que são só prestação de serviço, deixando algumas descrições do
        // catálogo sem "sujeito"/"retenção"/"substituição" — e portanto fora
        // do filtro de polaridade — mesmo sendo conceitualmente distantes).
        // Por isso não descartamos mais o melhor candidato só por estar
        // abaixo de 0.5: a tela sempre mostra o "% de confiança" e deixa
        // corrigir manualmente, então um palpite de baixa confiança ainda é
        // mais útil que nenhuma sugestão.
        return [$melhorId, $melhorScore];
    }

    /**
     * Detecta negação explícita tipo "não sujeitos ao fator r" no texto já
     * normalizado (sem acento/pontuação). Só cobre "não" — "sem"/"exceto"
     * de propósito NÃO entram aqui (testado e revertido em 2026-08-03): eles
     * aparecem em trechos do catálogo sem relação com o conceito que se quer
     * negar (ex.: "revenda de mercadorias EXCETO para o exterior" tem
     * "exceto" mas não é sobre substituição tributária), contaminando esse
     * filtro genérico. Os conceitos "substituição tributária" e "comércio
     * exterior" — que realmente precisam de "sem"/"exceto" como negação —
     * têm checagem própria e localizada em polaridadeSubstituicao/
     * polaridadeExterior, exatamente pra evitar essa contaminação.
     */
    private function contemNegacao(string $textoNormalizado): bool
    {
        return (bool) preg_match('/\bnao\b/', $textoNormalizado);
    }

    /**
     * Só aplica o filtro de polaridade quando os dois textos realmente falam do
     * mesmo conceito negável (ex.: "sujeito"), evitando descartar candidatos
     * válidos por uma negação de assunto totalmente diferente.
     */
    private function mencionaConceitoNegavel(string $alvo, string $candidato): bool
    {
        foreach (['sujeit', 'retencao'] as $conceito) {
            if (str_contains($alvo, $conceito) && str_contains($candidato, $conceito)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Polaridade de "substituição tributária" (null = conceito não mencionado
     * no texto, então não filtra por ele): olha a palavra imediatamente antes
     * de "substitui" pra decidir se é negado ("sem"/"não sujeito a") ou
     * afirmado ("com"/"sujeito a") — precisa ser localizado (não um "contém
     * 'sem' em qualquer lugar da string") porque o catálogo usa "exceto para
     * o exterior" no mesmo trecho, o que não tem nada a ver com substituição.
     */
    private function polaridadeSubstituicao(string $textoNormalizado): ?bool
    {
        if (! str_contains($textoNormalizado, 'substitui')) {
            return null;
        }

        if (preg_match('/\b(sem|nao[a-z ]{0,20}sujeit\w*)\b[a-z ]{0,60}substitui/', $textoNormalizado)) {
            return true;
        }

        if (preg_match('/\b(com|sujeit\w*)\b[a-z ]{0,60}(a )?substitui/', $textoNormalizado)) {
            return false;
        }

        return null;
    }

    /**
     * Polaridade de "substituição tributária" restrita ao que realmente
     * importa pro par de atividades 1/2 (e 4/5, indústria) do catálogo: se o
     * ICMS DA PRÓPRIA ATIVIDADE está substituído ou não — não se há
     * substituição tributária de QUALQUER tributo na tabela. O relatório do
     * Domínio tem tabelas de substituição que não envolvem ICMS nenhum (ex.:
     * "Tabela 7 - Substituição tributária somente do PIS/PASEP e da COFINS"),
     * nas quais o ICMS continua "Tributado"/Normal — mesma convenção de
     * retorno de polaridadeSubstituicao() (true = negado/"sem substituição
     * de ICMS", false = afirmado/"com substituição de ICMS", null = tabela
     * não fala de substituição).
     */
    private function polaridadeSubstituicaoIcms(string $tabelaTexto): ?bool
    {
        $t = $this->normalizar($tabelaTexto);

        if (! str_contains($t, 'substitui')) {
            return null;
        }

        if (preg_match('/\bsem\b[a-z ]{0,20}substitui/', $t)) {
            return true;
        }

        if (str_contains($t, 'icms')) {
            return false;
        }

        // Fala de substituição tributária mas não cita ICMS no texto da
        // Tabela (ex.: "somente do PIS/PASEP e da COFINS") -> a substituição
        // é de outro tributo, o ICMS da atividade permanece normal.
        return true;
    }

    /**
     * Polaridade de "comércio exterior" (null = não mencionado): "exceto ...
     * exterior" = atividade doméstica (negado); "para o exterior" sem
     * "exceto" perto = atividade de exportação (afirmado).
     */
    private function polaridadeExterior(string $textoNormalizado): ?bool
    {
        if (! str_contains($textoNormalizado, 'exterior')) {
            return null;
        }

        return ! (bool) preg_match('/\bexceto\b[a-z ]{0,60}exterior/', $textoNormalizado);
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/\bTabela\s*\d+\s*-\s*/ui', '', $texto);
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);
        // "Exportação"/"exportar" é sinônimo de "exterior" pro fim de detectar a
        // polaridade de comércio exterior (o relatório do Domínio usa um termo,
        // o catálogo oficial usa o outro) — sem isso os dois nunca "conversam"
        // no filtro de polaridade de mencionaConceitoNegavel/contemNegacao.
        $texto = preg_replace('/\bexportac(ao|oes)\b/', 'exterior', $texto);

        return trim($texto);
    }

    private function paraFloat(?string $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $normalizado = str_replace(['.', ','], ['', '.'], $valor);

        return is_numeric($normalizado) ? (float) $normalizado : null;
    }

    /**
     * O Domínio exporta em ISO-8859-1/Windows-1252 (sistema legado), não
     * UTF-8 — sem converter, acentos quebram o json_encode da resposta com
     * "Malformed UTF-8 characters".
     */
    private function paraUtf8(string $conteudo): string
    {
        if (mb_check_encoding($conteudo, 'UTF-8')) {
            return $conteudo;
        }

        $detectado = mb_detect_encoding($conteudo, ['ISO-8859-1', 'Windows-1252', 'UTF-8'], true) ?: 'ISO-8859-1';

        return mb_convert_encoding($conteudo, 'UTF-8', $detectado);
    }
}
