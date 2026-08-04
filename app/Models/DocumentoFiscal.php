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
        'data_saida_entrada',
        'xml_content',
    ];

    protected $casts = [
        'data_emissao'       => 'date',
        'data_saida_entrada' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
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
        $clienteCnpj = preg_replace('/\D/', '', Cliente::find($clienteId)?->cpfcnpj ?? '');

        $dataEfetiva = 'CASE WHEN emitente_doc = ? THEN data_emissao ELSE COALESCE(data_saida_entrada, data_emissao) END';

        $query = static::where('cliente_id', $clienteId)
            ->whereIn('tipo', $tipos)
            ->when($origens, fn ($q) => $q->whereIn('origem', $origens))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$clienteCnpj, $dataInicio, $dataFim]);

        $total = (clone $query)->count();

        $documentos = $query->orderByRaw("{$dataEfetiva}", [$clienteCnpj])->orderBy('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (DocumentoFiscal $doc) => [
                'nsu'          => $doc->nsu,
                'tipo'         => $doc->tipo,
                'origem'       => $doc->origem,
                'chaveAcesso'  => $doc->chave_acesso,
                'numero'       => $doc->numero,
                'dataEmissao'  => $doc->data_emissao?->format('Y-m-d\TH:i:s'),
                'dataSaidaEntrada' => $doc->data_saida_entrada?->format('Y-m-d\TH:i:s'),
                'emitenteNome' => $doc->emitente_nome,
                'emitenteDoc'  => $doc->emitente_doc,
                'valor'        => $doc->valor,
                'situacao'     => $doc->situacao,
                'tpNf'         => $doc->tp_nf,
                'xmlContent'   => $doc->xml_content,
                'sincronizadoEm' => $doc->updated_at?->format('Y-m-d\TH:i:s'),
            ])
            ->all();

        return ['total' => $total, 'documentos' => $documentos];
    }
}
