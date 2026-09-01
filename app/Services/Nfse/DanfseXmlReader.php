<?php

namespace App\Services\Nfse;

/**
 * Lê o XML da NFS-e de padrão nacional (elemento <NFSe> / infNFSe) e devolve
 * um array plano com todos os campos necessários para montar o DANFSe v2.0,
 * conforme a Nota Técnica SE/CGNFS-e nº 008/2026.
 *
 * Toda a navegação é feita por local-name() para ser imune a variações de
 * versão de leiaute e prefixo de namespace. Campos ausentes no XML voltam
 * como '-' (regra da nota 12 da NT 008) — exceto valores monetários, que
 * voltam como null para o Blade decidir se imprime ou suprime a linha.
 */
class DanfseXmlReader
{
    private \DOMXPath $xp;
    private \DOMElement $inf;

    // ─── Tabelas de domínio (código → descrição) da NT 008 / leiaute NFS-e ───

    private const TP_EMIT = [
        '1' => 'Prestador', '2' => 'Tomador', '3' => 'Intermediário',
    ];

    private const C_STAT = [
        '100' => 'NFS-e emitida com sucesso',
        '101' => 'NFS-e Cancelada',
        '102' => 'NFS-e de Decisão Judicial ou Administrativa',
        '103' => 'NFS-e avulsa emitida',
        '105' => 'NFS-e emitida com débito de ISSQN em análise',
    ];

    private const FIN_NFSE = [
        '0' => 'NFS-e regular',
        '1' => 'NFS-e emitida em substituição a outra NFS-e',
    ];

    private const OP_SIMPLES = [
        '1' => 'Não optante',
        '2' => 'Optante - Microempreendedor Individual (MEI)',
        '3' => 'Optante - Microempresa ou Empresa de Pequeno Porte (ME/EPP)',
    ];

    private const REG_AP_SN = [
        '1' => 'Regime de apuração dos tributos federais e municipal pelo SN',
        '2' => 'Regime de apuração dos tributos federais pelo SN e ISSQN pelo município',
        '3' => 'Regime de apuração de todos os tributos fora do SN (excesso de receita)',
    ];

    private const TRIB_ISSQN = [
        '1' => 'Operação Tributável',
        '2' => 'Exportação de Serviço',
        '3' => 'Não Incidência',
        '4' => 'Imunidade',
    ];

    private const REG_ESP_TRIB = [
        '0' => 'Nenhum',
        '1' => 'Ato Cooperado (Cooperativa)',
        '2' => 'Estimativa',
        '3' => 'Microempresa Municipal',
        '4' => 'Notário ou Registrador',
        '5' => 'Profissional Autônomo',
        '6' => 'Sociedade de Profissionais',
        '9' => 'Outros',
    ];

    private const TP_IMUNIDADE = [
        '1' => 'Imunidade da alínea "a" do inciso VI do art. 150 da CF',
        '2' => 'Imunidade da alínea "b" do inciso VI do art. 150 da CF',
        '3' => 'Imunidade da alínea "c" do inciso VI do art. 150 da CF',
        '4' => 'Livros, jornais, periódicos e o papel destinado a sua impressão',
        '5' => 'Outros',
    ];

    private const TP_SUSP = [
        '1' => 'Exigibilidade Suspensa por Decisão Judicial',
        '2' => 'Exigibilidade Suspensa por Processo Administrativo',
    ];

    private const TP_BM = [
        '1' => 'Isenção', '2' => 'Redução de Base de Cálculo',
        '3' => 'Redução de Alíquota', '4' => 'Outros',
    ];

    private const TP_RET_ISSQN = [
        '1' => 'Não Retido', '2' => 'Retido pelo Tomador', '3' => 'Retido pelo Intermediário',
    ];

    // tpRetPisCofins — leiaute NFS-e / NT 007. Impresso como "{código} - {descrição}".
    private const TP_RET_PISCOFINS = [
        '0' => 'PIS/COFINS/CSLL Não Retidos',
        '1' => 'PIS/COFINS/CSLL Retidos na Fonte',
        '2' => 'PIS/COFINS/CSLL Não Retidos',
        '3' => 'PIS/COFINS/CSLL Retidos',
        '4' => 'PIS/COFINS Retidos, CSLL Não Retido',
        '5' => 'PIS Retido, COFINS/CSLL Não Retidos',
        '6' => 'COFINS Retido, PIS/CSLL Não Retidos',
        '7' => 'PIS Não Retido, COFINS/CSLL Retidos',
        '8' => 'PIS/COFINS Não Retidos, CSLL Retido',
        '9' => 'COFINS Não Retido, PIS/CSLL Retidos',
    ];

