<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tratamento tributário (isenção/redução/substituição/monofásica/etc.) de um
 * tributo específico dentro de uma atividade — ver ressalva de mapeamento no
 * docblock da migration/App\Support\PgdasdAtividades.
 */
class SimplesReceitaAtividadeTributo extends Model
{
    protected $table = 'simples_receitas_atividades_tributos';

    protected $fillable = [
        'simples_receita_atividade_id',
        'cod_tributo',
        'tipo_ajuste',
        'identificador_isencao',
        'percentual_reducao',
        'motivo_suspensao',
        'valor',
    ];

    protected $casts = [
        'percentual_reducao' => 'decimal:2',
        'valor' => 'decimal:2',
    ];

    public function receitaAtividade(): BelongsTo
    {
        return $this->belongsTo(SimplesReceitaAtividade::class, 'simples_receita_atividade_id');
    }
}
