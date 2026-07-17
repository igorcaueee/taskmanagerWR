<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecificacaoAliquota extends Model
{
    protected $table = 'precificacao_aliquotas';

    protected $fillable = [
        'ncm',
        'cest',
        'descricao',
        'uf_referencia',
        'aliquota_icms_interna',
        'aplica_st',
        'aliquota_icms_st',
        'icms_venda_regra',
        'regime_pis_cofins',
        'aliquota_pis',
        'aliquota_cofins',
        'ativo',
    ];

    protected $casts = [
        'aliquota_icms_interna' => 'decimal:2',
        'aplica_st' => 'boolean',
        'aliquota_icms_st' => 'decimal:2',
        'aliquota_pis' => 'decimal:2',
        'aliquota_cofins' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public static function paraNcmCest(string $ncm, ?string $cest): ?self
    {
        return self::query()
            ->where('ncm', $ncm)
            ->where('cest', $cest)
            ->where('ativo', true)
            ->first();
    }
}
