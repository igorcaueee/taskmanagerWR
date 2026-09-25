<?php

namespace App\Services\IcmsSt;

use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use App\Models\StCalculo;
use App\Models\StOverrideBase;
use App\Models\StOverrideCest;
use App\Models\StRegraCest;
use Illuminate\Support\Facades\Log;

/**
 * Base comum dos motores de ICMS-ST por UF de destino (RS/MG) — porta fiel
 * de calcular_antecipacao_st_rs.py / calcular_antecipacao_st_mg.py. Ver
 * PROMPT_LARAVEL_ICMS_ST.md e o plano do módulo para os princípios
 * inegociáveis (nunca inventar dado tributário, sempre bloquear na incerteza,
 * CFOP nunca exclui item do cálculo).
 *
 * O que realmente diverge entre RS e MG é só a seleção da MVA ORIGINAL (RS
 * usa a coluna "operação interna" e valida a alíquota interestadual, MG tem
 * 1 coluna única) — por
 * isso só esse passo é abstrato; extração de item, resolução de overrides,
 * ajuste de MVA (Convênio 142/18), fórmula e persistência são idênticos e
 * ficam aqui. resolverMva() das subclasses NUNCA retorna a MVA pronta pra
 * aplicar -- retorna a MVA original da tabela, e ajustarMva() (nesta classe)
 * sempre a ajusta antes de calcular a base ST.
 */
abstract class AbstractCalculadoraSt
{
    use XmlHelpers;

    /** Sigla da UF de destino que este motor sabe calcular (RS ou MG). */
    abstract protected function ufSuportada(): string;

    /**
     * Resolve a MVA ORIGINAL (nacional, da tabela) para a regra/alíquota
     * interestadual do item -- NUNCA a MVA ajustada (isso é feito depois,
     * de forma centralizada, por ajustarMva()).
     *
     * @return array{mva: ?float, status: ?string, detalhe: ?string} status/detalhe
     *         preenchidos quando o item deve ficar pendente em vez de calculado.
     */
    abstract protected function resolverMva(StRegraCest $regra, ?float $pIcmsInterestadual): array;

    /** Texto do campo "responsavel", com qualquer nota específica da UF (ex.: sem acordo). */
    protected function notaResponsavel(StRegraCest $regra, string $ufOrigem): string
    {
        return 'destinatário (antecipação -- item sem ICMS-ST destacado na origem)';
    }

    /**
     * MVA ajustada -- Convênio ICMS 142/18, cláusula segunda: a MVA
     * ORIGINAL (nacional, cadastrada em st_regras_cest) nunca é aplicada
     * direto na base de cálculo -- sempre é ajustada pela diferença entre
     * a alíquota interestadual efetivamente praticada na operação e a
     * alíquota interna do Estado de destino para aquela mercadoria.
     * Decisão de Igor Caue, 24/09/2026, válida para os dois motores.
     */
    private function ajustarMva(float $mvaOriginal, float $aliquotaInterna, float $aliquotaInterestadual): float
    {
        $fator = (1 + $mvaOriginal / 100) * (1 - $aliquotaInterestadual / 100) / (1 - $aliquotaInterna / 100) - 1;

        return round($fator * 100, 2);
    }

    /**
     * @return array{totalDocumentos: int, processadas: int, itens: int, pendentes: int, semXmlCompleto: int}
     */
    public function calcularParaCliente(Cliente $cliente, string $dataInicio, string $dataFim): array
    {
        $documentos = DocumentoFiscal::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'nfe')
            ->whereNotNull('xml_content')
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->cursor();

        $totalDocumentos = 0;
        $processadas = 0;
        $itens = 0;
        $pendentes = 0;
        $semXmlCompleto = 0;

        foreach ($documentos as $documento) {
            $totalDocumentos++;
            $resultado = $this->processarDocumento($documento);

            if ($resultado === null) {
                // XML só com o resumo da distribuição (resNFe, sem <det>) ou
                // inválido -- não é "sem ST devida", é "ainda não dá pra saber".
                // Nunca conta como calculado nem como pendente silenciosamente;
                // o controller avisa o usuário para baixar o XML completo.
                $semXmlCompleto++;
                continue;
            }

            $processadas++;
            $itens += $resultado['itens'];
            $pendentes += $resultado['pendentes'];
        }

