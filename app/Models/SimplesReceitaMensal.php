<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Receita bruta do mês corrente lançada manualmente por cliente — alimenta a
 * transmissão do PGDASD (a SERPRO só devolve histórico já transmitido, nunca
 * o faturamento do período atual).
 */
class SimplesReceitaMensal extends Model
{
    protected $table = 'simples_receitas_mensais';

    protected $fillable = [
        'cliente_id',
        'periodo_apuracao',
        'receita_bruta_competencia',
        'receita_bruta_caixa',
        'regime_apuracao',
    ];

    protected $casts = [
        'receita_bruta_competencia' => 'decimal:2',
        'receita_bruta_caixa' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
