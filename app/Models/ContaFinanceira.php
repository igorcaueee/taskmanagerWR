<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaFinanceira extends Model
{
    protected $table = 'contas_financeiras';

    protected $fillable = [
        'cliente_id',
        'conta_azul_id',
        'nome',
        'tipo',
        'saldo_atual',
        'ativa',
        'atualizado_em',
    ];

    protected $casts = [
        'saldo_atual'   => 'decimal:2',
        'ativa'         => 'boolean',
        'atualizado_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'conta_financeira_id');
    }
}
