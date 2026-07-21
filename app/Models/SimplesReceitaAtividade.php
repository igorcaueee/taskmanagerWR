<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Receita de uma atividade específica dentro de um período — quebra do total
 * lançado em SimplesReceitaMensal por atividade cadastrada no Simples Nacional.
 */
class SimplesReceitaAtividade extends Model
{
    protected $table = 'simples_receitas_atividades';

    protected $fillable = [
        'cliente_id',
        'periodo_apuracao',
        'id_atividade',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tributos(): HasMany
    {
        return $this->hasMany(SimplesReceitaAtividadeTributo::class);
    }
}
