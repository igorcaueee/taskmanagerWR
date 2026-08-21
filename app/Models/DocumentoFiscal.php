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
        'papel_cte',
        'data_saida_entrada',
        'xml_content',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'data_saida_entrada' => 'date',
    ];

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