    public function __construct(string $xml)
    {
        $doc = new \DOMDocument();
        if (! @$doc->loadXML($xml)) {
            throw new \InvalidArgumentException('XML da NFS-e inválido ou não pôde ser lido.');
        }

        $this->xp = new \DOMXPath($doc);

        $inf = $this->xp->query('//*[local-name()="infNFSe"]')->item(0);
        if (! $inf instanceof \DOMElement) {
            throw new \InvalidArgumentException('Elemento infNFSe não encontrado no XML — não é uma NFS-e de padrão nacional.');
        }
        $this->inf = $inf;
    }

    // ─── Helpers de leitura ────────────────────────────────────────────────

    /**
     * Primeiro texto do primeiro elemento com o local-name informado.
     *
     * $ctx omitido  → busca a partir de infNFSe (árvore inteira).
     * $ctx = null   → o grupo esperado não existe no XML: retorna null (não faz
     *                 busca global, evitando "pescar" valor de outro bloco).
     * $ctx = DOMNode → busca dentro dele.
     */
    private function t(string $name, \DOMNode|false|null $ctx = false): ?string
    {
        if ($ctx === null) {
            return null;
        }
        $node = $this->xp->query('.//*[local-name()="' . $name . '"]', $ctx ?: $this->inf)->item(0);
        $v = $node ? trim($node->textContent) : null;

        return ($v === null || $v === '') ? null : $v;
    }

    private function node(string $name, \DOMNode|false|null $ctx = false): ?\DOMNode
    {
        if ($ctx === null) {
            return null;
        }

        return $this->xp->query('.//*[local-name()="' . $name . '"]', $ctx ?: $this->inf)->item(0);
    }

    private function f(string $name, \DOMNode|false|null $ctx = false): ?float
    {
        $v = $this->t($name, $ctx);

        return $v === null ? null : (float) $v;
    }

    private function dash(?string $v): string
    {
        return ($v === null || $v === '') ? '-' : $v;
    }

    private function map(array $table, ?string $code, int $max = 0): string
    {
        if ($code === null || $code === '') {
            return '-';
        }
        $desc = $table[$code] ?? $code;

        return $max > 0 ? $this->ellipsis($desc, $max) : $desc;
    }

    /** Formata "{código} - {descrição}" (ex.: tpRetPisCofins → "0 - PIS/COFINS/CSLL Não Retidos"). */
    private function codDesc(array $table, ?string $code): string
    {
        if ($code === null || $code === '') {
            return '-';
        }

        return $code . ' - ' . ($table[$code] ?? $code);
    }

    /** cTribNac de 6 dígitos → "nn.nn.nn" (NT 008, item 2.4.5). */
    private function formataCodTribNac(?string $c): string
    {
        if ($c === null || $c === '') {
            return '-';
        }
        $d = preg_replace('/\D/', '', $c);

        return strlen($d) === 6 ? substr($d, 0, 2) . '.' . substr($d, 2, 2) . '.' . substr($d, 4, 2) : $c;
    }

