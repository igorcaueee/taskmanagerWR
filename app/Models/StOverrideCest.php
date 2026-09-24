<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Atribuição/correção manual de CEST por item de NF-e (ver
 * database/migrations/..._create_st_overrides_cest_table.php). cest_atribuido
 * nulo = ainda só sugestão, pendente de confirmação explícita.
 */
class StOverrideCest extends Model
{
    protected $table = 'st_overrides_cest';

    protected $fillable = [
        'chave_acesso',
        'nfe_item',
        'ncm',
        'produto',
        'cest_sugerido',
        'cest_atribuido',
        'nao_sujeito_st',
        'fundamento',
        'observacao',
        'decidido_por',
        'decidido_em',
    ];

    protected $casts = [
        'nao_sujeito_st' => 'boolean',
        'decidido_em' => 'datetime',
    ];
}
