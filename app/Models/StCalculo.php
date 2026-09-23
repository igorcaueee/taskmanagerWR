<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um registro por item de NF-e processado pelo motor de ICMS-ST
 * (antecipação tributária pelo destinatário). Ver App\Services\IcmsSt.
 */
class StCalculo extends Model
{
    protected $table = 'st_calculos';

    protected $fillable = [
        'cliente_id',
        'chave_acesso',
        'nfe_numero',
        'nfe_item',
        'ncm',
        'produto',
        'cest_xml',
        'cest_usado',
        'cest_origem',
        'segmento',
        'cest_descricao',
        'cfop',
        'uf_origem',
        'uf_destino',
        'vprod',
        'vbc_origem',
        'base_operacao_usada',
        'vipi',
        'aliq_interestadual_pct',
        'mva_aplicada_pct',
        'base_st_calculada',
        'aliquota_interna_pct',
        'icms_proprio',
        'icms_st_devido',
        'adicional_tipo',
        'adicional_valor',
        'total_a_recolher',
        'responsavel',
        'status',
        'status_detalhe',
    ];

    protected $casts = [
        'vprod' => 'decimal:2',
        'vbc_origem' => 'decimal:2',
        'base_operacao_usada' => 'decimal:2',
        'vipi' => 'decimal:2',
        'aliq_interestadual_pct' => 'decimal:2',
        'mva_aplicada_pct' => 'decimal:2',
        'base_st_calculada' => 'decimal:2',
        'aliquota_interna_pct' => 'decimal:2',
        'icms_proprio' => 'decimal:2',
        'icms_st_devido' => 'decimal:2',
        'adicional_valor' => 'decimal:2',
        'total_a_recolher' => 'decimal:2',
    ];

    /** Status que representam alguma pendência de validação manual. */
    public const STATUS_PENDENTES = [
        'pendente_cest',
        'pendente_aliquota',
        'pendente_reducao_base',
        'pendente_fem',
        'uf_nao_suportada',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
