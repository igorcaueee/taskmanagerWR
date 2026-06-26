<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LancamentoFinanceiro extends Model
{
    protected $table = 'lancamentos_financeiros';

    protected $fillable = [
        'cliente_id',
        'conta_azul_id',
        'conta_financeira_id',
        'categoria_id',
        'centro_custo_id',
        'tipo',
        'descricao',
        'valor',
        'data_vencimento',
        'data_competencia',
        'data_pagamento',
        'status',
        'conciliado',
        'forma_pagamento',
        'origem',
    ];

    protected $casts = [
        'valor'             => 'decimal:2',
        'conciliado'        => 'boolean',
        'data_vencimento'   => 'date',
        'data_competencia'  => 'date',
        'data_pagamento'    => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function contaFinanceira(): BelongsTo
    {
        return $this->belongsTo(ContaFinanceira::class, 'conta_financeira_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_id');
    }

    public function centroCusto(): BelongsTo
    {
        return $this->belongsTo(CentroCusto::class, 'centro_custo_id');
    }

    public function scopeCreditos(Builder $query): Builder
    {
        return $query->where('tipo', 'credito');
    }

    public function scopeDebitos(Builder $query): Builder
    {
        return $query->where('tipo', 'debito');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', 'pendente');
    }

    public function scopePagos(Builder $query): Builder
    {
        return $query->where('status', 'pago');
    }
}
