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
     * @return array{0: string, 1: string}
     */
    private static function dataEfetivaSql(int $clienteId): array
    {
        $clienteCnpj = preg_replace('/\D/', '', Cliente::find($clienteId)?->cpfcnpj ?? '');

        $dataEfetiva = 'CASE WHEN emitente_doc = ? THEN data_emissao ELSE COALESCE(data_saida_entrada, data_emissao) END';

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
