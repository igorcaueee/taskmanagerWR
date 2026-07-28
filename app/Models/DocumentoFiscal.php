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
        'xml_content',
    ];

    protected $casts = [
        'data_emissao' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Busca os documentos já sincronizados de um cliente num período, no
     * formato de array que a tela de NF-e já espera.
     */
    public static function doPeriodo(int $clienteId, array $tipos, string $dataInicio, string $dataFim): array
    {
        return static::where('cliente_id', $clienteId)
            ->whereIn('tipo', $tipos)
            ->whereBetween('data_emissao', [$dataInicio, $dataFim])
            ->orderBy('data_emissao')
            ->get()
            ->map(fn (DocumentoFiscal $doc) => [
                'nsu'          => $doc->nsu,
                'tipo'         => $doc->tipo,
                'chaveAcesso'  => $doc->chave_acesso,
                'numero'       => $doc->numero,
                'dataEmissao'  => $doc->data_emissao?->format('Y-m-d\TH:i:s'),
                'emitenteNome' => $doc->emitente_nome,
                'emitenteDoc'  => $doc->emitente_doc,
                'valor'        => $doc->valor,
                'situacao'     => $doc->situacao,
                'xmlContent'   => $doc->xml_content,
            ])
            ->all();
    }
}
