<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Percentual de redução de base do ICMS-ST na UF de destino, informado
 * explicitamente (nunca copiado do pRedBC de origem — ver
 * database/migrations/..._create_st_overrides_base_table.php).
 */
class StOverrideBase extends Model
{
    protected $table = 'st_overrides_base';

    protected $fillable = [
        'chave_acesso',
        'nfe_item',
        'percentual_reducao_pct',
        'observacao',
        'decidido_por',
        'decidido_em',
    ];

    protected $casts = [
        'percentual_reducao_pct' => 'decimal:2',
        'decidido_em' => 'datetime',
    ];
}
