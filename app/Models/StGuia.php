<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guia de recolhimento (GNRE/RS ou DAE-SIARE/MG) preparada para uma NF-e.
 * O sistema nunca emite a guia — só prepara os dados; emissão é manual no
 * portal oficial, com o PDF resultante anexado de volta (pdf_guia_path).
 */
class StGuia extends Model
{
    protected $table = 'st_guias';

    public const TIPO_GNRE_RS = 'GNRE_RS';
    public const TIPO_DAE_MG = 'DAE_MG';

    public const STATUS_RASCUNHO = 'RASCUNHO';
    public const STATUS_PRONTA_PARA_EMISSAO = 'PRONTA_PARA_EMISSAO';
    public const STATUS_EMITIDA = 'EMITIDA';
    public const STATUS_PAGA = 'PAGA';

    protected $fillable = [
        'tipo',
        'cliente_id',
        'chave_acesso',
        'codigo_receita',
        'descricao_receita',
        'valor_icms_st',
        'valor_adicional',
        'valor_total',
        'data_vencimento',
        'data_pagamento',
        'periodo_referencia_mes',
        'periodo_referencia_ano',
        'status',
        'pdf_guia_path',
        'pdf_comprovante_path',
        'observacao',
    ];

    protected $casts = [
        'valor_icms_st' => 'decimal:2',
        'valor_adicional' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'data_vencimento' => 'date',
        'data_pagamento' => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
