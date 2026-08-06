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
     * formato de array que a tela de NF-e já espera. Paginado — cada
     * documento carrega o xml_content inteiro, então trazer tudo de uma vez
     * num período grande (milhares de docs) estoura o memory_limit do PHP.
     */
    public static function doPeriodo(int $clienteId, array $tipos, string $dataInicio, string $dataFim, ?array $origens = null, int $page = 1, int $perPage = 500): array
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

        $documentos = $query->orderByRaw("{$dataEfetiva}", [$clienteCnpj])->orderBy('id')
            ->forPage($page, $perPage)
            ->get()
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
                'xmlContent' => $doc->xml_content,
                'sincronizadoEm' => $doc->updated_at?->format('Y-m-d\TH:i:s'),
            ])
            ->all();

        return ['total' => $total, 'documentos' => $documentos];
    }
}
