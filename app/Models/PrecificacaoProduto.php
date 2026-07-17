<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrecificacaoProduto extends Model
{
    protected $table = 'precificacao_produtos';

    protected $fillable = [
        'cliente_id',
        'nome',
        'ncm',
        'cest',
        'unidade',
        'codigo_interno',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cenarios(): HasMany
    {
        return $this->hasMany(PrecificacaoCenario::class);
    }

    public function aliquota(): ?PrecificacaoAliquota
    {
        return PrecificacaoAliquota::paraNcmCest($this->ncm, $this->cest);
    }
}