    private function ellipsis(string $s, int $max): string
    {
        $s = trim($s);

        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 3) . '...' : $s;
    }

    private function dt(?string $iso, bool $comHora = false): string
    {
        if (! $iso) {
            return '-';
        }
        try {
            $c = new \DateTimeImmutable($iso);

            return $c->format($comHora ? 'd/m/Y H:i:s' : 'd/m/Y');
        } catch (\Throwable) {
            return $iso;
        }
    }

    private function doc(?string $cnpj, ?string $cpf, ?string $nif): string
    {
        if ($cnpj) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
        }
        if ($cpf) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
        }

        return $nif ? $nif : '-';
    }

    private function cep(?string $cep): string
    {
        if (! $cep) {
            return '';
        }
        $cep = preg_replace('/\D/', '', $cep);

        return strlen($cep) === 8 ? substr($cep, 0, 2) . '.' . substr($cep, 2, 3) . '-' . substr($cep, 5) : $cep;
    }

    private function fone(?string $f): string
    {
        if (! $f) {
            return '-';
        }
        $f = preg_replace('/\D/', '', $f);
        if (strlen($f) === 11) {
            return sprintf('(%s) %s-%s', substr($f, 0, 2), substr($f, 2, 5), substr($f, 7));
        }
        if (strlen($f) === 10) {
            return sprintf('(%s) %s-%s', substr($f, 0, 2), substr($f, 2, 4), substr($f, 6));
        }

        return $f;
    }

    /** Monta o bloco de endereço de uma pessoa (prest/toma/interm/dest). */
    private function endereco(?\DOMNode $pessoa): array
    {
        if (! $pessoa) {
            return ['municipio' => '-', 'ibgeCep' => '-', 'logradouro' => '-', 'email' => '-'];
        }

        $end = $this->node('end', $pessoa) ?? $pessoa;
        // O bloco <emit> usa <enderNac>; os demais (toma/prest/interm/dest) usam <end><endNac>.
        $endNac = $this->node('endNac', $end) ?? $this->node('enderNac', $pessoa);
        $endExt = $this->node('endExt', $end);

        $cMun = $this->t('cMun', $endNac);
        $municipioNome = $this->t('xMun', $end) ?? $this->t('xMun', $endNac) ?? \App\Support\MunicipiosIbge::nome($cMun);
        $uf = $this->t('UF', $endNac) ?? \App\Support\MunicipiosIbge::uf($cMun);
        $cep = $this->cep($this->t('CEP', $endNac));

        if ($endExt) {
            $municipioNome = $this->t('xCidade', $endExt) ?? $municipioNome;
            $uf = $this->t('xEstado', $endExt) ?? $uf;
            $cep = $this->t('cEndPost', $endExt) ?? $cep;
        }

        $municipio = $municipioNome
            ? trim($municipioNome . ($uf ? ' / ' . $uf : ''))
            : ($cMun ?? '-');
        $ibgeCep = trim(($cMun ?? '-') . ($cep ? ' / ' . $cep : ''));

        $logr = array_filter([
            $this->t('xLgr', $end),
            $this->t('nro', $end),
            $this->t('xCpl', $end),
            $this->t('xBairro', $end),
        ]);

        return [
            'municipio' => $municipio ?: '-',
            'ibgeCep' => $ibgeCep ?: '-',
            'logradouro' => $logr ? $this->ellipsis(implode(', ', $logr), 80) : '-',
            'email' => $this->dash($this->t('email', $pessoa)),
        ];
    }

    private function pessoa(string $tag, ?\DOMNode $scope = null): ?array
    {
        $p = $this->node($tag, $scope);
        if (! $p) {
            return null;
        }

        $end = $this->endereco($p);

        return [
            'doc' => $this->doc($this->t('CNPJ', $p), $this->t('CPF', $p), $this->t('NIF', $p)),
            'im' => $this->dash($this->t('IM', $p)),
            'fone' => $this->fone($this->t('fone', $p)),
            'nome' => $this->ellipsis($this->t('xNome', $p) ?? '-', 80),
            'municipio' => $end['municipio'],
            'ibgeCep' => $end['ibgeCep'],
            'logradouro' => $end['logradouro'],
            'email' => $end['email'],
        ];
    }

    // ─── API pública ───────────────────────────────────────────────────────

    public function chaveAcesso(): string
    {
        return preg_replace('/^NFS/i', '', $this->inf->getAttribute('Id'));
    }

    public function cStatRaw(): string
    {
        return $this->t('cStat') ?? '';
    }

    /** 'cancelada' | 'substituida' | 'homologacao' | null — controla a marca d'água. */
    public function marcaDagua(): ?string
    {
        if ($this->cStatRaw() === '101') {
            return 'CANCELADA';
        }
        // Substituição: existe grupo <subst> com chSubstda dentro da DPS.
        if ($this->t('chSubstda') !== null && $this->cStatRaw() === '102') {
            return 'SUBSTITUÍDA';
        }
        if (($this->t('tpAmb') ?? '1') === '2') {
            return 'NFS-e SEM VALIDADE JURÍDICA';
        }

        return null;
    }

    public function toArray(): array
    {
        $dpsInf = $this->node('infDPS');
        $prest = $this->node('prest', $dpsInf);
        $valNfse = $this->node('valores', $this->inf);        // NFSe/infNFSe/valores
        $valDps = $this->node('valores', $dpsInf);            // DPS/infDPS/valores
        $tribMun = $this->node('tribMun', $valDps);
        $tribFed = $this->node('tribFed', $valDps);
        $piscofins = $this->node('piscofins', $tribFed);
        $ibscbs = $this->node('IBSCBS', $dpsInf);
        $regTrib = $this->node('regTrib', $prest);

        $competIso = $this->t('dCompet');
        $competAno = $competIso ? (int) substr($competIso, 0, 4) : (int) date('Y');

        // ── Identificação ──
        $ident = [
            'chave' => $this->chaveAcesso(),
            'nNFSe' => $this->dash($this->t('nNFSe')),
            'dCompet' => $this->dt($competIso),
            'dhProc' => $this->dt($this->t('dhProc'), true),
            'nDPS' => $this->dash($this->t('nDPS')),
            'serie' => $this->dash($this->t('serie')),
            'dhEmiDps' => $this->dt($this->t('dhEmi', $dpsInf), true),
            'emitente' => $this->map(self::TP_EMIT, $this->t('tpEmit')),
            'situacao' => $this->map(self::C_STAT, $this->cStatRaw(), 37),
            'finalidade' => $this->map(self::FIN_NFSE, $this->t('finNFSe', $ibscbs), 37),
        ];

        // ── Cabeçalho ──
        $ufEmit = $this->t('UF', $this->node('enderNac', $this->node('emit')));
        $cabecalho = [
            'municipio' => 'Município: ' . trim(($this->t('xLocEmi') ?? '-') . ($ufEmit ? ' - ' . $ufEmit : '')),
            'ambGer' => $this->dash($this->t('ambGer')),
            'tpAmb' => $this->dash($this->t('tpAmb') ?? '1'),
        ];

        // ── Prestador ──
        $prestador = $this->pessoa('prest', $dpsInf) ?? [];
        // O nome/razão do prestador costuma vir só no <emit> do bloco NFS-e.
        if (($prestador['nome'] ?? '-') === '-') {
            $prestador['nome'] = $this->ellipsis($this->t('xNome', $this->node('emit')) ?? '-', 80);
        }
        if (($prestador['logradouro'] ?? '-') === '-') {
            $emitEnd = $this->endereco($this->node('emit'));
            $prestador = array_merge($prestador, [
                'municipio' => $prestador['municipio'] !== '-' ? $prestador['municipio'] : $emitEnd['municipio'],
                'ibgeCep' => $prestador['ibgeCep'] !== '-' ? $prestador['ibgeCep'] : $emitEnd['ibgeCep'],
                'logradouro' => $emitEnd['logradouro'],
            ]);
        }
        $prestador['fone'] = $prestador['fone'] !== '-' ? $prestador['fone'] : $this->fone($this->t('fone', $this->node('emit')));
        $prestador['email'] = $prestador['email'] !== '-' ? $prestador['email'] : $this->dash($this->t('email', $this->node('emit')));
        $prestador['simplesNacional'] = $this->map(self::OP_SIMPLES, $this->t('opSimpNac', $regTrib), 37);
        $prestador['regimeApuracaoSN'] = $this->map(self::REG_AP_SN, $this->t('regApTribSN', $regTrib), 77);

        // ── Tomador / Destinatário / Intermediário ──
        $tomador = $this->pessoa('toma', $dpsInf);
        $intermediario = $this->pessoa('interm', $dpsInf);
        $destinatario = $this->pessoa('dest', $ibscbs);
        // NT 008, item 2.3.2: só faz sentido falar em "destinatário = tomador"
        // quando a nota tem o grupo IBS/CBS (onde o bloco de destinatário existe).
        $destIgualTomador = $ibscbs !== null && $destinatario === null && $tomador !== null;

        // ── Serviço ──
        $cServ = $this->node('cServ', $dpsInf);
        $xTribMun = $this->t('xTribMun');
        $servico = [
            'codTrib' => $this->formataCodTribNac($this->t('cTribNac', $cServ)) . ' / ' . ($this->t('cTribMun', $cServ) ?? '-'),
            'cNBS' => $this->dash($this->t('cNBS', $cServ)),
            'localPrestacao' => implode(' / ', array_filter([
                $this->t('xLocPrestacao') ?? '-',
                $this->t('UF', $this->node('locPrest', $dpsInf)) ?? $ufEmit,
                $this->t('cPaisPrestacao', $this->node('locPrest', $dpsInf)) ?? '-',
            ], fn ($v) => $v !== null)),
            'descCodTrib' => $this->ellipsis(($xTribMun ?: $this->t('xTribNac')) ?? '-', 167),
            'descServico' => $this->ellipsis($this->t('xDescServ', $cServ) ?? '-', 1297),
        ];

        // ── Tributação Municipal (ISSQN) ──
        $exigSusp = $this->node('exigSusp', $tribMun);
        $regEsp = $this->t('regEspTrib', $regTrib);
        $issqnRegimeVals = [
            in_array($regEsp, [null, '0'], true) ? null : $regEsp,
            $this->t('tpImunidade', $tribMun),
            $this->t('tpSusp', $exigSusp), $this->t('nProcesso', $exigSusp),
        ];
        $issqnBeneficioVals = [
            $this->t('tpBM', $valNfse),
            $this->f('vCalcBM', $valNfse) ?? $this->f('vRedBCBM', $tribMun),
            $this->f('vDR', $this->node('vDedRed', $valDps)) ?? $this->f('vCalcDR', $valNfse),
            $this->f('vDescIncond', $this->node('vDescCondIncond', $valDps)),
        ];
        $issqn = [
            'aplica' => $this->t('tribISSQN', $tribMun) !== null || $this->f('vISSQN', $valNfse) !== null,
            // Nota 5 da NT 008: linha suprimida quando todos os campos dela estão vazios.
            'showLinhaRegime' => count(array_filter($issqnRegimeVals, fn ($v) => $v !== null)) > 0,
            'showLinhaBeneficio' => count(array_filter($issqnBeneficioVals, fn ($v) => $v !== null)) > 0,
            'tipo' => $this->map(self::TRIB_ISSQN, $this->t('tribISSQN', $tribMun)),
            'localIncidencia' => implode(' / ', array_filter([
                $this->t('xLocIncid') ?? '-',
                $ufEmit,
                $this->t('cPaisResult', $tribMun) ?? '-',
            ], fn ($v) => $v !== null)),
            'regimeEspecial' => $this->map(self::REG_ESP_TRIB, $this->t('regEspTrib', $regTrib)),
            'tipoImunidade' => $this->map(self::TP_IMUNIDADE, $this->t('tpImunidade', $tribMun), 37),
            'suspensao' => $this->map(self::TP_SUSP, $this->t('tpSusp', $exigSusp), 37),
            'nProcesso' => $this->dash($this->t('nProcesso', $exigSusp)),
            'beneficio' => $this->map(self::TP_BM, $this->t('tpBM', $valNfse)),
            'calcBM' => $this->f('vCalcBM', $valNfse) ?? $this->f('vRedBCBM', $tribMun),
            'deducoesReducoes' => $this->f('vDR', $this->node('vDedRed', $valDps)) ?? $this->f('vCalcDR', $valNfse),
            'descIncond' => $this->f('vDescIncond', $this->node('vDescCondIncond', $valDps)),
            'bcIssqn' => $this->f('vBC', $valNfse),
            'aliquota' => $this->f('pAliqAplic', $valNfse),
            'retencao' => $this->map(self::TP_RET_ISSQN, $this->t('tpRetISSQN', $tribMun)),
            'issqnApurado' => $this->f('vISSQN', $valNfse),
        ];

        // ── Tributação Federal (exceto CBS) ──
        $federal = [
            'imprime' => $competAno <= 2026,
            'irrf' => $this->f('vRetIRRF', $tribFed),
            'previdenciaria' => $this->f('vRetCP', $tribFed),
            'sociais' => $this->f('vRetCSLL', $tribFed),
            'pis' => $this->f('vPis', $piscofins),
            'cofins' => $this->f('vCofins', $piscofins),
            'descRetPisCofins' => $this->codDesc(self::TP_RET_PISCOFINS, $this->t('tpRetPisCofins', $piscofins)),
        ];

        // ── Tributação IBS / CBS ──
        $gIBSCBS = $this->node('gIBSCBS', $ibscbs);
        $valIbs = $this->node('valores', $ibscbs);
        $ufIbs = $this->node('uf', $valIbs);
        $munIbs = $this->node('mun', $valIbs);
        $fedIbs = $this->node('fed', $valIbs);
        $totCIBS = $this->node('totCIBS', $ibscbs);
        $temIbsCbs = $gIBSCBS !== null || $totCIBS !== null;

        $ibscbsArr = [
            'aplica' => $temIbsCbs,
            'cstClassTrib' => trim(($this->t('CST', $gIBSCBS) ?? '-') . ' / ' . ($this->t('cClassTrib', $gIBSCBS) ?? '-'), ' /'),
            'indOperacao' => implode(' / ', array_map(fn ($v) => $v ?? '-', [
                $this->t('cIndOp', $ibscbs),
                $this->t('cLocalidadeIncid', $ibscbs),
                $this->t('xLocalidadeIncid', $ibscbs),
                $this->t('UF', $this->node('dest', $ibscbs)),
            ])),
            'exclusoesReducoes' => $this->somaExclusoesIbs($valDps, $valIbs, $valNfse, $piscofins),
            'bcAposReducoes' => $this->f('vBC', $valIbs),
            'redAliquota' => $this->pct3($this->t('pRedAliqUF', $ufIbs), $this->t('pRedAliqMun', $munIbs), $this->t('pRedAliqCBS', $fedIbs)),
            'aliqIbs' => $this->pct2($this->t('pIBSUF', $ufIbs), $this->t('pIBSMun', $munIbs)),
            'aliqEfetMunIbs' => $this->f('pAliqEfetMun', $munIbs),
            'valorMunIbs' => $this->f('vIBSMun', $this->node('gIBSMunTot', $this->node('gIBS', $totCIBS))),
            'aliqEfetUfIbs' => $this->f('pAliqEfetUF', $ufIbs),
            'valorUfIbs' => $this->f('vIBSUF', $this->node('gIBSUFTot', $this->node('gIBS', $totCIBS))),
            'valorTotalIbs' => $this->f('vIBSTot', $this->node('gIBS', $totCIBS)),
            'aliqCbs' => $this->f('pCBS', $fedIbs),
            'aliqEfetCbs' => $this->f('pAliqEfetCBS', $fedIbs),
            'valorTotalCbs' => $this->f('vCBS', $this->node('gCBS', $totCIBS)),
        ];

        // ── Valor Total da NFS-e ──
        $vDescCondIncond = $this->node('vDescCondIncond', $valDps);
        $vIBSTot = $this->f('vIBSTot', $this->node('gIBS', $totCIBS)) ?? 0.0;
        $vCBS = $this->f('vCBS', $this->node('gCBS', $totCIBS)) ?? 0.0;
        $vLiq = $this->f('vLiq', $valNfse);

        $totais = [
            'valorServico' => $this->f('vServ', $this->node('vServPrest', $valDps)),
            'descIncond' => $this->f('vDescIncond', $vDescCondIncond),
            'descCond' => $this->f('vDescCond', $vDescCondIncond),
            'totalRetencoes' => $this->f('vTotalRet', $valNfse),
            'valorLiquido' => $vLiq,
            'totalIbsCbs' => $vIBSTot + $vCBS,
            'valorLiquidoComIbsCbs' => $this->f('vTotNF', $totCIBS) ?? (($vLiq ?? 0) + $vIBSTot + $vCBS),
        ];

        // ── Canhoto (opcional) ──
        $canhoto = [
            'nNFSeChave' => ($this->t('nNFSe') ?? '-') . ' / ' . $this->chaveAcesso(),
        ];

        // ── Informações Complementares ──
        $infComplNode = $this->node('infoCompl', $dpsInf);
        $partes = [];
        if ($v = $this->t('xInfComp', $infComplNode)) {
            $partes[] = 'Inf. Cont.: ' . $v;
        }
        if ($v = $this->t('chSubstda')) {
            $partes[] = 'NFS-e Subst.: ' . $v;
        }
        if ($v = $this->t('docRef', $infComplNode)) {
            $partes[] = 'Doc. Ref.: ' . $v;
        }
        if ($v = $this->t('cObra', $this->node('obra', $dpsInf))) {
            $partes[] = 'Cod. Obra: ' . $v;
        }
        if ($v = $this->t('inscImobFisc', $this->node('imovel', $ibscbs))) {
            $partes[] = 'Insc. Imob.: ' . $v;
        }
        if ($v = $this->t('idAtvEvt', $this->node('atvEvento', $dpsInf))) {
            $partes[] = 'Cod. Evt.: ' . $v;
        }
        if ($v = $this->t('xOutInf', $infComplNode)) {
            $partes[] = 'Inf. A. T. Mun.: ' . $v;
        }
        $partes[] = $this->totaisAproximados($valDps);

        $infoCompl = $this->ellipsis(implode(' | ', array_filter($partes)), 2000);

        return [
            'marcaDagua' => $this->marcaDagua(),
            'cabecalho' => $cabecalho,
            'identificacao' => $ident,
            'prestador' => $prestador,
            'tomador' => $tomador,
            'destinatario' => $destinatario,
            'destIgualTomador' => $destIgualTomador,
            'intermediario' => $intermediario,
            'servico' => $servico,
            'issqn' => $issqn,
            'federal' => $federal,
            'ibscbs' => $ibscbsArr,
            'totais' => $totais,
            'canhoto' => $canhoto,
            'infoCompl' => $infoCompl,
            'qrCodeUrl' => 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=' . $this->chaveAcesso(),
        ];
    }

    private function pct2(?string $a, ?string $b): string
    {
        return trim(($a !== null ? $a . '%' : '-') . ' / ' . ($b !== null ? $b . '%' : '-'), ' /');
    }

    private function pct3(?string $a, ?string $b, ?string $c): string
    {
        return implode(' / ', array_map(fn ($v) => $v !== null ? $v . '%' : '-', [$a, $b, $c]));
    }

    private function somaExclusoesIbs(?\DOMNode $valDps, ?\DOMNode $valIbs, ?\DOMNode $valNfse, ?\DOMNode $piscofins): ?float
    {
        $soma = 0.0;
        $achou = false;
        foreach ([
            [$this->node('vDescCondIncond', $valDps), 'vDescIncond'],
            [$valIbs, 'vCalcReeRepRes'],
            [$valNfse, 'vISSQN'],
            [$piscofins, 'vPis'],
            [$piscofins, 'vCofins'],
        ] as [$ctx, $tag]) {
            $v = $this->f($tag, $ctx);
            if ($v !== null) {
                $soma += $v;
                $achou = true;
            }
        }

        return $achou ? $soma : null;
    }

    /**
     * Linha fixa e obrigatória de "Totais Aproximados dos Tributos" (Lei 12.741/2012).
     * Aceita valores monetários (vTotTrib*) ou percentuais (pTotTrib*).
     */
    private function totaisAproximados(?\DOMNode $valDps): string
    {
        $totTrib = $this->node('totTrib', $valDps);
        $vGroup = $this->node('vTotTrib', $totTrib);
        $pGroup = $this->node('pTotTrib', $totTrib);

        $fmtR = fn (?float $v) => 'R$ ' . number_format($v ?? 0, 2, ',', '.');
        $fmtP = fn (?string $v) => number_format((float) ($v ?? 0), 2, ',', '.') . '%';

        if ($vGroup) {
            $fed = $fmtR($this->f('vTotTribFed', $vGroup));
            $est = $fmtR($this->f('vTotTribEst', $vGroup));
            $mun = $fmtR($this->f('vTotTribMun', $vGroup));
        } elseif ($pGroup) {
            $fed = $fmtP($this->t('pTotTribFed', $pGroup));
            $est = $fmtP($this->t('pTotTribEst', $pGroup));
            $mun = $fmtP($this->t('pTotTribMun', $pGroup));
        } else {
            $fed = $est = $mun = 'R$ 0,00';
        }

        return "Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: {$fed} ; Estaduais: {$est} ; Municipais: {$mun}";
    }
}
