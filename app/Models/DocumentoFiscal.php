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
        // A data que define o período de uma nota é a de saída/entrada (dhSaiEnt) quando o
        // XML traz esse campo — não a de emissão. Uma nota emitida no fim do mês pode só ter
        // saído/entrado fisicamente no mês seguinte, e é isso que deve valer pro filtro (e
        // pro que é exibido), mesmo que "emissão" continue sendo o nome da coluna na tela.
        $dataEfetiva = 'COALESCE(data_saida_entrada, data_emissao)';

        $query = static::where('cliente_id', $clienteId)
            ->whereIn('tipo', $tipos)
            ->when($origens, fn ($q) => $q->whereIn('origem', $origens))
            ->whereRaw("{$dataEfetiva} BETWEEN ? AND ?", [$dataInicio, $dataFim]);

        $total = (clone $query)->count();

        $documentos = $query->orderByRaw($dataEfetiva)->orderBy('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (DocumentoFiscal $doc) => [
                'nsu'          => $doc->nsu,
                'tipo'         => $doc->tipo,
                'origem'       => $doc->origem,
                'chaveAcesso'  => $doc->chave_acesso,
                'numero'       => $doc->numero,
                'dataEmissao'  => ($doc->data_saida_entrada ?? $doc->data_emissao)?->format('Y-m-d\TH:i:s'),
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
