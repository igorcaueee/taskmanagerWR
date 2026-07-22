<?php

namespace App\Services\SimplesNacional;

use App\Support\PgdasdAtividades;

/**
 * Parser do relatório de apuração do Simples Nacional exportado em .txt pelo
 * sistema Domínio — permite importar receita por atividade sem digitar tudo
 * manualmente na tela de transmissão.
 *
 * Formato observado (não documentado oficialmente pelo Domínio, inferido a
 * partir de um arquivo real de exemplo em 2026-07-22): linhas separadas por
 * TAB, primeira coluna é um "tipo de linha" que indica o conteúdo, coluna 4
 * (índice 0) é o índice do estabelecimento (0 = nível da declaração/matriz,
 * 1..N = cada estabelecimento, N+1 = totais finais). As colunas de dados após
 * a 20 podem deslocar de posição dependendo de campos opcionais — por isso
 * extraímos os valores pelo texto do rótulo ("Receita Tributada Total:" etc.)
 * em vez de por índice fixo de coluna, o que é mais robusto a variações de
 * layout entre relatórios.
 *
 * Vale revalidar esse parser se o Domínio mudar o formato do relatório, ou se
 * aparecer um caso com múltiplas atividades por estabelecimento, regime misto
 * (competência + caixa), ou situação tributária diferente de "Tributado".
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
     *         atividades: array<int, array{
     *             tabela_texto: ?string,
     *             anexo_texto: ?string,
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
        $ultimaAtividadeIdx = [];

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

            $estabIdx = (int) trim($cols[4] ?? '0');

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
                    $label = $tail[0] ?? '';
                    $total = $this->paraFloat(end($tail) !== $label ? end($tail) : null);
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
                        $estabelecimentos[$estabIdx] = [
                            'cnpj' => preg_replace('/\D/', '', $tail[2]),
                            'nome' => $tail[1],
                            'atividades' => [],
                        ];
                        $ultimaAtividadeIdx[$estabIdx] = -1;
                    }
                    $pendingLabel = null;
                    break;

                case '4': // texto Anexo/Seção/Tabela (pode continuar em linhas sem rótulo)
                    if (! isset($estabelecimentos[$estabIdx]) || empty($tail)) {
                        break;
                    }

                    $atividadeIdx = max($ultimaAtividadeIdx[$estabIdx] ?? -1, 0);
                    $this->garantirAtividade($estabelecimentos[$estabIdx], $atividadeIdx);
                    $ultimaAtividadeIdx[$estabIdx] = $atividadeIdx;

                    $ultimo = end($tail);

                    if (in_array($ultimo, self::LABELS_TEXTO_ATIVIDADE, true)) {
                        $label = rtrim($ultimo, ':');
                        $texto = implode(' ', array_slice($tail, 0, -1));
                        $chave = $label === 'Anexo' ? 'anexo_texto' : ($label === 'Tabela' ? 'tabela_texto' : 'secao_texto');
                        $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx][$chave] = $texto;
                        $pendingLabel = $chave;
                    } elseif ($pendingLabel) {
                        $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx][$pendingLabel] .= ' '.implode(' ', $tail);
                    }
                    break;

                case '5': // Receita Tributada Total / Alíquota / Simples Nacional Total
                    if (! isset($estabelecimentos[$estabIdx])) {
                        break;
                    }

                    $atividadeIdx = max($ultimaAtividadeIdx[$estabIdx] ?? 0, 0);
                    $this->garantirAtividade($estabelecimentos[$estabIdx], $atividadeIdx);

                    $mapa = $this->extrairPorLabel($tail, ['Receita Tributada Total:', 'Alíquota:', 'Simples Nacional Total:']);
                    $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx]['receita_tributada_total'] = $this->paraFloat($mapa['Receita Tributada Total:'][0] ?? null);

                    // Depois desta linha, uma eventual próxima atividade no mesmo estabelecimento
                    // começará um novo índice (o layout do Domínio não numera atividades
                    // explicitamente; usamos a sequência de blocos "tipo 4" pra separar).
                    $ultimaAtividadeIdx[$estabIdx] = $atividadeIdx + 1;
                    break;

                case '6': // Partilha (nomes dos tributos) / Situação
                    if (! isset($estabelecimentos[$estabIdx])) {
                        break;
                    }

                    $atividadeIdx = max(($ultimaAtividadeIdx[$estabIdx] ?? 1) - 1, 0);

                    if (! isset($estabelecimentos[$estabIdx]['atividades'][$atividadeIdx])) {
                        break;
                    }

                    $label = $tail[0] ?? '';
                    $valores = array_slice($tail, 1);

                    if (str_starts_with($label, 'Partilha')) {
                        $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx]['_ordem_tributos'] = $valores;
                    } elseif (str_starts_with($label, 'Situação')) {
                        $ordem = $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx]['_ordem_tributos'] ?? [];
                        $tributos = [];

                        foreach ($ordem as $i => $nomeTributo) {
                            $codTributo = $this->codigoTributoPorNome($nomeTributo);

                            if ($codTributo) {
                                $tributos[] = ['cod_tributo' => $codTributo, 'situacao' => $valores[$i] ?? 'Tributado'];
                            }
                        }

                        $estabelecimentos[$estabIdx]['atividades'][$atividadeIdx]['tributos'] = $tributos;
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
                unset($atividade['_ordem_tributos']);
                $tabelaTexto = $atividade['tabela_texto'] ?? null;
                [$idSugerido, $confianca] = $tabelaTexto
                    ? $this->sugerirIdAtividade($tabelaTexto)
                    : [null, 0.0];

                $atividadesFinal[] = [
                    'tabela_texto' => $tabelaTexto,
                    'anexo_texto' => $atividade['anexo_texto'] ?? null,
                    'id_atividade_sugerido' => $idSugerido,
                    'confianca_match' => $confianca,
                    'receita_tributada_total' => $atividade['receita_tributada_total'] ?? null,
                    'tributos' => $atividade['tributos'] ?? [],
                ];
            }

            $resultado['estabelecimentos'][] = array_merge($dadosNivelDeclaracao, [
                'cnpj' => $dados['cnpj'],
                'nome' => $dados['nome'],
                'atividades' => $atividadesFinal,
            ]);
        }

        return $resultado;
    }

    private function garantirAtividade(array &$estabelecimento, int $idx): void
    {
        if (! isset($estabelecimento['atividades'][$idx])) {
            $estabelecimento['atividades'][$idx] = [];
        }
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
     * Casa o texto da "Tabela" do relatório do Domínio com a descrição
     * correspondente no catálogo oficial de atividades do PGDASD, por
     * similaridade de texto (normalizado, sem acento/pontuação).
     *
     * @return array{0: ?int, 1: float} [idAtividade, confiança 0-1]
     */
    private function sugerirIdAtividade(string $tabelaTexto): array
    {
        $alvo = $this->normalizar($tabelaTexto);
        $melhorId = null;
        $melhorScore = 0.0;

        // similar_text() é baseado em caractere, não em sentido — "Não sujeitos ao
        // fator r" e "Sujeitos ao fator r" têm ~90% de overlap textual apesar de
        // significarem o oposto. Filtra por polaridade (negado ou não) antes de
        // comparar por similaridade, senão o match pode escolher a opção errada
        // com confiança alta.
        $alvoNegado = $this->contemNegacao($alvo);

        foreach (PgdasdAtividades::catalogo() as $id => $info) {
            $candidato = $this->normalizar($info['descricao']);

            if ($this->contemNegacao($candidato) !== $alvoNegado && $this->mencionaConceitoNegavel($alvo, $candidato)) {
                continue;
            }

            similar_text($alvo, $candidato, $percentual);
            $score = $percentual / 100;

            if ($score > $melhorScore) {
                $melhorScore = $score;
                $melhorId = $id;
            }
        }

        return $melhorScore >= 0.5 ? [$melhorId, $melhorScore] : [null, $melhorScore];
    }

    /**
     * Detecta negação explícita tipo "não sujeitos"/"sem retenção" no texto
     * já normalizado (sem acento/pontuação).
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
        foreach (['sujeit', 'retencao', 'substitui'] as $conceito) {
            if (str_contains($alvo, $conceito) && str_contains($candidato, $conceito)) {
                return true;
            }
        }

        return false;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/\bTabela\s*\d+\s*-\s*/ui', '', $texto);
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

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
