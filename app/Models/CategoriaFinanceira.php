<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanceira extends Model
{
    protected $table = 'categorias_financeiras';

    protected $fillable = [
        'cliente_id',
        'conta_azul_id',
        'nome',
        'tipo',
        'categoria_pai_id',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pai(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_pai_id');
    }

    public function filhas(): HasMany
    {
        return $this->hasMany(CategoriaFinanceira::class, 'categoria_pai_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'categoria_id');
    }
}
