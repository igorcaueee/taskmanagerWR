<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
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
        'possibilidade',
    ];

    protected $casts = [
        'vencimento_certificado' => 'date',
        'cliente_desde' => 'date',
        'dataabertura' => 'date',
        'data_encerramento' => 'date',
    ];

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'cliente_produto');
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(ContatoCliente::class);
    }
}
