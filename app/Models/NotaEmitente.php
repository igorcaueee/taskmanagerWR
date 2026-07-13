<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotaEmitente extends Model
{
    protected $table = 'nota_emitentes';

    protected $fillable = [
        'cliente_id',
        'nome',
        'cpfcnpj',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function notasEmitidas(): HasMany
    {
        return $this->hasMany(NotaEmitida::class, 'emitente_id');
    }

    public function getNomeExibicaoAttribute(): string
    {
        return $this->cliente?->nome ?? (string) $this->nome;
    }

    public function getCpfcnpjExibicaoAttribute(): ?string
    {
        return $this->cliente?->cpfcnpj ?? $this->cpfcnpj;
    }
}
