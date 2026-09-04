<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoFiscal extends Model
{
    protected $table = 'documentos_fiscais';

    protected $fillable = [
        'cliente_id',
        'chave_acesso',
        'tipo',
        'origem',
        'nsu',
        'numero',
        'data_emissao',
        'emitente_nome',
        'emitente_doc',
        'valor',
        'situacao',
        'tp_nf',
        'emitente_crt',
        'papel_cte',
        'data_saida_entrada',
        'xml_content',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_saida_entrada' => 'date',
        'emitente_crt' => 'integer',
    ];

    /**
     * CRTs da tag <emit><CRT> que caracterizam um emitente optante pelo Simples
     * Nacional: 1 = Simples Nacional; 2 = Simples Nacional, excesso de sublimite
     * de receita bruta. MEI (4) fica de fora — é Simei, tratado à parte.
     */
    public const CRT_SIMPLES_NACIONAL = [1, 2];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Alguns XMLs sincronizados via SEFAZ-RS (NfeIntegracaoRsService) antes da correção
     * ficaram salvos com um invólucro <proc NSU="..." chAcesso="..." schema="..."> em volta
     * do <nfeProc>/<resNFe> real. Sistemas de terceiros (ex.: Econet) rejeitam esse formato
     * por não ser o XML padrão da NF-e. Removemos o wrapper aqui, no momento da entrega,
     * para cobrir tanto os registros antigos quanto qualquer caso futuro equivalente.
     */
    public static function removerWrapperProc(?string $xml): ?string
    {
        if ($xml === null || (! str_contains($xml, '<proc ') && ! str_contains($xml, '<proc>'))) {
            return $xml;
        }

        try {
            libxml_use_internal_errors(true);
            $elemento = new \SimpleXMLElement($xml);

            if (strtolower($elemento->getName()) !== 'proc') {
                return $xml;
            }

            $dom = dom_import_simplexml($elemento);

            foreach ($dom->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    return $node->ownerDocument->saveXML($node);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[NF-e] removerWrapperProc: falha ao processar XML', ['msg' => $e->getMessage()]);
        }

        return $xml;
    }

    /**
     * Acrescenta o CEST (<det><prod><CEST>) ao final da descrição do produto
     * (<xProd>) antes de gerar o DANFE/DANFE-NFC-e, replicando o formato que
     * o Sieg já usa nos XMLs baixados por lá. A lib nfephp-org/sped-da lê
     * xProd direto do XML, então o ajuste é feito aqui, sem tocar no vendor.
     */
    public static function adicionarCestNaDescricao(?string $xml): ?string
    {
        if ($xml === null || ! str_contains($xml, '<CEST>')) {
            return $xml;
        }

        try {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            foreach ($dom->getElementsByTagName('prod') as $prod) {
                $cest = $prod->getElementsByTagName('CEST')->item(0);
                $xProd = $prod->getElementsByTagName('xProd')->item(0);

                if ($cest === null || $xProd === null || trim($cest->nodeValue) === '') {
                    continue;
                }

                $xProd->nodeValue = trim($xProd->nodeValue).' - CEST: '.trim($cest->nodeValue);
            }

            return $dom->saveXML();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[NF-e] adicionarCestNaDescricao: falha ao processar XML', ['msg' => $e->getMessage()]);
        }

        return $xml;
    }

    /**
     * Classifica o documento em 'entrada' ou 'saida' do ponto de vista do
     * cliente (mesma lógica usada na tela de NF-e, função `direcaoDoc` em
     * resources/views/nfe/index.blade.php — mantenha as duas em sincronia).
     *
     * tp_nf (0=entrada, 1=saída) é sempre da perspectiva de quem EMITIU a nota
     * — não da empresa consultada. Uma venda normal de terceiro pro cliente
     * tem tp_nf=1 (saída do terceiro), mas é uma entrada pro cliente. Por isso
     * a direção real depende de duas coisas: se o cliente é o emitente ou o
     * destinatário, e o tp_nf. Sem tp_nf (CT-e, ou documento antigo ainda não
     * migrado), cai no fallback por emitente.
     */
    public function direcao(string $clienteCnpj): string
    {
        $clienteEhEmitente = $clienteCnpj !== '' && preg_replace('/\D/', '', (string) $this->emitente_doc) === $clienteCnpj;

        if ($this->tp_nf !== null) {
            $ehSaidaDoEmitente = (int) $this->tp_nf === 1;

            return $clienteEhEmitente === $ehSaidaDoEmitente ? 'saida' : 'entrada';
        }

        return $clienteEhEmitente ? 'saida' : 'entrada';
    }

    /**
     * Monta a expressão SQL usada para decidir em que período uma nota entra (ver
     * comentário em doPeriodo) e o CNPJ (só dígitos) do cliente, usado como bind dela.
     *
     * CT-e sempre entra pela data de EMISSÃO (dhEmi) — é o que a Sefaz usa no extrato
     * de CT-e e o que o Cofre Fiscal já lista. CT-e não tem dhSaiEnt, e a coluna
     * data_saida_entrada nunca é preenchida pra ele; a regra emitente-vs-terceiro
     * só faz sentido pra NF-e/NFC-e (mercadoria circulando).
     *
     * @return array{0: string, 1: string}
     */
    private static function dataEfetivaSql(int $clienteId): array
    {
        $clienteCnpj = preg_replace('/\D/', '', Cliente::find($clienteId)?->cpfcnpj ?? '');

        $dataEfetiva = "CASE WHEN tipo = 'cte' THEN data_emissao "
            .'WHEN emitente_doc = ? THEN data_emissao '
            .'ELSE COALESCE(data_saida_entrada, data_emissao) END';

        return [$dataEfetiva, $clienteCnpj];
    }

    /**
     * Ranking de fornecedores (emitentes de NF-e de entrada) optantes pelo Simples
     * Nacional num mês, para o dashboard "Top Fornecedores (SN)" da tela de NF-e.
     *
     * Só olha `documentos_fiscais` (GROUP BY em SQL), sem reabrir xml_content —
     * por isso depende da coluna emitente_crt já preenchida (sincronização nova
     * ou `fiscal:backfill-emitente-crt`).
     *
     * Considera fornecedor = terceiro que emitiu a nota (emitente_doc != CNPJ do
     * cliente), modelo NF-e, não cancelada, dentro do mês pela mesma "data efetiva"
     * usada no resto da tela (ver dataEfetivaSql / doPeriodo).
     *
     * @return array{periodo: string, totalGeral: float, fornecedores: array<int, array{cnpj: string, nome: string, total: float, qtd: int}>}
     */
    public static function rankingFornecedoresSimples(int $clienteId, string $dataInicio, string $dataFim, int $limite = 10): array
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        $linhas = static::where('cliente_id', $clienteId)
            ->where('tipo', 'nfe')
            ->whereIn('emitente_crt', self::CRT_SIMPLES_NACIONAL)
            ->where(fn ($q) => $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada'))
            ->when($clienteCnpj !== '', fn ($q) => $q->where(fn ($sub) => $sub->whereNull('emitente_doc')->orWhereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') != ?", [$clienteCnpj])))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim])
            ->selectRaw('emitente_doc, MAX(emitente_nome) as nome, SUM(valor) as total, COUNT(*) as qtd')
            ->groupBy('emitente_doc')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'totalGeral' => (float) $linhas->sum('total'),
            'fornecedores' => $linhas->map(fn ($l) => [
                'cnpj' => (string) $l->emitente_doc,
                'nome' => $l->nome ?: 'Emitente não identificado',
                'total' => (float) $l->total,
                'qtd' => (int) $l->qtd,
            ])->all(),
        ];
    }

    /** Rótulo "dd/mm/aaaa a dd/mm/aaaa" (ou só a data, quando início == fim) pros dashboards. */
    private static function rotuloPeriodo(string $dataInicio, string $dataFim): string
    {
        $fmt = fn (string $d) => date('d/m/Y', strtotime($d));

        return $dataInicio === $dataFim ? $fmt($dataInicio) : $fmt($dataInicio).' a '.$fmt($dataFim);
    }

    /**
     * Ranking de produtos mais vendidos (por valor) num mês, para o dashboard
     * "Top Produtos" da aba Dashboards. Precisa abrir o xml_content de cada
     * NF-e de saída do período e somar os itens (<det><prod>) — não dá pra
     * fazer só em SQL porque item não tem coluna própria.
     *
     * "Vendido" = NF-e em que o cliente é o emitente e não é devolução
     * (tp_nf != 0). Agrupa por código do produto (cProd) quando existir,
     * senão pela descrição normalizada.
     *
     * @return array{periodo: string, totalGeral: float, qtdNotas: int, produtos: array<int, array{codigo: ?string, descricao: string, ncm: ?string, cest: ?string, unidade: ?string, cfops: array<int, string>, valor: float, quantidade: float, notas: int}>}
     */
    public static function rankingProdutosVendidos(int $clienteId, string $dataInicio, string $dataFim, int $limite = 10): array
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        $query = static::where('cliente_id', $clienteId)
            ->where('tipo', 'nfe')
            ->where(fn ($q) => $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada'))
            ->where(fn ($q) => $q->whereNull('tp_nf')->orWhere('tp_nf', '!=', 0))
            ->whereNotNull('xml_content')
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        if ($clienteCnpj !== '') {
            $query->whereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') = ?", [$clienteCnpj]);
        }

        $agg = [];
        $qtdNotas = 0;

        foreach ($query->select('id', 'xml_content')->cursor() as $doc) {
            $itens = self::extrairItensProduto($doc->xml_content);

            if ($itens === []) {
                continue;
            }

            $qtdNotas++;

            foreach ($itens as $item) {
                $chave = $item['codigo'] !== null && $item['codigo'] !== ''
                    ? 'c:'.$item['codigo']
                    : 'd:'.mb_strtoupper(trim($item['descricao']));

                if (! isset($agg[$chave])) {
                    $agg[$chave] = [
                        'codigo' => $item['codigo'] ?: null,
                        'descricao' => $item['descricao'] ?: '(sem descrição)',
                        'ncm' => $item['ncm'] ?: null,
                        'cest' => $item['cest'] ?: null,
                        'unidade' => $item['unidade'] ?: null,
                        'cfops' => [],
                        'valor' => 0.0,
                        'quantidade' => 0.0,
                        'notas' => 0,
                    ];
                }

                $agg[$chave]['valor'] += $item['valor'];
                $agg[$chave]['quantidade'] += $item['quantidade'];
                $agg[$chave]['notas']++;

                if ($item['cfop'] && ! in_array($item['cfop'], $agg[$chave]['cfops'], true)) {
                    $agg[$chave]['cfops'][] = $item['cfop'];
                }

                if (! $agg[$chave]['ncm'] && $item['ncm']) {
                    $agg[$chave]['ncm'] = $item['ncm'];
                }
            }
        }

        usort($agg, fn ($a, $b) => $b['valor'] <=> $a['valor']);

        foreach ($agg as &$linha) {
            sort($linha['cfops']);
        }
        unset($linha);

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'totalGeral' => array_sum(array_column($agg, 'valor')),
            'qtdNotas' => $qtdNotas,
            'produtos' => array_slice(array_values($agg), 0, $limite),
        ];
    }

    /**
     * Resumo de operações interestaduais (compras e vendas) por UF, para o
     * dashboard "Compras e Vendas Interestaduais" (mapa) da aba Dashboards.
     *
     * Para cada NF-e do período abre o xml_content e descobre:
     *  - papel: se o cliente é o emitente → venda; senão → compra;
     *  - UF da contraparte: enderDest/UF (venda) ou enderEmit/UF (compra);
     *  - se é interestadual: ide/idDest = 2, ou (fallback) UF da contraparte
     *    diferente da UF do cliente. idDest = 3 (exterior) é ignorado.
     *
     * O valor de cada nota vem da coluna `valor` (vNF), não do XML.
     *
     * @return array{periodo: string, clienteUf: ?string, totalCompras: float, totalVendas: float, ufs: array<int, array{uf: string, compras: float, vendas: float, total: float}>}
     */
    public static function resumoInterestadual(int $clienteId, string $dataInicio, string $dataFim): array
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        $clienteUf = self::descobrirUfCliente($clienteId, $clienteCnpj);

        $query = static::where('cliente_id', $clienteId)
            ->where('tipo', 'nfe')
            ->where(fn ($q) => $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada'))
            ->whereNotNull('xml_content')
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        $ufs = [];
        $totalCompras = 0.0;
        $totalVendas = 0.0;

        foreach ($query->select('id', 'valor', 'emitente_doc', 'xml_content')->cursor() as $doc) {
            $info = self::extrairOperacaoInterestadual($doc->xml_content, $clienteCnpj, $clienteUf);

            if ($info === null || ! $info['interestadual'] || $info['uf'] === null) {
                continue;
            }

            $valor = (float) $doc->valor;
            $uf = $info['uf'];

            if (! isset($ufs[$uf])) {
                $ufs[$uf] = ['uf' => $uf, 'compras' => 0.0, 'vendas' => 0.0, 'total' => 0.0];
            }

            if ($info['papel'] === 'venda') {
                $ufs[$uf]['vendas'] += $valor;
                $totalVendas += $valor;
            } else {
                $ufs[$uf]['compras'] += $valor;
                $totalCompras += $valor;
            }

            $ufs[$uf]['total'] += $valor;
        }

        usort($ufs, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'clienteUf' => $clienteUf,
            'totalCompras' => round($totalCompras, 2),
            'totalVendas' => round($totalVendas, 2),
            'ufs' => array_values($ufs),
        ];
    }

    /** Máximo de notas listadas no detalhamento da auditoria DIFAL/FCP (contadores continuam somando tudo). */
    private const AUDITORIA_DIFAL_MAX_NOTAS = 800;

    /**
     * Auditoria DIFAL / FCP (Fase 1) do dashboard "Auditoria DIFAL / FCP" da aba
     * Dashboards. Varre as NF-e de SAÍDA do cliente no período (ele é o emitente)
     * e, para cada uma, decide se a operação exige partilha de DIFAL a consumidor
     * final não contribuinte (LC 190/2022) e confere o que a própria nota destacou
     * no grupo <ICMSUFDest>.
     *
     * Fase 1 = coerência interna do XML. Fase 2 (ativa) = recalcula o valor
     * esperado usando a tabela de alíquota interna por UF (com vigência) e o teto
     * de FCP por UF em config/fiscal_aliquotas.php, e estima o DIFAL não recolhido
     * das notas que não destacaram. Status por nota:
     *  - ok           → exige DIFAL e o grupo <ICMSUFDest> está presente e coerente;
     *  - faltou       → exige DIFAL e nenhum item tem <ICMSUFDest> (não destacou);
     *  - inconsistente → tem <ICMSUFDest> mas com partilha/base/FCP/alíquota/valor
     *                    incoerente, ou CFOP interno (5xxx) numa operação interestadual;
     *  - nao_aplica   → operação interna, exterior, ou destinatário contribuinte.
     *
     * @return array{periodo: string, contadores: array<string, int>, totalDifalDestacado: float, totalFcpDestacado: float, totalDifalEstimadoFaltante: float, totalNotas: int, notasListadas: int, notas: array<int, array<string, mixed>>}
     */
    public static function auditoriaDifalFcp(int $clienteId, string $dataInicio, string $dataFim): array
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);
        $clienteUf = self::descobrirUfCliente($clienteId, $clienteCnpj);

        $query = static::where('cliente_id', $clienteId)
            ->where('tipo', 'nfe')
            ->where(fn ($q) => $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada'))
            ->whereNotNull('xml_content')
            ->when($clienteCnpj !== '', fn ($q) => $q->whereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') = ?", [$clienteCnpj]))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        $contadores = ['ok' => 0, 'faltou' => 0, 'inconsistente' => 0, 'nao_aplica' => 0];
        $totalDifal = 0.0;
        $totalFcp = 0.0;
        $totalEstimado = 0.0;
        $totalNotas = 0;
        $notas = [];

        foreach ($query->select('id', 'chave_acesso', 'numero', 'valor', 'data_emissao', 'xml_content')->cursor() as $doc) {
            $aud = self::auditarNotaDifalFcp($doc->xml_content, $clienteUf, $doc->data_emissao?->format('Y-m-d'));

            if ($aud === null) {
                continue;
            }

            $totalNotas++;
            $contadores[$aud['status']]++;
            $totalDifal += $aud['difal'];
            $totalFcp += $aud['fcp'];
            $totalEstimado += $aud['difalEstimado'] ?? 0.0;

            if ($aud['status'] !== 'nao_aplica' && count($notas) < self::AUDITORIA_DIFAL_MAX_NOTAS) {
                $notas[] = [
                    'chave' => (string) $doc->chave_acesso,
                    'numero' => (string) $doc->numero,
                    'data' => $doc->data_emissao?->format('Y-m-d'),
                    'destinatario' => $aud['destinatario'],
                    'uf' => $aud['uf'],
                    'valor' => (float) $doc->valor,
                    'status' => $aud['status'],
                    'motivos' => $aud['motivos'],
                    'difal' => round($aud['difal'], 2),
                    'fcp' => round($aud['fcp'], 2),
                    'difalEstimado' => round($aud['difalEstimado'] ?? 0.0, 2),
                ];
            }
        }

        // Prioriza o que precisa de olho humano: faltou → inconsistente → ok.
        $ordem = ['faltou' => 0, 'inconsistente' => 1, 'ok' => 2];
        usort($notas, fn ($a, $b) => [$ordem[$a['status']], $b['valor']] <=> [$ordem[$b['status']], $a['valor']]);

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'contadores' => $contadores,
            'totalDifalDestacado' => round($totalDifal, 2),
            'totalFcpDestacado' => round($totalFcp, 2),
            'totalDifalEstimadoFaltante' => round($totalEstimado, 2),
            'totalNotas' => $totalNotas,
            'notasListadas' => count($notas),
            'notas' => $notas,
        ];
    }

    /**
     * Audita uma NF-e (XML) para DIFAL/FCP a consumidor final não contribuinte.
     * Retorna null se não for NF-e válida (resumo/proc sem infNFe).
     *
     * @return array{status: string, uf: ?string, destinatario: string, motivos: array<int, string>, difal: float, fcp: float, difalEstimado: float}|null
     */
    private static function auditarNotaDifalFcp(?string $xml, ?string $clienteUf, ?string $dataFallback = null): ?array
    {
        if (empty($xml)) {
            return null;
        }

        libxml_use_internal_errors(true);

        try {
            $obj = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return null;
        }

        if (! $obj->xpath("//*[local-name()='infNFe']")) {
            return null;
        }

        $x = fn (string $path) => trim((string) ($obj->xpath($path)[0] ?? ''));
        $num = fn (string $s) => (float) str_replace(',', '.', $s);

        $ufDest = strtoupper($x("//*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']"));
        $destNome = (string) mb_convert_encoding($x("//*[local-name()='dest']/*[local-name()='xNome']"), 'UTF-8', 'UTF-8');
        $idDest = $x("//*[local-name()='ide']/*[local-name()='idDest']");
        $indFinal = $x("//*[local-name()='ide']/*[local-name()='indFinal']");
        $indIEDest = $x("//*[local-name()='dest']/*[local-name()='indIEDest']");
        $ufEmit = strtoupper($x("//*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='UF']")) ?: ($clienteUf ?: '');
        $dataEmissao = substr($x("//*[local-name()='ide']/*[local-name()='dhEmi']")
            ?: $x("//*[local-name()='ide']/*[local-name()='dEmi']"), 0, 10) ?: $dataFallback;

        $base = ['uf' => $ufDest ?: null, 'destinatario' => $destNome ?: 'Destinatário não identificado', 'difal' => 0.0, 'fcp' => 0.0, 'difalEstimado' => 0.0];
        $resultado = fn (array $extra) => array_merge($base, $extra);

        $interestadual = $idDest === '2'
            ? true
            : ($idDest === '1' ? false : ($clienteUf !== null && $ufDest !== '' && $ufDest !== $clienteUf));

        // Consumidor final não contribuinte: indIEDest 9 (não contribuinte) ou 2 (isento de IE).
        $consumidorFinalNaoContrib = $indFinal === '1' && in_array($indIEDest, ['2', '9'], true);

        if ($idDest === '3' || $ufDest === 'EX' || ! $interestadual || ! $consumidorFinalNaoContrib) {
            return $resultado(['status' => 'nao_aplica', 'motivos' => []]);
        }

        // Fase 2: tabela externa de alíquota interna por UF (com vigência) e teto de FCP.
        $cfg = config('fiscal_aliquotas');
        $tolValor = (float) ($cfg['tolerancia_valor'] ?? 0.05);
        $tolPp = (float) ($cfg['tolerancia_aliquota_pp'] ?? 0.5);
        $aliqInterna = self::aliquotaInternaUf($ufDest, $dataEmissao, $cfg);
        $fcpTeto = $cfg['fcp_max'][$ufDest] ?? null;
        $aliqInterFallback = self::aliquotaInterestadual($ufEmit, $ufDest, $cfg);

        // Soma o que a nota destacou de DIFAL/FCP e olha a partilha item a item.
        $grupos = $obj->xpath("//*[local-name()='det']/*[local-name()='imposto']/*[local-name()='ICMSUFDest']");
        $cfops = array_map(fn ($n) => (string) $n, $obj->xpath("//*[local-name()='det']/*[local-name()='prod']/*[local-name()='CFOP']") ?: []);

        $motivos = [];
        $vICMSUFDest = 0.0;
        $vFCPUFDest = 0.0;
        $partilhaForaDe100 = false;
        $baseZeroComAliquota = false;
        $fcpZeroComAliquota = false;
        $aliquotasInternasDestacadas = [];
        $valorDivergente = 0.0;
        $fcpValorDivergente = 0.0;
        $fcpAcimaTeto = null;

        foreach ($grupos as $g) {
            $gx = fn (string $tag) => trim((string) ($g->xpath("*[local-name()='{$tag}']")[0] ?? ''));

            $pUFDest = $num($gx('pICMSUFDest'));
            $pInter = $num($gx('pICMSInter'));
            $vBCUFDest = $num($gx('vBCUFDest'));
            $vICMSItem = $num($gx('vICMSUFDest'));
            $pFCP = $num($gx('pFCPUFDest'));
            $vBCFCP = $num($gx('vBCFCPUFDest')) ?: $vBCUFDest;
            $vFCPItem = $num($gx('vFCPUFDest'));

            $vICMSUFDest += $vICMSItem;
            $vFCPUFDest += $vFCPItem;

            $pInterPart = $gx('pICMSInterPart');
            if ($pInterPart !== '' && abs($num($pInterPart) - 100) > 0.01) {
                $partilhaForaDe100 = true;
            }

            if ($pUFDest > 0 && $vBCUFDest > 0 && $vICMSItem <= 0 && $pUFDest > $pInter) {
                $baseZeroComAliquota = true;
            }

            if ($pFCP > 0 && $vFCPItem <= 0) {
                $fcpZeroComAliquota = true;
            }

            if ($pUFDest > 0) {
                $aliquotasInternasDestacadas[(string) $pUFDest] = $pUFDest;
            }

            // Fase 2 — recálculo do valor destacado no item.
            if ($vBCUFDest > 0 && $pUFDest > $pInter) {
                $esperado = round($vBCUFDest * ($pUFDest - $pInter) / 100, 2);
                $valorDivergente += abs($esperado - $vICMSItem);
            }
            if ($pFCP > 0 && $vBCFCP > 0) {
                $esperadoFcp = round($vBCFCP * $pFCP / 100, 2);
                $fcpValorDivergente += abs($esperadoFcp - $vFCPItem);
            }
            if ($fcpTeto !== null && $pFCP > $fcpTeto + 0.001) {
                $fcpAcimaTeto = max($fcpAcimaTeto ?? 0, $pFCP);
            }
        }

        $temCfopInterestadual = false;
        $temCfopInterno = false;
        foreach ($cfops as $cfop) {
            $inicio = substr(preg_replace('/\D/', '', $cfop), 0, 1);
            if ($inicio === '6') {
                $temCfopInterestadual = true;
            } elseif ($inicio === '5') {
                $temCfopInterno = true;
            }
        }

        if (count($grupos) === 0) {
            if ($temCfopInterno && ! $temCfopInterestadual) {
                $motivos[] = 'Operação interestadual a consumidor final não contribuinte com CFOP interno (5xxx).';
            }
            $motivos[] = 'Nenhum item destacou o grupo ICMSUFDest (DIFAL não partilhado).';

            // Estimativa grosseira do DIFAL não recolhido: valor da nota x (alíquota interna do
            // destino - interestadual). Só orienta a triagem; o valor exato depende da base "por
            // dentro" e de alíquotas específicas por produto.
            $estimado = 0.0;
            if ($aliqInterna !== null && $aliqInterna > $aliqInterFallback) {
                $valorNota = $num($x("//*[local-name()='total']/*[local-name()='ICMSTot']/*[local-name()='vNF']"));
                $estimado = round($valorNota * ($aliqInterna - $aliqInterFallback) / 100, 2);
                $motivos[] = sprintf(
                    'DIFAL estimado não recolhido: R$ %s (%s%% interna %s − %s%% interestadual, sobre R$ %s).',
                    number_format($estimado, 2, ',', '.'),
                    rtrim(rtrim(number_format($aliqInterna, 2, ',', '.'), '0'), ','),
                    $ufDest,
                    rtrim(rtrim(number_format($aliqInterFallback, 2, ',', '.'), '0'), ','),
                    number_format($valorNota, 2, ',', '.')
                );
            }

            return $resultado(['status' => 'faltou', 'motivos' => $motivos, 'difalEstimado' => $estimado]);
        }

        if ($partilhaForaDe100) {
            $motivos[] = 'pICMSInterPart diferente de 100% (partilha antiga; desde 2019 a partilha é 100% para o destino).';
        }
        if ($baseZeroComAliquota) {
            $motivos[] = 'Item com alíquota interna do destino informada mas vICMSUFDest zerado.';
        }
        if ($fcpZeroComAliquota) {
            $motivos[] = 'Item com pFCPUFDest informado mas vFCPUFDest zerado.';
        }
        if ($temCfopInterno && ! $temCfopInterestadual) {
            $motivos[] = 'CFOP interno (5xxx) numa operação interestadual.';
        }
        if ($valorDivergente > $tolValor) {
            $motivos[] = sprintf('Valor de ICMS DIFAL destacado não bate com o recálculo (diferença de R$ %s).', number_format($valorDivergente, 2, ',', '.'));
        }
        if ($fcpValorDivergente > $tolValor) {
            $motivos[] = sprintf('Valor de FCP destacado não bate com o recálculo (diferença de R$ %s).', number_format($fcpValorDivergente, 2, ',', '.'));
        }
        if ($fcpAcimaTeto !== null) {
            $motivos[] = sprintf('FCP destacado (%s%%) acima do teto de %s%% da UF %s.', rtrim(rtrim(number_format($fcpAcimaTeto, 2, ',', '.'), '0'), ','), rtrim(rtrim(number_format((float) $fcpTeto, 2, ',', '.'), '0'), ','), $ufDest);
        }
        if ($aliqInterna !== null && $aliquotasInternasDestacadas !== []) {
            $foraDaTabela = array_filter($aliquotasInternasDestacadas, fn ($p) => abs($p - $aliqInterna) > $tolPp);
            if ($foraDaTabela !== []) {
                $lista = implode('%, ', array_map(fn ($p) => rtrim(rtrim(number_format($p, 2, ',', '.'), '0'), ','), $foraDaTabela));
                $motivos[] = sprintf(
                    'Alíquota interna destacada (%s%%) diverge da alíquota geral de %s%% da UF %s — confira se o produto tem alíquota específica.',
                    $lista,
                    rtrim(rtrim(number_format($aliqInterna, 2, ',', '.'), '0'), ','),
                    $ufDest
                );
            }
        }

        return $resultado([
            'status' => $motivos === [] ? 'ok' : 'inconsistente',
            'motivos' => $motivos,
            'difal' => round($vICMSUFDest, 2),
            'fcp' => round($vFCPUFDest, 2),
        ]);
    }

    /** Alíquota interna geral de ICMS da UF vigente na data (Y-m-d), da tabela config/fiscal_aliquotas.php. Null se desconhecida. */
    private static function aliquotaInternaUf(?string $uf, ?string $data, ?array $cfg = null): ?float
    {
        if ($uf === null || $uf === '') {
            return null;
        }

        $cfg ??= config('fiscal_aliquotas');
        $faixas = $cfg['internas'][strtoupper($uf)] ?? null;

        if (! $faixas) {
            return null;
        }

        $data = $data ?: date('Y-m-d');
        $aliquota = null;

        foreach ($faixas as $faixa) {
            if (($faixa['desde'] ?? '0000-00-00') <= $data) {
                $aliquota = (float) $faixa['aliquota'];
            }
        }

        return $aliquota ?? (float) ($faixas[0]['aliquota'] ?? 0) ?: null;
    }

    /**
     * Alíquota interestadual de ICMS presumida origem → destino (regra do Senado
     * Res. 22/89 e 13/12): 7% do Sul/Sudeste (exceto ES) para Norte/Nordeste/
     * Centro-Oeste + ES; 12% nos demais casos. Importados (4%) não dá para inferir
     * sem olhar a origem da mercadoria — fica de fora. Usado só como fallback
     * quando a nota não informa pICMSInter.
     */
    private static function aliquotaInterestadual(?string $ufOrigem, ?string $ufDestino, ?array $cfg = null): float
    {
        $cfg ??= config('fiscal_aliquotas');
        $padrao = (float) ($cfg['interestadual_padrao'] ?? 12.0);

        $sulSudeste = ['SP', 'RJ', 'MG', 'PR', 'SC', 'RS'];
        $norteNordesteCO = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'MA', 'PB', 'PA', 'PE', 'PI', 'RN', 'RO', 'RR', 'SE', 'TO', 'DF', 'GO', 'MT', 'MS', 'ES'];

        if (in_array(strtoupper((string) $ufOrigem), $sulSudeste, true)
            && in_array(strtoupper((string) $ufDestino), $norteNordesteCO, true)) {
            return 7.0;
        }

        return $padrao;
    }

    /** Máximo de números faltantes listados por série na análise de quebra de numeração. */
    private const QUEBRA_NUM_MAX_POR_SERIE = 300;

    /**
     * Análise de quebra de numeração das NF-e / NFC-e EMITIDAS pelo cliente no
     * período (dashboard "Quebra de Numeração"). Para cada série, ordena os
     * números emitidos e aponta os buracos internos (entre o menor e o maior
     * número presentes) — número que ficou sem nota é indício de nota não
     * declarada, inutilização não registrada, ou emissão em outra série/ambiente.
     *
     * Série e número saem da própria chave de acesso (posições 23-25 e 26-34),
     * sem reabrir o XML. Notas canceladas/denegadas CONTAM como presentes — elas
     * consumiram o número. Buracos nas pontas não são apontados (não dá para
     * saber, só com o período, se existe nota antes da primeira / depois da última).
     *
     * @return array{periodo: string, totalEmitidas: int, totalFaltando: int, series: array<int, array{serie: string, tipo: string, menor: int, maior: int, emitidas: int, faltando: array<int, int>, qtdFaltando: int, duplicados: array<int, int>}>}
     */
    public static function quebrasNumeracaoNfe(int $clienteId, string $dataInicio, string $dataFim): array
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        $query = static::where('cliente_id', $clienteId)
            ->whereIn('tipo', ['nfe', 'nfce'])
            ->whereNotNull('chave_acesso')
            ->when($clienteCnpj !== '', fn ($q) => $q->whereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') = ?", [$clienteCnpj]))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        // agrupador: [ "tipo|serie" => [ numero => contagem ] ]
        $mapa = [];
        $totalEmitidas = 0;

        foreach ($query->select('tipo', 'chave_acesso')->cursor() as $doc) {
            $chave = preg_replace('/\D/', '', (string) $doc->chave_acesso);

            if (strlen($chave) !== 44) {
                continue;
            }

            $serie = ltrim(substr($chave, 22, 3), '0') ?: '0';
            $numero = (int) substr($chave, 25, 9);

            if ($numero <= 0) {
                continue;
            }

            $key = $doc->tipo.'|'.$serie;
            $mapa[$key][$numero] = ($mapa[$key][$numero] ?? 0) + 1;
            $totalEmitidas++;
        }

        $series = [];
        $totalFaltando = 0;

        foreach ($mapa as $key => $numeros) {
            [$tipo, $serie] = explode('|', $key, 2);
            ksort($numeros);

            $menor = (int) array_key_first($numeros);
            $maior = (int) array_key_last($numeros);

            // menor e maior estão sempre presentes, então todo faltante é interno:
            // total de inteiros no intervalo menos os números realmente emitidos.
            // Conta por aritmética (não por loop) — a faixa pode ter milhões de
            // números (série reaproveitada entre pontos de emissão, número legado).
            $qtdFaltando = ($maior - $menor + 1) - count($numeros);

            $faltando = [];
            if ($qtdFaltando > 0) {
                for ($n = $menor + 1; $n < $maior && count($faltando) < self::QUEBRA_NUM_MAX_POR_SERIE; $n++) {
                    if (! isset($numeros[$n])) {
                        $faltando[] = $n;
                    }
                }
            }

            $duplicados = array_keys(array_filter($numeros, fn ($c) => $c > 1));
            $totalFaltando += $qtdFaltando;

            $series[] = [
                'serie' => $serie,
                'tipo' => $tipo,
                'menor' => $menor,
                'maior' => $maior,
                'emitidas' => count($numeros),
                'faltando' => $faltando,
                'qtdFaltando' => $qtdFaltando,
                'duplicados' => array_values($duplicados),
            ];
        }

        // Séries com mais buracos primeiro.
        usort($series, fn ($a, $b) => $b['qtdFaltando'] <=> $a['qtdFaltando'] ?: strcmp($a['tipo'].$a['serie'], $b['tipo'].$b['serie']));

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'totalEmitidas' => $totalEmitidas,
            'totalFaltando' => $totalFaltando,
            'series' => $series,
        ];
    }

    /**
     * Limites de receita bruta (12 meses) por regime, para o monitor do Simples/MEI.
     * Chave em MAIÚSCULA — o lookup normaliza clientes.regime_tributario com
     * strtoupper/trim (a coluna chega com casing variado no banco).
     *
     * `tolerancia` = teto de estouro efetivo (limite + 20% de LC 123/2006):
     * entre `limite` e `tolerancia` a exclusão vale só no ano seguinte; acima de
     * `tolerancia`, exclusão retroativa.
     */
    private const LIMITES_RECEITA = [
        'MEI' => ['limite' => 81000.0, 'tolerancia' => 97200.0, 'sublimite' => null],
        'SIMPLES NACIONAL' => ['limite' => 4800000.0, 'tolerancia' => 5760000.0, 'sublimite' => 3600000.0],
    ];

    /**
     * Monitor de limite do Simples Nacional / MEI (dashboard "Limite do Simples").
     * Soma a receita bruta de SAÍDA (NF-e/NFC-e emitidas pelo cliente, tp_nf != 0,
     * não canceladas) dos 12 meses que terminam no mês de `dataFim`, compara com
     * o limite do regime e projeta o fechamento do ano-calendário.
     *
     * Não substitui o PGDAS — é uma estimativa a partir dos XMLs sincronizados
     * (pode faltar nota não baixada; NFS-e não entra aqui). Serve de alerta
     * antecipado de desenquadramento.
     *
     * @return array{periodo: string, regime: ?string, mesReferencia: string, limite: ?float, sublimite: ?float, tolerancia: ?float, rbt12: float, rba: float, percentual: ?float, status: string, projecaoAno: float, meses: array<int, array{mes: string, valor: float}>}
     */
    public static function monitorLimiteSimples(int $clienteId, string $dataInicio, string $dataFim): array
    {
        $cliente = Cliente::find($clienteId);
        $clienteCnpj = preg_replace('/\D/', '', $cliente?->cpfcnpj ?? '');
        $regime = $cliente?->regime_tributario;

        $fim = (new \DateTimeImmutable($dataFim))->modify('last day of this month');
        $inicio12 = $fim->modify('first day of this month')->modify('-11 months');

        $base = static::where('cliente_id', $clienteId)
            ->whereIn('tipo', ['nfe', 'nfce'])
            ->where(fn ($q) => $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada'))
            ->where(fn ($q) => $q->whereNull('tp_nf')->orWhere('tp_nf', '!=', '0'))
            ->when($clienteCnpj !== '', fn ($q) => $q->whereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') = ?", [$clienteCnpj]));

        $linhas = (clone $base)
            ->whereBetween('data_emissao', [$inicio12->format('Y-m-d'), $fim->format('Y-m-d')])
            ->selectRaw("DATE_FORMAT(data_emissao, '%Y-%m') as mes, SUM(valor) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $meses = [];
        $rbt12 = 0.0;

        for ($m = $inicio12; $m <= $fim; $m = $m->modify('+1 month')) {
            $chave = $m->format('Y-m');
            $valor = (float) ($linhas[$chave] ?? 0);
            $meses[] = ['mes' => $chave, 'valor' => round($valor, 2)];
            $rbt12 += $valor;
        }

        $rba = (float) (clone $base)
            ->whereBetween('data_emissao', [$fim->format('Y').'-01-01', $fim->format('Y-m-d')])
            ->sum('valor');

        $conf = self::LIMITES_RECEITA[strtoupper(trim((string) $regime))] ?? null;
        $limite = $conf['limite'] ?? null;
        $percentual = $limite ? round($rbt12 / $limite * 100, 1) : null;

        $mesesDecorridos = (int) $fim->format('n');
        $projecaoAno = $mesesDecorridos > 0 ? round($rba / $mesesDecorridos * 12, 2) : 0.0;

        $status = 'sem_limite';
        if ($limite !== null) {
            $teto = $conf['tolerancia'] ?? $limite;
            $status = $rbt12 >= $teto ? 'estouro'
                : ($rbt12 >= $limite ? 'tolerancia'
                    : ($percentual >= 80 ? 'atencao' : 'ok'));
        }

        return [
            'periodo' => self::rotuloPeriodo($dataInicio, $dataFim),
            'regime' => $regime,
            'mesReferencia' => $fim->format('Y-m'),
            'limite' => $limite,
            'sublimite' => $conf['sublimite'] ?? null,
            'tolerancia' => $conf['tolerancia'] ?? null,
            'rbt12' => round($rbt12, 2),
            'rba' => round($rba, 2),
            'percentual' => $percentual,
            'status' => $status,
            'projecaoAno' => $projecaoAno,
            'meses' => $meses,
        ];
    }

    /** UF do cliente: a mais frequente em enderEmit/UF das notas que ele mesmo emitiu; fallback pra clientes.estado. */
    private static function descobrirUfCliente(int $clienteId, string $clienteCnpj): ?string
    {
        if ($clienteCnpj !== '') {
            $amostra = static::where('cliente_id', $clienteId)
                ->where('tipo', 'nfe')
                ->whereNotNull('xml_content')
                ->whereRaw("REPLACE(REPLACE(REPLACE(emitente_doc, '.', ''), '/', ''), '-', '') = ?", [$clienteCnpj])
                ->orderByDesc('id')
                ->limit(20)
                ->pluck('xml_content');

            $contagem = [];

            foreach ($amostra as $xml) {
                libxml_use_internal_errors(true);

                try {
                    $obj = new \SimpleXMLElement($xml);
                } catch (\Throwable) {
                    continue;
                }

                $uf = trim((string) ($obj->xpath("//*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='UF']")[0] ?? ''));

                if ($uf !== '') {
                    $contagem[$uf] = ($contagem[$uf] ?? 0) + 1;
                }
            }

            if ($contagem !== []) {
                arsort($contagem);

                return array_key_first($contagem);
            }
        }

        $estado = Cliente::find($clienteId)?->estado;

        return $estado ? strtoupper(substr(trim($estado), 0, 2)) : null;
    }

    /**
     * A partir do XML de uma NF-e, identifica papel (compra/venda pro cliente),
     * UF da contraparte e se a operação é interestadual.
     *
     * @return array{papel: string, uf: ?string, interestadual: bool}|null
     */
    private static function extrairOperacaoInterestadual(?string $xml, string $clienteCnpj, ?string $clienteUf): ?array
    {
        if (empty($xml)) {
            return null;
        }

        libxml_use_internal_errors(true);

        try {
            $obj = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return null;
        }

        if (! $obj->xpath("//*[local-name()='infNFe']")) {
            return null;
        }

        $x = fn (string $path) => trim((string) ($obj->xpath($path)[0] ?? ''));

        $emitDoc = preg_replace('/\D/', '', $x("//*[local-name()='emit']/*[local-name()='CNPJ']")
            ?: $x("//*[local-name()='emit']/*[local-name()='CPF']"));

        $ehVenda = $clienteCnpj !== '' && $emitDoc === $clienteCnpj;

        $ufContraparte = $ehVenda
            ? $x("//*[local-name()='dest']/*[local-name()='enderDest']/*[local-name()='UF']")
            : $x("//*[local-name()='emit']/*[local-name()='enderEmit']/*[local-name()='UF']");

        $ufContraparte = $ufContraparte !== '' ? strtoupper($ufContraparte) : null;

        $idDest = $x("//*[local-name()='ide']/*[local-name()='idDest']");

        // idDest: 1 = interna, 2 = interestadual, 3 = exterior. Sem a tag, cai no
        // fallback comparando a UF da contraparte com a do cliente.
        if ($idDest === '3' || $ufContraparte === 'EX') {
            return ['papel' => $ehVenda ? 'venda' : 'compra', 'uf' => null, 'interestadual' => false];
        }

        $interestadual = $idDest === '2'
            ? true
            : ($idDest === '1'
                ? false
                : ($clienteUf !== null && $ufContraparte !== null && $ufContraparte !== $clienteUf));

        return [
            'papel' => $ehVenda ? 'venda' : 'compra',
            'uf' => $ufContraparte,
            'interestadual' => $interestadual,
        ];
    }

    /**
     * Extrai os itens de produto de um XML de NF-e (só os campos que o dashboard
     * "Top Produtos" usa). Retorna [] pra resumo (resNFe) ou XML inválido.
     *
     * @return array<int, array{codigo: ?string, descricao: string, ncm: ?string, cest: ?string, unidade: ?string, cfop: ?string, valor: float, quantidade: float}>
     */
    private static function extrairItensProduto(?string $xml): array
    {
        if (empty($xml)) {
            return [];
        }

        libxml_use_internal_errors(true);

        try {
            $obj = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return [];
        }

        $dets = $obj->xpath("//*[local-name()='infNFe']/*[local-name()='det']");

        if (! $dets) {
            return [];
        }

        $itens = [];

        foreach ($dets as $det) {
            $prod = $det->xpath("*[local-name()='prod']")[0] ?? null;

            if ($prod === null) {
                continue;
            }

            $t = fn (string $tag) => trim((string) ($prod->xpath("*[local-name()='{$tag}']")[0] ?? ''));

            $vProd = (float) str_replace(',', '.', $t('vProd'));
            $vDesc = (float) str_replace(',', '.', $t('vDesc'));

            $itens[] = [
                'codigo' => $t('cProd') ?: null,
                'descricao' => (string) mb_convert_encoding($t('xProd'), 'UTF-8', 'UTF-8'),
                'ncm' => $t('NCM') ?: null,
                'cest' => $t('CEST') ?: null,
                'unidade' => $t('uCom') ?: null,
                'cfop' => $t('CFOP') ?: null,
                'valor' => round($vProd - $vDesc, 2),
                'quantidade' => (float) str_replace(',', '.', $t('qCom')),
            ];
        }

        return $itens;
    }

    /**
     * Busca os documentos já sincronizados de um cliente, tipo e período —
     * usado pelo relatório fiscal (Excel), que precisa de todos os documentos
     * do período (não paginado) para extrair os itens do xml_content. O
     * chamador deve iterar com cursor()/chunk() para não estourar memória.
     */
    public static function queryPeriodo(int $clienteId, string $tipo, string $dataInicio, string $dataFim)
    {
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        return static::where('cliente_id', $clienteId)
            ->where('tipo', $tipo)
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim])
            ->orderByRaw($dataEfetiva, [$clienteCnpj])
            ->orderBy('id');
    }

    /**
     * Busca os documentos já sincronizados de um cliente num período, no
     * formato de array que a tela de NF-e já espera. Paginado por cursor
     * (keyset) — cada documento carrega o xml_content inteiro, então trazer
     * tudo de uma vez num período grande (milhares de docs) estoura o
     * memory_limit do PHP.
     *
     * Usa cursor (data_efetiva, id) em vez de OFFSET (forPage): com OFFSET,
     * o cron de sincronização automática rodando em paralelo pode inserir/
     * atualizar um documento entre duas chamadas de página e deslocar o
     * offset das páginas seguintes, fazendo um documento "cair no buraco"
     * entre duas páginas e nunca mais ser exibido. Isso foi confirmado em
     * produção (RM Automotive/ALL IN, clientes grandes com 10+ páginas —
     * documentos recuperados manualmente via buscarPorChave() apareciam no
     * Cofre Fiscal mas sumiam da busca por período). Cursor não sofre disso:
     * cada chamada só pede "o que vem depois do último id já visto".
     */
    public static function doPeriodo(int $clienteId, array $tipos, string $dataInicio, string $dataFim, ?array $origens = null, ?string $cursorData = null, ?int $cursorId = null, int $perPage = 500): array
    {
        // O Sefaz usa datas diferentes pra decidir em que período uma nota entra, dependendo
        // de quem emitiu (confirmado testando os dois casos: bate exato com o extrato da
        // Sefaz usando essa regra, e não bate usando só uma das duas datas sempre):
        // - Cliente é o emitente (nota própria): usa a data de EMISSÃO — é a data que ele
        //   mesmo registrou, vale pro período dele independente de quando a mercadoria saiu.
        // - Terceiro é o emitente (cliente é destinatário/remetente): usa a data de
        //   SAÍDA/ENTRADA (dhSaiEnt) quando existir — pro cliente, o que importa é quando a
        //   mercadoria de fato circulou na empresa dele, não quando o terceiro emitiu.
        [$dataEfetiva, $clienteCnpj] = self::dataEfetivaSql($clienteId);

        $query = static::where('cliente_id', $clienteId)
            ->whereIn('tipo', $tipos)
            ->when($origens, fn ($q) => $q->whereIn('origem', $origens))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        $total = (clone $query)->count();

        if ($cursorData !== null && $cursorId !== null) {
            $query->whereRaw("({$dataEfetiva}, id) > (?, ?)", [$clienteCnpj, $cursorData, $cursorId]);
        }

        $registros = $query->selectRaw("documentos_fiscais.*, {$dataEfetiva} as data_efetiva_cursor", [$clienteCnpj])
            ->orderByRaw("{$dataEfetiva}", [$clienteCnpj])->orderBy('id')
            ->limit($perPage)
            ->get();

        $documentos = $registros
            ->map(fn (DocumentoFiscal $doc) => [
                'nsu' => $doc->nsu,
                'tipo' => $doc->tipo,
                'origem' => $doc->origem,
                'chaveAcesso' => $doc->chave_acesso,
                'numero' => $doc->numero,
                'dataEmissao' => $doc->data_emissao?->format('Y-m-d\TH:i:s'),
                'dataSaidaEntrada' => $doc->data_saida_entrada?->format('Y-m-d\TH:i:s'),
                'emitenteNome' => $doc->emitente_nome,
                'emitenteDoc' => $doc->emitente_doc,
                'valor' => $doc->valor,
                'situacao' => $doc->situacao,
                'tpNf' => $doc->tp_nf,
                'papelCte' => $doc->papel_cte,
                'xmlContent' => self::removerWrapperProc($doc->xml_content),
                'sincronizadoEm' => $doc->updated_at?->format('Y-m-d\TH:i:s'),
            ])
            ->all();

        $ultimo = $registros->last();

        return [
            'total' => $total,
            'documentos' => $documentos,
            'proximoCursor' => $ultimo ? ['data' => $ultimo->data_efetiva_cursor, 'id' => $ultimo->id] : null,
            'concluido' => $registros->count() < $perPage,
        ];
    }
}
