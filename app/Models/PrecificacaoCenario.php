<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecificacaoCenario extends Model
{
    protected $table = 'precificacao_cenarios';

    protected $fillable = [
        'precificacao_produto_id',
        'nome',
        'uf_compra',
        'uf_venda',
        'valor_compra_total',
        'quantidade',
        'frete_compra',
        'ipi_pct',
        'markup_pct',
        'comissao_pct',
        'frete_venda_pct',
    ];

    protected $casts = [
        'valor_compra_total' => 'decimal:2',
        'quantidade' => 'decimal:3',
        'frete_compra' => 'decimal:2',
        'ipi_pct' => 'decimal:2',
        'markup_pct' => 'decimal:2',
        'comissao_pct' => 'decimal:2',
        'frete_venda_pct' => 'decimal:2',
    ];

    public function produto(): BelongsTo
    {
        return $this->belongsTo(PrecificacaoProduto::class, 'precificacao_produto_id');
    }
}
