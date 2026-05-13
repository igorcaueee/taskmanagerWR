<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'pasta_arquivos',
        'segmentacao_id',
        'descricao',
        'cpfcnpj',
        'tipo',
        'regime_tributario',
        'cidade',
        'estado',
        'status',
        'motivo_encerramento',
        'data_encerramento',
        'fator_r',
        'cliente_desde',
        'dataabertura',
        'vencimento_certificado',
        'faturamento',
        'servico',
        'honorario',
        'capital_social',
        'possibilidade',
    ];

    protected $casts = [
        'vencimento_certificado' => 'date',
        'cliente_desde' => 'date',
        'dataabertura' => 'date',
        'data_encerramento' => 'date',
        'capital_social' => 'decimal:2',
    ];

    public function segmentacao(): BelongsTo
    {
        return $this->belongsTo(Segmentacao::class);
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'cliente_produto');
    }

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class)->orderBy('ordem');
    }

    public function conhecimentos(): HasMany
    {
        return $this->hasMany(ClienteConhecimento::class)->orderByDesc('created_at');
    }
}
