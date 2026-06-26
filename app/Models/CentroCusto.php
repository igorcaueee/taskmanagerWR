<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroCusto extends Model
{
    protected $table = 'centros_custo';

    protected $fillable = [
        'cliente_id',
        'conta_azul_id',
        'codigo',
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'centro_custo_id');
    }
}