        return compact('totalDocumentos', 'processadas', 'itens', 'pendentes', 'semXmlCompleto');
    }

    /**
     * @return array{itens: int, pendentes: int}|null null quando o XML só tem o resumo
     *         da distribuição (resNFe, sem <det>) ou não pôde ser parseado.
     */
    private function processarDocumento(DocumentoFiscal $documento): ?array
    {
        libxml_use_internal_errors(true);

        try {
            $raiz = new \SimpleXMLElement($documento->xml_content);
        } catch (\Throwable $e) {
            Log::warning('[ICMS-ST] XML inválido, item ignorado', [
                'chave_acesso' => $documento->chave_acesso,
                'msg' => $e->getMessage(),
            ]);

            return null;
        }

        $infNFe = self::descendente($raiz, 'infNFe');

        if (! $infNFe) {
            // resNFe (resumo, sem <det>) — nada a calcular ainda para este documento.
            return null;
        }

        $ide = self::filho($infNFe, 'ide');
        $emit = self::filho($infNFe, 'emit');
        $dest = self::filho($infNFe, 'dest');
        $enderEmit = self::filho($emit, 'enderEmit');
        $enderDest = self::filho($dest, 'enderDest');

        $chave = preg_replace('/^NFe/', '', trim((string) $infNFe['Id'])) ?: $documento->chave_acesso;
        $numero = self::txt($ide, 'nNF') ?: $documento->numero;
        $ufOrigem = self::txt($enderEmit, 'UF');
        $ufDestino = self::txt($enderDest, 'UF');

        $itens = 0;
        $pendentes = 0;

        if ($ufDestino !== $this->ufSuportada()) {
            StCalculo::updateOrCreate(
                ['chave_acesso' => $chave, 'nfe_item' => '0'],
                [
                    'cliente_id' => $documento->cliente_id,
                    'nfe_numero' => $numero,
                    'produto' => '(NF-e inteira)',
                    'uf_origem' => $ufOrigem,
                    'uf_destino' => $ufDestino,
                    'status' => 'uf_nao_suportada',
                    'status_detalhe' => "NF-e com destinatário em {$ufDestino} -- este motor só possui "
                        . "regras cadastradas para destinatários no {$this->ufSuportada()}. Nenhum item "
                        . 'desta NF-e foi calculado. Não presumir que a mercadoria está isenta de ST.',
                ]
            );

            return ['itens' => 1, 'pendentes' => 1];
        }

        foreach (self::filhos($infNFe, 'det') as $det) {
            $this->processarItem($documento, $chave, $numero, $ufOrigem, $ufDestino, $det, $itens, $pendentes);
        }

        return ['itens' => $itens, 'pendentes' => $pendentes];
    }

    private function processarItem(
        DocumentoFiscal $documento,
        string $chave,
        ?string $numero,
        ?string $ufOrigem,
        ?string $ufDestino,
        \SimpleXMLElement $det,
        int &$itens,
        int &$pendentes,
    ): void {
        $itens++;
        $nItem = (string) ($det['nItem'] ?? '');
        $prod = self::filho($det, 'prod');
        $cfop = self::txt($prod, 'CFOP');
        $ncm = self::txt($prod, 'NCM');
        $xProd = self::txt($prod, 'xProd');
        $vProd = self::num($prod, 'vProd') ?? 0.0;
        $cestXml = self::txt($prod, 'CEST');

        $override = StOverrideCest::where('chave_acesso', $chave)->where('nfe_item', $nItem)->first();

        $base = [
            'cliente_id' => $documento->cliente_id,
            'nfe_numero' => $numero,
            'ncm' => $ncm,
            'produto' => $xProd,
            'cest_xml' => $cestXml,
            'cfop' => $cfop,
            'uf_origem' => $ufOrigem,
            'uf_destino' => $ufDestino,
            'vprod' => $vProd,
        ];

        // Decisão manual do contador de que este item genuinamente não é
        // sujeito a ST (produto fora de qualquer segmento) -- diferente do
        // "nao_sujeito_st" automático por regra revogada mais abaixo; aqui é
        // sempre uma decisão humana, registrada com fundamento e autoria.
        if ($override && $override->nao_sujeito_st) {
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'nao_sujeito_st',
                'status_detalhe' => "Marcado manualmente como não sujeito a ST por {$override->decidido_por} "
                    . "em {$override->decidido_em?->format('d/m/Y')}: {$override->fundamento}",
            ]);

            return;
        }

        [$cest, $cestOrigem, $cestPendenteDetalhe] = $this->resolverCest($cestXml, $override);

        if ($cest === null) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'pendente_cest',
                'status_detalhe' => $cestPendenteDetalhe,
            ]);

            return;
        }

        $regra = StRegraCest::where('uf', $this->ufSuportada())->where('cest', $cest)->first();

        if (! $regra) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'cest_usado' => $cest,
                'cest_origem' => $cestOrigem,
                'status' => 'pendente_cest',
                'status_detalhe' => "CEST {$cest} não localizado na tabela oficial de regras cadastrada "
                    . 'neste motor (pode ser um segmento ainda não implementado, ou um CEST incorreto) -- '
                    . 'validar manualmente.',
            ]);

            return;
        }

        $base += [
            'cest_usado' => $cest,
            'cest_origem' => $cestOrigem,
            'segmento' => $regra->segmento,
            'cest_descricao' => $regra->descricao,
        ];

        // CEST reconhecido, mas a regra foi revogada (deixou de ser ST) na data
        // de emissão desta NF-e -- resposta definitiva "não sujeito a ST", não
        // uma pendência. Ex.: Autopeças no RS, Decreto nº 57.848/2024.
        if ($regra->situacao === 'revogada'
            && $regra->revogada_desde !== null
            && $documento->data_emissao !== null
            && $documento->data_emissao->greaterThanOrEqualTo($regra->revogada_desde)
        ) {
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'nao_sujeito_st',
                'status_detalhe' => "CEST {$cest} não é mais sujeito a ICMS-ST no {$this->ufSuportada()} "
                    . "desde {$regra->revogada_desde->format('d/m/Y')} ({$regra->fonte_legal})",
            ]);

            return;
        }

        $imposto = self::filho($det, 'imposto');
        $icmsContainer = self::filho($imposto, 'ICMS');
        $icms = self::primeiroFilho($icmsContainer);

        $vBC = self::num($icms, 'vBC') ?? 0.0;
        $pICMS = self::num($icms, 'pICMS');
        $vICMSProprio = self::num($icms, 'vICMS') ?? 0.0;
        $vICMSSTXml = self::num($icms, 'vICMSST') ?? 0.0;
        $pRedBC = self::num($icms, 'pRedBC');

        $base['icms_proprio'] = $vICMSProprio;
        $base['vbc_origem'] = $vBC;

        if ($vICMSSTXml > 0) {
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'st_ja_destacada_no_xml',
                'status_detalhe' => 'ICMS-ST já veio destacado no XML; não é caso de antecipação.',
            ]);

            return;
        }

        if (! $regra->aliquota_confirmada) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'pendente_aliquota',
                'status_detalhe' => "Pendente confirmação de alíquota interna para {$regra->segmento} -- cálculo bloqueado.",
            ]);

            return;
        }

        $ipiTrib = self::filho(self::filho($imposto, 'IPI'), 'IPITrib');
        $vIPI = self::num($ipiTrib, 'vIPI') ?? 0.0;
        $base['vipi'] = $vIPI;

        $baseOperacao = $vBC;

        if ($pRedBC !== null) {
            $overrideBase = StOverrideBase::where('chave_acesso', $chave)->where('nfe_item', $nItem)->first();

            if (! $overrideBase || $overrideBase->percentual_reducao_pct === null) {
                $pendentes++;
                StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                    'status' => 'pendente_reducao_base',
                    'status_detalhe' => "Pendente informar percentual de redução de base do {$this->ufSuportada()} "
                        . "(origem/{$ufOrigem} aplicou pRedBC {$pRedBC}% no ICMS próprio -- vBC origem={$vBC}, "
                        . "vProd cheio={$vProd}; a redução do destino, se houver, pode ser diferente).",
                ]);

                return;
            }

            $pct = (float) $overrideBase->percentual_reducao_pct;
            $baseOperacao = round($vProd * (1 - $pct / 100), 2);
        }

        $base['base_operacao_usada'] = $baseOperacao;

        $resolvidoMva = $this->resolverMva($regra, $pICMS);

        // Rede de segurança: uma regra vigente sem MVA cadastrada (não deveria
        // existir, mas nunca deixa o cálculo seguir com null como se fosse 0%).
        if ($resolvidoMva['mva'] === null && $resolvidoMva['status'] === null) {
            $resolvidoMva['status'] = 'pendente_aliquota';
            $resolvidoMva['detalhe'] = "MVA não cadastrada para o CEST {$cest} em {$this->ufSuportada()}.";
        }

        if ($resolvidoMva['status'] !== null) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'aliq_interestadual_pct' => $pICMS,
                'status' => $resolvidoMva['status'],
                'status_detalhe' => $resolvidoMva['detalhe'],
            ]);

            return;
        }

        $mvaOriginal = $resolvidoMva['mva'];
        $aliquotaInterna = (float) $regra->aliquota_interna_pct;

        // MVA ajustada (Convênio ICMS 142/18, cláusula segunda): as MVA
        // cadastradas em st_regras_cest são a MVA ORIGINAL (nacional) --
        // nunca aplicada direto na base, sempre ajustada pela alíquota
        // interestadual real da operação (pICMS do XML) x alíquota interna
        // do destino. Vale para os dois motores (RS e MG), decisão de Igor
        // Caue em 24/09/2026. No RS a MVA original é a coluna "operação
        // interna" (mva_pct) -- as colunas 12%/4% já vêm ajustadas na
        // tabela e não podem entrar aqui (ajuste em dobro, corrigido em
        // 25/09/2026).
        if ($pICMS === null) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'status' => 'pendente_aliquota',
                'status_detalhe' => 'Alíquota interestadual (pICMS) não informada no XML -- necessária '
                    . 'para calcular a MVA ajustada (Convênio ICMS 142/18, cláusula segunda).',
            ]);

            return;
        }

        $mva = $this->ajustarMva($mvaOriginal, $aliquotaInterna, $pICMS);

        $baseSt = round(($baseOperacao + $vIPI) * (1 + $mva / 100), 2);
        $icmsStDevido = round($baseSt * $aliquotaInterna / 100 - $vICMSProprio, 2);
        if ($icmsStDevido < 0) {
            $icmsStDevido = 0.0;
        }

        $base += [
            'aliq_interestadual_pct' => $pICMS,
            'mva_aplicada_pct' => $mva,
            'base_st_calculada' => $baseSt,
            'aliquota_interna_pct' => $aliquotaInterna,
            'icms_st_devido' => $icmsStDevido,
            'responsavel' => $this->notaResponsavel($regra, (string) $ufOrigem)
                . " MVA original {$mvaOriginal}% ajustada para {$mva}% (Convênio ICMS 142/18 -- alíq. "
                . "interna {$aliquotaInterna}% x interestadual {$pICMS}%).",
        ];

        if ($regra->adicional_tipo !== 'nenhum' && ! $regra->adicional_confirmado) {
            $pendentes++;
            StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
                'adicional_tipo' => 'nenhum',
                'adicional_valor' => 0,
                'total_a_recolher' => $icmsStDevido,
                'status' => 'pendente_fem',
                'status_detalhe' => "ICMS-ST calculado; adicional {$regra->adicional_tipo} PENDENTE de "
                    . 'confirmação -- item provavelmente sujeito ao enquadramento, mas requer validação '
                    . 'expressa antes de somar o adicional.',
            ]);

            return;
        }

        $adicionalValor = 0.0;
        $adicionalTipo = 'nenhum';

        if ($regra->adicional_tipo !== 'nenhum' && $regra->adicional_confirmado) {
            $adicionalTipo = $regra->adicional_tipo;
            $adicionalValor = round($baseSt * (float) $regra->adicional_pct / 100, 2);
        }

        StCalculo::updateOrCreate(['chave_acesso' => $chave, 'nfe_item' => $nItem], $base + [
            'adicional_tipo' => $adicionalTipo,
            'adicional_valor' => $adicionalValor,
            'total_a_recolher' => $icmsStDevido + $adicionalValor,
            'status' => 'calculado',
            'status_detalhe' => null,
        ]);
    }

    /**
     * Mesma árvore de decisão de cest_origem dos motores Python: nunca infere
     * CEST a partir do NCM, só usa XML ou um override explícito.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string} [cest, cest_origem, detalhe_se_pendente]
     */
    private function resolverCest(?string $cestXml, ?StOverrideCest $override): array
    {
        if ($override && $override->cest_atribuido) {
            $corrigido = $override->cest_atribuido;

            if ($cestXml && $cestXml !== $corrigido) {
                return [$corrigido, 'corrigido_manualmente', null];
            }

            if ($cestXml && $cestXml === $corrigido) {
                return [$cestXml, 'xml_confirmado_manualmente', null];
            }

            return [$corrigido, 'atribuido_manualmente', null];
        }

        if ($cestXml) {
            return [$cestXml, 'xml', null];
        }

        if ($override && $override->cest_sugerido) {
            return [null, null, "CEST não informado pelo emitente -- sugestão {$override->cest_sugerido} "
                . "({$override->fundamento}) ainda não confirmada (preencher cest_atribuido para calcular)."];
        }

        return [null, null, 'Sem CEST no XML e sem atribuição registrada em overrides.'];
    }
}
