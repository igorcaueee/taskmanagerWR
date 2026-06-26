<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdutoFinanceiro extends Model
{
    use HasFactory;

    protected $table = 'produtos_financeiros';

    protected $fillable = [
        'cliente_id',
        'nome',
        'codigo',
        'categoria',
        'preco_custo',
        'preco_venda',
        'estoque_atual',
        'ativo',
    ];

    protected $casts = [
        'preco_custo'   => 'decimal:2',
        'preco_venda'   => 'decimal:2',
        'estoque_atual' => 'decimal:3',
        'ativo'         => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
