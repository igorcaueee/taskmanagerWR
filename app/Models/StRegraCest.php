<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de regras de ICMS-ST por UF+CEST (MVA, alíquota interna,
 * adicional AMPARA/RS ou FEM/MG) — fonte de verdade única do motor de
 * cálculo. Seedado a partir dos motores Python já validados (ver
 * database/seeders/StRegrasCestSeeder.php); nunca editado por inferência.
 */
class StRegraCest extends Model
{
    protected $table = 'st_regras_cest';

    protected $fillable = [
        'uf',
        'cest',
        'situacao',
        'revogada_desde',
        'segmento',
        'descricao',
        'mva_12_pct',
        'mva_4_pct',
        'mva_pct',
        'aliquota_interna_pct',
        'adicional_tipo',
        'adicional_pct',
        'adicional_confirmado',
        'aliquota_confirmada',
        'nao_aplica_uf_origem',
        'fonte_legal',
        'editado_por',
        'editado_em',
    ];

    protected $casts = [
        'revogada_desde' => 'date',
        'mva_12_pct' => 'decimal:2',
        'mva_4_pct' => 'decimal:2',
        'mva_pct' => 'decimal:2',
        'aliquota_interna_pct' => 'decimal:2',
        'adicional_pct' => 'decimal:2',
        'adicional_confirmado' => 'boolean',
        'aliquota_confirmada' => 'boolean',
        'nao_aplica_uf_origem' => 'array',
        'editado_em' => 'datetime',
    ];

    public function historico(): HasMany
    {
        return $this->hasMany(StRegraCestHistorico::class, 'st_regra_cest_id');
    }
}
